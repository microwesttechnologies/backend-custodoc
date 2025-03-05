<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Models\Document;
use App\Models\Folder;

class FolderController extends Controller
{

    public function getFoldersByParent($parent = 'null', Request $request)
    {

        DB::enableQueryLog();

        $userAuth = Auth::user();

        $cacheKey = "folders_parent_{$parent}_deleted_{$request->query('deleted')}_favorite_{$request->query('isFavorite')}";

        // return Cache::remember($cacheKey, 60, function () use ($parent, $request, $userAuth) {

        $concatWhereNotNull = $request->query('deleted') === 'true' ? 'IS NOT NULL' : 'IS NULL';

        $queryFolders = Folder::select(
            'folders.*',
            DB::raw('IF(fd.identification, 1, 0) AS isFavorite'),
            DB::raw("(SELECT COUNT(*) FROM documents AS d WHERE d.id_folder = folders.id_folder and d.deleted_at {$concatWhereNotNull}) AS fileCount")
        )
            ->leftJoin('favorite_documents AS fd', function ($leftJoin) use ($userAuth) {
                $leftJoin->on('fd.id_folder', 'folders.id_folder')
                    ->where('fd.identification', $userAuth->identification);
            })->where('folders.id_company', $userAuth->id_company);

        // Filtro por padre
        if ($parent === 'null' && $request->query('isFavorite') !== 'true' && !$request->query('search')) {
            $queryFolders->whereNull('folders.parent');
        }

        if ($parent !== 'null') {
            $queryFolders->where('folders.parent', $parent);
        }

        // Filtro por estado eliminado
        if ($request->query('deleted') === 'true') {
            $foldersWithDeletedChildren = Folder::select('parent')
                ->whereNotNull('deleted_at')
                ->distinct();

            $documentsDeleted = Document::select('id_folder')
                ->whereNotNull('deleted_at')
                ->distinct();

            $ancestorFolders = Folder::select('folders.id_folder')
                ->whereIn('folders.id_folder', function ($query) use ($foldersWithDeletedChildren, $documentsDeleted) {
                    $query->select('parent')
                        ->from('folders')
                        ->whereIn('folders.id_folder', $foldersWithDeletedChildren)
                        ->orWhereIn('folders.id_folder', $documentsDeleted);
                });


            $queryFolders->where(function ($where) use ($foldersWithDeletedChildren, $documentsDeleted, $ancestorFolders) {
                $where->whereNotNull('deleted_at')
                    ->orWhereIn('folders.id_folder', $foldersWithDeletedChildren)
                    ->orWhereIn('folders.id_folder', $documentsDeleted)
                    ->orWhereIn('folders.id_folder', $ancestorFolders);
            })->orderBy('folders.deleted_at', 'DESC');
        } else {
            $queryFolders->whereNull('folders.deleted_at')
                ->orderBy('folders.created_at', 'DESC');
        }

        /** Filtro para favoritos */
        if ($request->query('isFavorite') === 'true') {
            $queryFolders->whereNotNull('fd.id_folder');
        }

        if ($userAuth->id_area !== 1) {
            $queryFolders->where(function ($where) use ($userAuth) {
                $where->whereIn('folders.id_area', [$userAuth->id_area, 1]);
            });
        }

        $search = $request->query('search');
        if ($search) {
            $queryFolders->where('folders.name', 'LIKE', "%{$search}%");
        }

        return response()->json($queryFolders->get());
    }

    public function createFolder(Request $request)
    {

        try {
            $userAuth = Auth::user();

            $folder = [
                'identification' => $userAuth->identification,
                'id_company' => $userAuth->id_company,
                'parent' => $request->parent ?? null,
                'id_area' => $request->id_area,
                'name' => $request->name,
            ];

            Folder::create($folder);

            return response()->json(['status' => true, 'message' => 'Carpeta creada exitosamente']);
        } catch (\Throwable $th) {
            DB::rollBack();
            if ($th->getMessage() !== null) {
                return response()->json(['status' => false, 'message' => $th->getMessage() . " en la línea " . $th->getLine()]);
            } else {
                return response()->json(['status' => false, 'message' => $th]);
            }
        }
    }

    public function updateFolder(Request $request)
    {

        try {

            $folder = [
                'name' => $request->name,
            ];

            Folder::where('id_folder', $request->id_folder)->update($folder);

            return response()->json(['status' => true, 'message' => 'Carpeta actualizada exitosamente']);
        } catch (\Throwable $th) {
            DB::rollBack();
            if ($th->getMessage() !== null) {
                return response()->json(['status' => false, 'message' => $th->getMessage() . " en la línea " . $th->getLine()]);
            } else {
                return response()->json(['status' => false, 'message' => $th]);
            }
        }
    }

    public function deleteFolder($id_folder, Request $request)
    {
        DB::beginTransaction();
        try {

            $userAuth = Auth::user();
            $temporal = $request->query('temporal');

            $this->deleteFoldersAndDocuments($userAuth, $id_folder, $temporal);

            DB::commit();
            return response()->json(['status' => true, 'message' => 'Carpeta eliminada exitosamente']);
        } catch (\Throwable $th) {
            DB::rollBack();
            if ($th->getMessage() !== null) {
                return response()->json(['status' => false, 'message' => $th->getMessage() . " en la línea " . $th->getLine()]);
            } else {
                return response()->json(['status' => false, 'message' => $th]);
            }
        }
    }

    private function deleteFoldersAndDocuments($userAuth, $id_folder, $temporal)
    {

        if ($temporal === 'true') {
            Folder::where('id_folder', $id_folder)->update(['deleted_at' => now()]);
            DB::table('favorite_documents')->where([
                ['identification', $userAuth->identification],
                ['id_folder', $id_folder],
            ])->delete();

            Document::where('id_folder', $id_folder)->update(['deleted_at' => now()]);
        } else {
            Folder::where('id_folder', $id_folder)->delete();
            $documents = Document::where('id_folder', $id_folder)->get();

            foreach ($documents as $document) {
                Document::where('id_history', $document->id_history)->delete();

                $fullPath = storage_path('app/public/' . $document->path);
                if (file_exists($fullPath)) {
                    unlink($fullPath); // Elimina el archivo
                }
            }
        }

        $folders = Folder::select('id_folder')->where('parent', $id_folder)->get();

        foreach ($folders as $folder) {
            $this->deleteFoldersAndDocuments($userAuth, $folder->id_folder, $temporal);
        }
    }

    public function deleteFoldersAndDocumentsById(Request $request)
    {
        DB::beginTransaction();
        try {

            $userAuth = Auth::user();
            $temporal = $request->query('temporal');

            foreach ($request->folders as $id_folder) {
                $this->deleteFoldersAndDocuments($userAuth, $id_folder, $temporal);
            }

            foreach ($request->documents as $id_history) {
                if ($temporal === 'true') {
                    Document::where('id_history', $id_history)->update(['deleted_at' => now()]);
                } else {
                    $document = Document::where('id_history', $id_history)->first();
                    if ($document) {
                        Document::where('id_history', $id_history)->delete();
                        $fullPath = storage_path('app/public/' . $document->path);
                        if (file_exists($fullPath)) {
                            unlink($fullPath); // Elimina el archivo
                        }
                    }
                }
            }

            DB::commit();
            return response()->json(['status' => true, 'message' => 'Carpeta eliminada exitosamente']);
        } catch (\Throwable $th) {
            DB::rollBack();
            if ($th->getMessage() !== null) {
                return response()->json(['status' => false, 'message' => $th->getMessage() . " en la línea " . $th->getLine()]);
            } else {
                return response()->json(['status' => false, 'message' => $th]);
            }
        }
    }

    public function restoreFolder($id_folder)
    {
        DB::beginTransaction();
        try {

            $userAuth = Auth::user();

            $this->restoreFoldersAndDocuments($userAuth, $id_folder);

            DB::commit();
            return response()->json(['status' => true, 'message' => 'Carpeta restaurada exitosamente']);
        } catch (\Throwable $th) {
            DB::rollBack();
            if ($th->getMessage() !== null) {
                return response()->json(['status' => false, 'message' => $th->getMessage() . " en la línea " . $th->getLine()]);
            } else {
                return response()->json(['status' => false, 'message' => $th]);
            }
        }
    }

    private function restoreFoldersAndDocuments($userAuth, $id_folder)
    {

        Folder::where('id_folder', $id_folder)->update(['deleted_at' => null]);
        Document::where('id_folder', $id_folder)->update(['deleted_at' => null]);

        $folders = Folder::select('id_folder')->where('parent', $id_folder)->get();

        foreach ($folders as $folder) {
            $this->restoreFoldersAndDocuments($userAuth, $folder->id_folder);
        }
    }
}

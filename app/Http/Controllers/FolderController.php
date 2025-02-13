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

        $queryFolders = Folder::select('folders.*', DB::raw('IF(fd.identification, 1, 0) AS isFavorite'))
            ->leftJoin('favorite_documents AS fd', function ($leftJoin) use ($userAuth) {
                $leftJoin->on('fd.id_folder', 'folders.id_folder')
                    ->where('fd.identification', $userAuth->identification);
            })->where('folders.id_company', $userAuth->id_company);

        // Filtro por padre
        if ($parent === 'null') {
            $queryFolders->whereNull('folders.parent');
        } else {
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

        // Filtro por favoritos
        if ($request->query('isFavorite') === 'true') {
            $favoriteFolders = Folder::select('folders.parent')
                ->join('favorite_documents AS fd', function ($join) use ($userAuth,) {
                    $join->on('fd.id_folder', DB::raw('folders.id_folder'))
                        ->where('fd.identification', DB::raw($userAuth->identification));
                })->whereNotNull('folders.parent')
                ->distinct();

            $favoriteDocuments = Document::select('documents.id_folder')
                ->join('favorite_documents AS fd', function ($join) use ($userAuth,) {
                    $join->on('fd.id_history', DB::raw('documents.id_history'))
                        ->where('fd.identification', DB::raw($userAuth->identification));
                })->whereNotNull('documents.id_folder')
                ->distinct();

            $ancestorFolders = Folder::select('folders.id_folder')
                ->whereIn('folders.id_folder', function ($query) use ($favoriteFolders, $favoriteDocuments) {
                    $query->select('parent')
                        ->from('folders')
                        ->whereIn('folders.id_folder', $favoriteFolders)
                        ->orWhereIn('folders.id_folder', $favoriteDocuments);
                });

            $queryFolders->where(function ($where) use ($favoriteFolders, $favoriteDocuments, $ancestorFolders) {
                $where->whereNotNull('fd.identification')
                    ->orWhereIn('folders.id_folder', $favoriteFolders)
                    ->orWhereIn('folders.id_folder', $favoriteDocuments)
                    ->orWhereIn('folders.id_folder', $ancestorFolders);
            });
        }

        // $queryFolders->get();
        return response()->json($queryFolders->get());
        // return response()->json(DB::getQueryLog());
        // });
    }

    public function createFolder(Request $request)
    {

        try {
            $userAuth = Auth::user();

            $folder = [
                'identification' => $userAuth->identification,
                'id_company' => $userAuth->id_company,
                'parent' => $request->parent ?? null,
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

<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Http\Request;
use App\Models\Document;
use Illuminate\Support\Facades\DB;

class DocumentController extends Controller
{
    public function getAllDocuments(Request $request)
    {
        $userAuth = Auth::user();

        $queryDocuments = Document::select('documents.*', 'c.name AS name_customer')
            ->join('customers AS c', 'documents.identification', 'c.identification')
            ->whereNull('deleted_at')
            ->orderBy('documents.created_at', 'DESC');

        if ($userAuth->id_rol !== 1) {
            $queryDocuments->where('documents.id_company', $request->query('id_company') ?? $userAuth->id_company);
        }

        $rangeDates = $request->query('rangeDates');

        if ($rangeDates) {
            $rangeDates = explode(',', $rangeDates);
            $queryDocuments->whereBetween('documents.created_at', [$rangeDates[0], $rangeDates[1]]);
        }

        return response()->json($queryDocuments->get());
    }

    public function getDocumentsByFolder($id_folder = 'null', Request $request)
    {
        $userAuth = Auth::user();

        $queryDocuments = Document::select('documents.*', DB::raw('IF(fd.identification, 1, 0) AS isFavorite'))
            ->join('users AS u', 'documents.user_identification', 'u.identification')
            ->leftJoin('favorite_documents AS fd', function ($leftJoin) use ($userAuth) {
                $leftJoin->on('fd.id_history', 'documents.id_history')
                    ->where('fd.identification', $userAuth->identification);
            })
            ->where('documents.id_company', $request->query('id_company') ?? $userAuth->id_company);

        if ($id_folder === 'null' && $request->query('isViewed') !== 'true' && $request->query('isFavorite') !== 'true' && !$request->query('search')) {
            $queryDocuments->whereNull('documents.id_folder');
        }

        if ($id_folder !== 'null') {
            $queryDocuments->where('documents.id_folder', $id_folder);
        }

        if ($request->query('deleted') === 'true') {
            $queryDocuments->whereNotNull('documents.deleted_at')
                ->orderBy('deleted_at', 'DESC');
        } else {
            $queryDocuments->where('documents.deleted_at', null)
                ->orderBy('created_at', 'DESC');
        }

        if ($request->query('isFavorite') === 'true') {
            $queryDocuments->whereNotNull('fd.id_history');
        }

        if ($request->query('isViewed') === 'true') {
            $queryDocuments->join('recently_viewed AS rv', function ($join) use ($userAuth) {
                $join->on('rv.id_history', 'documents.id_history')->where('rv.identification', $userAuth->identification);
            });
        }

        if ($userAuth->id_area !== 1) {
            $queryDocuments->where(function ($where) use ($userAuth) {
                $where->whereIn('documents.id_area', [$userAuth->id_area, 1]);
            });
        }

        $search = $request->query('search');
        if ($search) {
            $queryDocuments->where('documents.name', 'LIKE', "%{$search}%");
        }

        return response()->json($queryDocuments->get());
    }

    public function createOrUpdateDocument(Request $request)
    {

        DB::beginTransaction();

        $response = [];
        try {

            if ($request->id_history) {
                $response = $this->updateDocument($request);
            } else {
                $response = $this->createDocument($request);
            }

            DB::commit();
            return response()->json($response);
        } catch (\Throwable $th) {
            DB::rollBack();
            if (isset($response['filePath'])) {
                // Eliminar los archivos que se subieron antes del error
                $fullPath = storage_path('app/public/' . $response['filePath']);
                if (file_exists($fullPath)) {
                    unlink($fullPath); // Elimina el archivo
                }
            }

            if ($th->getMessage() !== null) {
                return response()->json(['status' => false, 'message' => $th->getMessage() . " en la línea " . $th->getLine()]);
            } else {
                return response()->json(['status' => false, 'message' => $th]);
            }
        }
    }

    private function createDocument(Request $request)
    {
        $global = new GlobalController();
        $userAuth = Auth::user();
        $filePath = $global->uploadOrUpdateFile($request->file('file'), 'documents');

        $document = [
            'id_company' => $request->id_company ?? $userAuth->id_company,
            'identification' => $request->identification ?? null,
            'user_identification' => $userAuth->identification,
            'description' => $request->description,
            'id_folder' => $request->id_folder,
            'id_area' => $request->id_area,
            'name' => $request->name,
            'path' => $filePath,
        ];

        Document::create($document);

        return ['status' => true, 'message' => 'Registro exitoso', 'filePath' => $filePath];
    }

    private function updateDocument(Request $request)
    {

        $filePath = "";
        $global = new GlobalController();

        $document = [
            'description' => $request->description,
            'name' => $request->name,
        ];

        if ($request->hasFile('file')) {
            $filePath = $global->uploadOrUpdateFile($request->file('file'), 'documents', $request->path);
            $document['path'] = $filePath;
        }

        Document::where('id_history', $request->id_history)->update($document);

        return ['status' => true, 'message' => 'Documento actualizado exitosamente', 'filePath' => $filePath];
    }

    public function bulkUploadDocuments(Request $request)
    {

        // Inicializar un array para almacenar los archivos subidos
        $uploadedFiles = [];

        DB::beginTransaction();

        try {
            $global = new GlobalController();
            $userAuth = Auth::user();

            foreach ($request->documents as $documentData) {
                // Sube el archivo y guarda la ruta
                $filePath = $global->uploadOrUpdateFile($documentData['file'], 'documents');
                $uploadedFiles[] = $filePath;

                // Crea el registro en la base de datos
                $document = [
                    'path' => $filePath,
                    'user_identification' => $userAuth->identification,
                    'identification' => $documentData['identification'],
                    'description' => $documentData['description'],
                    'name' => $documentData['name'],
                ];

                Document::create($document);
            }

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Documentos y registros guardados exitosamente',
            ]);
        } catch (\Throwable $th) {
            // Revertir transacciones
            DB::rollBack();

            // Eliminar los archivos que se subieron antes del error
            foreach ($uploadedFiles as $filePath) {
                $fullPath = storage_path('app/public/' . $filePath);
                if (file_exists($fullPath)) {
                    unlink($fullPath); // Elimina el archivo
                }
            }

            if ($th->getMessage() !== null) {
                return response()->json(['status' => false, 'message' => $th->getMessage() . " en la línea " . $th->getLine()]);
            } else {
                return response()->json(['status' => false, 'message' => $th]);
            }
        }
    }

    public function getFile($id_history)
    {
        $userAuth = Auth::user();
        $document = Document::find($id_history);

        if ($document && File::exists(storage_path('app/public/' . $document->path))) {
            $recentlyViewed = DB::table('recently_viewed')->where([['identification', $userAuth->identification], ['id_history', $id_history]])->first();
            if ($recentlyViewed) {
                DB::table('recently_viewed')->where([['identification', $userAuth->identification], ['id_history', $id_history]])->update(['date_viewed' => now()]);
            } else {
                DB::table('recently_viewed')->insert([
                    'identification' => $userAuth->identification,
                    'id_history' => $id_history,
                    'date_viewed' => now()
                ]);
            }
            return response()->file(storage_path('app/public/' . $document->path));
        }

        return response()->json([], 404);
    }

    public function getAllDocumentsByCustomer($id_customer, Request $request)
    {
        $queryDocumentsByCustomer = Document::where('identification', $id_customer)->whereNull('deleted_at');

        // Obtener el valor del queryParam "rangeDates"
        $rangeDates = $request->query('rangeDates');

        if ($rangeDates) {
            $rangeDates = explode(',', $rangeDates);
            $queryDocumentsByCustomer->whereBetween('created_at', [$rangeDates[0], $rangeDates[1]]);
        }

        return response()->json($queryDocumentsByCustomer->get());
    }

    public function deleteDocument($id_history, Request $request)
    {
        DB::beginTransaction();
        try {

            $userAuth = Auth::user();
            $temporal = $request->query('temporal');

            if ($temporal === 'true') {
                Document::where('id_history', $id_history)->update(['deleted_at' => now()]);
                DB::table('favorite_documents')->where([
                    ['identification', $userAuth->identification],
                    ['id_history', $id_history],
                ])->delete();
            } else {
                $document = Document::find($id_history);

                Document::where('id_history', $id_history)->delete();

                $fullPath = storage_path('app/public/' . $document->path);
                if (file_exists($fullPath)) {
                    unlink($fullPath); // Elimina el archivo
                }
            }

            DB::commit();
            return response()->json(['status' => true, 'message' => 'Registro eliminado exitosamente']);
        } catch (\Throwable $th) {
            DB::rollBack();
            if ($th->getMessage() !== null) {
                return response()->json(['status' => false, 'message' => $th->getMessage() . " en la línea " . $th->getLine()]);
            } else {
                return response()->json(['status' => false, 'message' => $th]);
            }
        }
    }

    public function markAndDesmarkFavorite(Request $request)
    {
        try {

            $userAuth = Auth::user();
            $id_history = $request->query('id_history');
            $id_folder = $request->query('id_folder');

            $key = $id_history ? 'id_history' : 'id_folder';
            $value = $id_history ?? $id_folder;

            $recordFound = DB::table('favorite_documents')->where(
                [
                    ['identification', $userAuth->identification],
                    [$key, $value]
                ]
            )->first();

            if ($recordFound) {
                DB::table('favorite_documents')->where(
                    [
                        ['identification', $userAuth->identification],
                        [$key, $value]
                    ]
                )->delete();
            } else {
                DB::table('favorite_documents')->insert(
                    [
                        'identification' => $userAuth->identification,
                        $key => $value
                    ]
                );
            }

            return response()->json(['status' => true, 'message' => ($recordFound ? 'Desmarcado' : 'Marcado') . ' como favorito exitosamente']);
        } catch (\Throwable $th) {
            if ($th->getMessage() !== null) {
                return response()->json(['status' => false, 'message' => $th->getMessage() . " en la línea " . $th->getLine()]);
            } else {
                return response()->json(['status' => false, 'message' => $th]);
            }
        }
    }

    public function restoreDocument($id_history)
    {
        try {

            Document::where('id_history', $id_history)->update(['deleted_at' => null]);

            return response()->json(['status' => true, 'message' => 'Documento restaurado exitosamente']);
        } catch (\Throwable $th) {
            DB::rollBack();
            if ($th->getMessage() !== null) {
                return response()->json(['status' => false, 'message' => $th->getMessage() . " en la línea " . $th->getLine()]);
            } else {
                return response()->json(['status' => false, 'message' => $th]);
            }
        }
    }
}

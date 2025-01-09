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
            ->orderBy('created_at', 'DESC');

        if ($userAuth->id_rol !== 1 && $userAuth->id_rol !== 4) {
            $queryDocuments->where('id_company', $userAuth->id_company);
        }

        // Obtener el valor del queryParam "rangeDates"
        $rangeDates = $request->query('rangeDates');

        if ($rangeDates) {
            $rangeDates = explode(',', $rangeDates);
            $queryDocuments->whereBetween('documents.created_at', [$rangeDates[0], $rangeDates[1]]);
        }

        return response()->json($queryDocuments->get());
    }

    public function createDocument(Request $request)
    {

        DB::beginTransaction();
        $filePath = "";
        try {
            $global = new GlobalController();
            $userAuth = Auth::user();
            $filePath = $global->uploadFile($request->file('file'), 'documents');

            $document = [
                'path' => $filePath,
                'user_identification' => $userAuth->identification,
                'identification' => $request->identification,
                'description' => $request->description,
                'name' => $request->name,
            ];

            Document::create($document);

            DB::commit();
            return response()->json(['status' => true, 'message' => 'Registro exitoso']);
        } catch (\Throwable $th) {
            DB::rollBack();
            // Eliminar los archivos que se subieron antes del error
            $fullPath = storage_path('app/public/' . $filePath);
            if (file_exists($fullPath)) {
                unlink($fullPath); // Elimina el archivo
            }

            if ($th->getMessage() !== null) {
                return response()->json(['status' => false, 'message' => $th->getMessage() . " en la línea " . $th->getLine()]);
            } else {
                return response()->json(['status' => false, 'message' => $th]);
            }
        }
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
                $filePath = $global->uploadFile($documentData['file'], 'documents');
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
        $document = Document::find($id_history);

        if ($document && File::exists(storage_path('app/public/' . $document->path))) {
            return response()->file(storage_path('app/public/' . $document->path));
        }

        return response()->json([], 404);
    }

    public function getAllDocumentsByCustomer($id_customer, Request $request)
    {
        $queryDocumentsByCustomer = Document::where('identification', $id_customer);

        // Obtener el valor del queryParam "rangeDates"
        $rangeDates = $request->query('rangeDates');

        if ($rangeDates) {
            $rangeDates = explode(',', $rangeDates);
            $queryDocumentsByCustomer->whereBetween('created_at', [$rangeDates[0], $rangeDates[1]]);
        }

        return response()->json($queryDocumentsByCustomer->get());
    }

    public function deleteDocument($id_history)
    {
        DB::beginTransaction();
        try {

            $document = Document::where('id_history', $id_history)->first();

            Document::where('id_history', $id_history)->delete();

            $fullPath = storage_path('app/public/' . $document->path);
            if (file_exists($fullPath)) {
                unlink($fullPath); // Elimina el archivo
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
}

<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Http\Request;
use App\Models\Document;
use Illuminate\Support\Facades\DB;

class DocumentController extends Controller
{
    public function getAllDocuments()
    {
        $user = Auth::user();

        $queryDocuments = Document::select('documents.*', 'c.name AS name_customer')
            ->join('customers AS c', 'documents.identification', 'c.identification')
            ->orderBy('created_at', 'DESC');

        if ($user->id_rol !== 1) {
            $queryDocuments->where('id_company', $user->id_company);
        }

        return response()->json($queryDocuments->get());
    }

    public function createDocument(Request $request)
    {

        DB::beginTransaction();
        $filePath = "";
        try {
            $global = new GlobalController();
            $user = Auth::user();
            $filePath = $global->uploadFile($request->file('file'), 'documents');

            $document = [
                'path' => $filePath,
                'user_identification' => $user->identification,
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
            $user = Auth::user();

            foreach ($request->documents as $documentData) {
                // Sube el archivo y guarda la ruta
                $filePath = $global->uploadFile($documentData['file'], 'documents');
                $uploadedFiles[] = $filePath;

                // Crea el registro en la base de datos
                $document = [
                    'path' => $filePath,
                    'user_identification' => $user->identification,
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

    public function getAllDocumentsByCustomer($id_customer)
    {
        $documentsByCustomer = Document::where('identification', $id_customer)->get();

        return response()->json($documentsByCustomer);
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

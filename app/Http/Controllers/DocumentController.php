<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Models\Document;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

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

        try {
            $global = new GlobalController();
            $user = Auth::user();

            $document = [
                'path' => $global->uploadFile($request->file('file'), 'documents'),
                'user_identification' => $user->identification,
                'identification' => $request->identification,
                'description' => $request->description,
                'name' => $request->name,
            ];

            Document::create($document);

            return response()->json(['status' => true, 'message' => 'Registro exitoso']);
        } catch (\Throwable $th) {
            if ($th->getMessage() !== null) {
                return response()->json(['status' => false, 'message' => $th->getMessage() . " en la línea " . $th->getLine()]);
            } else {
                return response()->json(['status' => false, 'message' => $th]);
            }
        }
    }

    // public function upload

    public function getFile($id_history)
    {
        $document = Document::find($id_history);

        if ($document && File::exists(storage_path('app/public/' . $document->path))) {
            return response()->file(storage_path('app/public/' . $document->path));
        }

        return response()->json([], 404);
    }
}

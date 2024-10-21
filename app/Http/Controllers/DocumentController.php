<?php


namespace App\Http\Controllers;

use Illuminate\Http\Request;

class DocumentController extends Controller
{
    public function uploadDocument(Request $request)
    {
        $request->validate([
            'documento' => 'required|file|mimes:pdf|max:10000',
            'identidad' => 'required|string',
        ]);

        if ($request->file('documento')->isValid()) {
            $identidad = $request->input('identidad');
            $fecha = now()->format('Ymd');

            $nombreArchivo = "{$identidad}_{$fecha}.pdf";


            $path = $request->file('documento')->storeAs('documents', $nombreArchivo, 'public');
            $url = url('storage/' . $path);

            return response()->json(['message' => 'Documento subido correctamente', 'path' => $url], 200);
        }

        return response()->json(['message' => 'Error al subir el documento'], 400);
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\TypesDocuments;

class TypesDocumentController extends Controller
{

    public function getAllTypesDocument()
    {
        return response()->json(TypesDocuments::all());
    }
}

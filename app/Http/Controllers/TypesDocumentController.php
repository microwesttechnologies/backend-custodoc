<?php

namespace App\Http\Controllers;

use App\Models\TypesDocument;
use Illuminate\Support\Facades\DB;

class TypesDocumentController extends Controller
{

    public function getAllTypesDocument()
    {
        return response()->json(TypesDocument::all());
    }
}

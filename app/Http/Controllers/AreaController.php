<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Models\Area;

class AreaController extends Controller
{
    public function getAllAreas($id_company = null)
    {

        $userAuth = Auth::user();
        $areas = Area::whereNull('id_company')->orWhere('id_company', $id_company ?? $userAuth->id_company)->get();

        return response()->json($areas);
    }

    public function createArea(Request $request)
    {

        try {

            $userAuth = Auth::user();

            $area = Area::create([
                'name' => $request->name,
                'id_company' => $userAuth->id_company
            ]);

            return response()->json(['status' => true, 'message' => 'Area creada exitosamente', 'record' => $area]);
        } catch (\Throwable $th) {
            if ($th->getMessage() !== null) {
                return response()->json(['status' => false, 'message' => $th->getMessage() . " en la línea " . $th->getLine()]);
            } else {
                return response()->json(['status' => false, 'message' => $th]);
            }
        }
    }

    public function updateArea(Request $request)
    {
        try {

            Area::where('id_area', $request->id_area)->update([
                'name' => $request->name,
            ]);

            return response()->json(['status' => true, 'message' => 'Area actualizada exitosamente']);
        } catch (\Throwable $th) {
            if ($th->getMessage() !== null) {
                return response()->json(['status' => false, 'message' => $th->getMessage() . " en la línea " . $th->getLine()]);
            } else {
                return response()->json(['status' => false, 'message' => $th]);
            }
        }
    }
}

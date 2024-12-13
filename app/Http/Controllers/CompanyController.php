<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Document;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;


class CompanyController extends Controller
{
    public function getAllCompanies()
    {
        $companies = Company::where('id_company', '!=', '1')->orderBy('created_at', 'DESC')->get();
        return response()->json($companies);
    }

    public function createCompany(Request $request)
    {
        try {
            $company = Company::where('nit', $request->nit)->first();

            if ($company) {
                return response()->json(['status' => false, 'message' => 'El nit ya se encuentra registrado']);
            }

            Company::create($request->all());
            return response()->json(['status' => true, 'message' => 'Registro exitoso']);
        } catch (\Throwable $th) {
            if ($th->getMessage() !== null) {
                return response()->json(['status' => false, 'message' => $th->getMessage() . " en la línea " . $th->getLine()]);
            } else {
                return response()->json(['status' => false, 'message' => $th]);
            }
        }
    }

    public function updateCompany(Request $request)
    {
        try {
            Company::where('id_company', $request->id_company)->update([
                'name' => $request->name,
                'type' => $request->type,
                'address' => $request->address,
                'city' => $request->city,
                'country' => $request->country,
                'phone' => $request->phone,
            ]);
            return response()->json(['status' => true, 'message' => 'Actualización exitosa']);
        } catch (\Throwable $th) {
            if ($th->getMessage() !== null) {
                return response()->json(['status' => false, 'message' => $th->getMessage() . " en la línea " . $th->getLine()]);
            } else {
                return response()->json(['status' => false, 'message' => $th]);
            }
        }
    }

    public function deleteCompany($id_company)
    {
        DB::beginTransaction();
        try {
            $documents = Document::select('documents.path')
                ->join('customers AS c', 'documents.identification', 'c.identification')
                ->where('c.id_company', $id_company)
                ->get();

            for ($i = 0; $i < count($documents); $i++) {
                $fullPath = storage_path('app/public/' . $documents[$i]->path);
                if (file_exists($fullPath)) {
                    unlink($fullPath); // Elimina el archivo
                }
            }

            Company::where('id_company', $id_company)->delete();
            DB::commit();
            return response()->json(['status' => true, 'message' => 'Eliminación exitosa']);
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
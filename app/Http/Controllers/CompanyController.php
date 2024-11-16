<?php

namespace App\Http\Controllers;

use App\Models\Company;
use Illuminate\Http\Request;
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
}

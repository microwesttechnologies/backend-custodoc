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

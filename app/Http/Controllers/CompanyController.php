<?php

namespace App\Http\Controllers;

use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;


class CompanyController extends Controller
{
    public function getAllCompanies()
    {
        return response()->json(Company::orderBy('created_at', 'DESC')->get());
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

    public function store(Request $request)
    {
        $companies = $request->all();
        $errors = [];

        if (isset($companies[0])) {
            // Si es un array de compañías
            foreach ($companies as $index => $company) {
                try {
                    $this->validateCompany($company);
                    Company::create($company);
                } catch (\Illuminate\Validation\ValidationException $e) {
                    // Guardar errores específicos de esta compañía
                    $errors['company_' . $index] = $e->errors();
                } catch (\Illuminate\Database\QueryException $e) {
                    $errors['company_' . $index] = ['error' => $e->getMessage()];
                }
            }
        } else {
            try {
                $this->validateCompany($companies);
                Company::create($companies);
            } catch (\Illuminate\Validation\ValidationException $e) {
                return response()->json(['error' => $e->errors()], 422);
            } catch (\Illuminate\Database\QueryException $e) {
                return response()->json(['error' => $e->getMessage()], 422);
            }
        }

        if (!empty($errors)) {
            return response()->json(['errors' => $errors], 422);
        }

        return response()->json(['message' => 'Compañías creadas correctamente'], 201);
    }

    private function validateCompany($company)
    {
        return Validator::make($company, [
            'nameCompany' => 'required|string|max:255',
            'typeCompany' => 'required|string',
            'addressCompany' => 'required|string',
            'cityCompany' => 'required|string',
            'countryCompany' => 'required|string',
        ])->validate();
    }

    public function show($id)
    {
        return Company::findOrFail($id);
    }

    public function update(Request $request, $id)
    {
        $company = Company::findOrFail($id);
        $company->update($request->all());
        return $company;
    }

    public function destroy($id)
    {
        Company::destroy($id);
        return response()->noContent();
    }
}

<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Models\Document;
use App\Models\Company;
use App\Models\Routes;
use App\Models\Roles;

class CompanyController extends Controller
{
    public function getAllCompanies(Request $request)
    {

        $companiesQuery = Company::orderBy('created_at', 'DESC');

        if ($request->query('type_company')) {
            $companiesQuery->where('type', $request->query('type_company'));
        }

        return response()->json($companiesQuery->get());
    }

    public function createCompany(Request $request)
    {
        DB::beginTransaction();
        try {
            $company = Company::where('nit', $request->nit)->first();

            if ($company) {
                return response()->json(['status' => false, 'message' => 'El nit ya se encuentra registrado']);
            }

            $company = Company::create($request->all());

            if ($company->type !== 'IPS') {

                $rol = Roles::create(['name' => 'Administrador', 'id_company' => $company->id_company, 'isDefault' => 1]);

                $routes = Routes::whereNotIn('code', ['RANKING', 'COMPANY', 'CUSTOMER'])->get();

                foreach ($routes as $route) {
                    DB::table('roles_routes')->insert([
                        'id_rol' => $rol->id_rol,
                        'id_route' => $route->id_route,
                        'CREATE' => $route->CREATE,
                        'UPDATE' => $route->UPDATE,
                        'DELETE' => $route->DELETE,
                        'EXPORT' => $route->EXPORT,
                    ]);
                }
            }

            DB::commit();
            return response()->json(['status' => true, 'message' => 'Compañía creada exitosamente']);
        } catch (\Throwable $th) {
            DB::rollBack();
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
            return response()->json(['status' => true, 'message' => 'Compañía actualizada exitosamente']);
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
            return response()->json(['status' => true, 'message' => 'Compañía eliminada exitosamente']);
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

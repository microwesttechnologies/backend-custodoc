<?php

namespace App\Http\Controllers;

use App\Models\Company;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Models\Roles;
use App\Models\Routes;

class RolesController extends Controller
{
    public function getRolesByCompany($id_company = null)
    {
        $company = Company::where('id_company', $id_company)->first();

        $rolesQuery = Roles::where([['id_company', isset($company) && $company->type === 'IPS' ? null : $id_company], ['id_rol', '!=', '1']]);

        $rolesQuery->where('id_rol', '!=', '4');

        return response()->json($rolesQuery->get());
    }

    public function getRolesAndPermissions()
    {
        $userAuth = Auth::user();

        $rolesAndPermissions = [];

        $roles = Roles::where('roles.id_company', $userAuth->id_company)->get();
        $index = 0;

        foreach ($roles as $rol) {
            $rolesAndPermissions[$index]['isDefault'] = $rol->isDefault === 1;
            $rolesAndPermissions[$index]['created_at'] = $rol->created_at;
            $rolesAndPermissions[$index]['id_rol'] = $rol->id_rol;
            $rolesAndPermissions[$index]['name'] = $rol->name;

            $permissionsAndModuleByRole = Routes::select(
                DB::raw('IF(rr.id_rol,1,0) AS ACCESS'),
                'routes.id_route',
                'routes.name',
                'routes.code',
                'rr.CREATE',
                'rr.UPDATE',
                'rr.DELETE',
                'rr.EXPORT',
            )
                ->leftJoin('roles_routes AS rr', function ($leftJoin) use ($rol) {
                    $leftJoin->on('routes.id_route', 'rr.id_route')->where('rr.id_rol', $rol->id_rol);
                })->where([['routes.code', '!=', 'COMPANY'], ['routes.code', '!=', 'RANKING']])
                ->get();

            $rolesAndPermissions[$index]['modules'] = $permissionsAndModuleByRole;
            $index++;
        }

        return response()->json($rolesAndPermissions);
    }

    public function createRol(Request $request)
    {

        DB::beginTransaction();
        try {

            $userAuth = Auth::user();
            $modules = array_filter($request->modules, function ($module) {
                return $module['permissions']['ACCESS'] === 1;
            });
            $name = $request->name;

            $rol = Roles::create([
                'id_company' => $userAuth->id_company,
                'name' => $name,
            ]);

            foreach ($modules as $module) {
                $moduleInsert = array_merge([
                    'id_route' => $module['id_route'],
                    'id_rol' => $rol->id_rol
                ], $module['permissions']);

                unset($moduleInsert['ACCESS']);

                DB::table('roles_routes')->insert($moduleInsert);
            }


            DB::commit();
            return response()->json(['status' => true, 'message' => 'Rol creado exitosamente']);
        } catch (\Throwable $th) {
            DB::rollBack();
            if ($th->getMessage() !== null) {
                return response()->json(['status' => false, 'message' => $th->getMessage() . " en la línea " . $th->getLine()]);
            } else {
                return response()->json(['status' => false, 'message' => $th]);
            }
        }
    }

    public function updateRol(Request $request)
    {
        DB::beginTransaction();
        try {

            $modules = $request->modules;
            $id_rol = $request->id_rol;
            $name = $request->name;

            Roles::where('id_rol', $id_rol)->update(['name' => $name]);

            foreach ($modules as $module) {
                $moduleFound = DB::table('roles_routes')->where([
                    ['id_route', $module['id_route']],
                    ['id_rol', $id_rol]
                ])->first();

                if ($module['permissions']['ACCESS'] && !$moduleFound) {
                    $moduleInsert = array_merge([
                        'id_route' => $module['id_route'],
                        'id_rol' => $id_rol,
                    ], $module['permissions']);
                    unset($moduleInsert['ACCESS']);
                    DB::table('roles_routes')->insert($moduleInsert);
                } else if ($module['permissions']['ACCESS'] && $moduleFound) {
                    unset($module['permissions']['ACCESS']);
                    if (count($module['permissions'])) {
                        DB::table('roles_routes')->where([
                            ['id_route', $module['id_route']],
                            ['id_rol', $id_rol]
                        ])->update($module['permissions']);
                    }
                } else if (!$module['permissions']['ACCESS'] && $moduleFound) {
                    DB::table('roles_routes')->where([
                        ['id_route', $module['id_route']],
                        ['id_rol', $id_rol]
                    ])->delete();
                }
            }


            DB::commit();
            return response()->json(['status' => true, 'message' => 'Rol actualizado exitosamente']);
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

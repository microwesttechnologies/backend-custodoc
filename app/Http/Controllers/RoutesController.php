<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use App\Models\Company;
use App\Models\Routes;

class RoutesController extends Controller
{
    public function getRoutesByRole()
    {
        // Obtener el rol del usuario autenticado
        $userAuth = Auth::user();

        $company = Company::where('id_company', $userAuth->id_company)->first();

        // Obtener todas las rutas relacionadas
        $routesQuery = Routes::select('routes.name', 'routes.code', 'routes.path', 'routes.order', 'routes.icon', 'routes.id_route', 'rr.CREATE', 'rr.UPDATE', 'rr.DELETE', 'rr.EXPORT')
            ->join('roles_routes AS rr', 'routes.id_route', 'rr.id_route')
            ->where('rr.id_rol', $userAuth->id_rol)
            ->orderBy('routes.order', 'ASC');

        if ($company && $company->type === 'IPS') {
            $routesQuery->where([
                ['routes.code', '!=', 'TRASH'],
                ['routes.code', '!=', 'ROLES']
            ]);
        }

        return response()->json($routesQuery->get());
    }

    public function getRoutesAndPermissions()
    {
        $routesAndPermissions = Routes::whereNotIn('code', ['RANKING', 'COMPANY', 'CUSTOMER'])->get();

        return response()->json($routesAndPermissions);
    }
}

<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\Routes;

class RoutesController extends Controller
{
    public function getRoutesByRole()
    {
        // Obtener el rol del usuario autenticado
        $userRoleId = Auth::user()->id_rol; // Cambia esto según tu relación de usuario y roles

        // Obtener los IDs de las rutas permitidas para este rol
        $allowedRouteIds = DB::table('roles_routes')
            ->where('id_rol', $userRoleId)
            ->pluck('id_route')
            ->toArray();

        // Obtener todas las rutas relacionadas
        $routes = Routes::whereIn('id_route', $allowedRouteIds)->get();

        // Construir la jerarquía de rutas
        $menu = $this->buildRoutesHierarchy($routes);

        return response()->json(['menu' => $menu, 'allowedRouteIds' => $allowedRouteIds]);
    }

    private function buildRoutesHierarchy($routes)
    {
        $menu = [];

        // Obtener las rutas principales (sin parent)
        $parents = Routes::whereNull('parent')->get();

        foreach ($parents as $parent) {
            // Filtrar los hijos de cada ruta principal
            $children = $routes->filter(function ($route) use ($parent) {
                return $route->parent === $parent->id_route;
            });

            if (count($children) > 0) {


                // Estructura del padre
                $routeData = [
                    'label' => $parent->name,
                    'items' => [],
                ];

                // Agregar hijos si existen
                foreach ($children as $child) {
                    $routeData['items'][] = [
                        'id' => $child->id_route,
                        'label' => $child->name,
                        'icon' => $child->icon,
                        'path' => $child->path,
                    ];
                }

                $menu[] = $routeData;
            }
        }

        return $menu;
    }
}

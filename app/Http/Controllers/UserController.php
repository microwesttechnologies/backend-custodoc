<?php

namespace App\Http\Controllers;

use App\Models\Area;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use App\Models\TypesDocument;
use Illuminate\Http\Request;
use App\Models\Company;
use App\Models\Roles;
use App\Models\User;


class UserController extends Controller
{
    public function getUserProfile()
    {
        $userAuth = Auth::user();

        $company = Company::where('id_company', $userAuth->id_company)->first();
        $typeDocument = TypesDocument::where('id_document', $userAuth->id_document)->first();
        $rol = Roles::where('id_rol', $userAuth->id_rol)->first();
        $area = Area::where('id_area', $userAuth->id_area)->first();

        $userAuth['name_type_document'] = $typeDocument->name ?? null;
        $userAuth['name_company'] = $company->name ?? null;
        $userAuth['type_company'] = $company->type ?? null;
        $userAuth['name_rol'] = $rol->name ?? null;
        if ($area) {
            $userAuth['name_area'] = $area->name;
        }

        return response()->json($userAuth);
    }

    public function getAllUsers($id_company = null)
    {

        $userAuth = Auth::user();

        $queryUser = User::select([
            'users.*',
            'c.name AS name_company',
            'td.name AS name_type_document',
            'r.name AS name_rol',
            'c.type AS type_company',
            'a.name AS name_area'
        ])->leftJoin('companies AS c', 'users.id_company', 'c.id_company')
            ->join('types_document AS td', 'users.id_document', 'td.id_document')
            ->join('roles AS r', 'users.id_rol', 'r.id_rol')
            ->leftJoin('areas AS a', 'users.id_area', 'a.id_area')
            ->where('users.id_rol', '!=', 1)
            ->orderBy('created_at', 'DESC');

        if ($userAuth->id_rol !== 1 || $id_company) {
            $queryUser->where('users.id_company', $id_company ?? $userAuth->id_company);
        }

        return response()->json($queryUser->get());
    }

    public function createUser(Request $request)
    {
        try {

            $user  = User::where('identification', $request->identification)->orWhere('email', $request->email)->first();
            $userAuth = Auth::user();

            if ($user) {
                return response()->json(['status' => false, 'message' => ($user->email === $request->email ? 'El email' : 'La identification') . ' ya se encuentra registrado']);
            }

            $data = $request->all();
            $data['password'] = Hash::make($request->password);

            if ($userAuth->id_rol !== 1) {
                $data['id_company'] = $userAuth->id_company;
            }

            if ($request->id_area) {
                $data['id_area'] = $request->id_area;
            }

            if ($data['id_rol'] === 4) {
                $data['id_area'] = 1;
            }

            if ($data['id_company'] === 'IPS' || $data['id_company'] === 'Otras') {
                unset($data['id_company']);
            }

            User::create($data);
            return response()->json(['status' => true, 'message' => 'Registro exitoso']);
        } catch (\Throwable $th) {
            if ($th->getMessage() !== null) {
                return response()->json(['status' => false, 'message' => $th->getMessage() . " en la línea " . $th->getLine()]);
            } else {
                return response()->json(['status' => false, 'message' => $th]);
            }
        }
    }

    public function updateUser(Request $request)
    {
        try {

            $userAuth = Auth::user();

            $user  = User::where([
                ['email', $request->email],
                ['identification', '!=', $request->identification]
            ])->first();

            if ($user) {
                return response()->json(['status' => false, 'message' => 'El email ya se encuentra registrado']);
            }

            $dataToUpdate = [
                'id_rol' => $request->id_rol,
                'email' => $request->email,
                'phone' => $request->phone,
                'name' => $request->name,
            ];

            if ($userAuth->id_rol === 1) {
                $dataToUpdate['id_company'] = $request->id_company;
            }

            if ($request->id_area) {
                $dataToUpdate['id_area'] = $request->id_area;
            }

            User::where('identification', $request->identification)->update($dataToUpdate);

            return response()->json(['status' => true, 'message' => 'Registro exitoso']);
        } catch (\Throwable $th) {
            if ($th->getMessage() !== null) {
                return response()->json(['status' => false, 'message' => $th->getMessage() . " en la línea " . $th->getLine()]);
            } else {
                return response()->json(['status' => false, 'message' => $th]);
            }
        }
    }

    public function getAllRankingUsers(Request $request)
    {

        $queryRankingUser = User::select([
            'users.identification',
            'c.name AS name_company',
            'users.name AS name_user',
            DB::raw('COUNT(d.id_history) AS total_documents')
        ])
            ->Join('documents AS d', 'users.identification', 'd.user_identification')
            ->Join('companies AS c', 'c.id_company', 'd.id_company')
            ->where('users.id_rol', 4)
            ->groupBy('users.name', 'users.identification', 'c.name');

        // Obtener el valor del queryParam "rangeDates"
        $rangeDates = $request->query('rangeDates');

        if ($rangeDates) {
            $rangeDates = explode(',', $rangeDates);
            $queryRankingUser->whereBetween('d.created_at', [$rangeDates[0], $rangeDates[1]]);
        }

        return response()->json($queryRankingUser->get());
    }

    public function deleteUser($identification)
    {
        try {
            User::where('identification', $identification)->delete();

            return response()->json(['status' => true, 'message' => 'Registro eliminado exitosamente']);
        } catch (\Throwable $th) {
            if ($th->getMessage() !== null) {
                return response()->json(['status' => false, 'message' => $th->getMessage() . " en la línea " . $th->getLine()]);
            } else {
                return response()->json(['status' => false, 'message' => $th]);
            }
        }
    }

    public function updatePassword(Request $request)
    {
        try {
            $userAuth = Auth::user();

            $user = User::where('email', $userAuth->email)->first();

            // Verificar que la contraseña actual coincida
            if (!Hash::check($request->currentPassword, $user->password)) {
                return response()->json([
                    'message' => 'La contraseña actual es incorrecta.',
                    'code' => 'INCORRECT_CURRENT_PASSWORD',
                    'status' => false
                ]);
            }

            // Restablecer la contraseña
            $user->password = Hash::make($request->password);
            $user->save();

            return response()->json(['status' => true, 'message' => 'Contraseña actualizada correctamente.']);
        } catch (\Throwable $th) {
            if ($th->getMessage() !== null) {
                return response()->json(['status' => false, 'message' => $th->getMessage() . " en la línea " . $th->getLine()]);
            } else {
                return response()->json(['status' => false, 'message' => $th]);
            }
        }
    }
}

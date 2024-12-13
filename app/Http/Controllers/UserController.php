<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;


class UserController extends Controller
{
    public function getAllUsers()
    {

        $userAuth = Auth::user();

        $queryUser = User::select([
            'users.*',
            'c.name AS name_company',
            'td.name AS name_type_document',
            'r.name AS name_rol'
        ])->leftJoin('companies AS c', 'users.id_company', 'c.id_company')
            ->join('types_document AS td', 'users.id_document', 'td.id_document')
            ->join('roles AS r', 'users.id_rol', 'r.id_rol')
            ->where('users.id_rol', '!=', 1)
            ->orderBy('created_at', 'DESC');


        if ($userAuth->id_rol !== 1) {
            $queryUser->where('users.id_company', $userAuth->id_company);
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

            if ($userAuth->id_rol === 2) {
                $data['id_company'] = $userAuth->id_company;
                /** El adminsitrador de las empresas solo pueden crear empleados, el código es 3 */
                $data['id_rol'] = 3;
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

            $user  = User::where([
                ['email', $request->email],
                ['identification', '!=', $request->identification]
            ])->first();

            if ($user) {
                return response()->json(['status' => false, 'message' => 'El email ya se encuentra registrado']);
            }

            User::where('identification', $request->identification)->update([
                'id_company' => $request->id_company,
                'id_rol' => $request->id_rol,
                'email' => $request->email,
                'phone' => $request->phone,
                'name' => $request->name,
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

    public function getAllRankingUsers()
    {

        $queryRankingUser = User::select([
            'users.identification',
            'c.name AS name_company',
            'users.name AS name_user',
            DB::raw('COUNT(d.id_history) AS total_documents')
        ])
            ->leftJoin('companies AS c', 'users.id_company', 'c.id_company')
            ->Join('documents AS d', 'users.identification', 'd.user_identification')
            ->groupBy('users.identification', 'c.name', 'users.name');

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
}
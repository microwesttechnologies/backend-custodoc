<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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
            'td.name AS name_type_document'
        ])->join('companies AS c', 'users.id_company', 'c.id_company')
            ->join('types_document AS td', 'users.id_document', 'td.id_document')
            ->join('roles AS r', 'users.id_rol', 'r.id_rol')
            ->where('identification', '!=', $userAuth->identification)
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

            if ($userAuth->id_rol !== 1) {
                $data['id_company'] = $userAuth->id_company;
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

    private function validateUser($user)
    {
        return Validator::make($user, [
            'nameUser' => 'required|string|max:255',
            'emailUser' => 'required|string|email|max:255|unique:users',
            'passUser' => 'required|string|min:8',
            'role' => 'required|string',
            'state' => 'required|integer',
            'id_company' => 'required|integer',
        ])->validate();
    }
}

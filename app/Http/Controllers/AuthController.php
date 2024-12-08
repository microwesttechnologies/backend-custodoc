<?php

namespace App\Http\Controllers;

use App\Models\Company;
use Tymon\JWTAuth\Exceptions\JWTException;
use Illuminate\Support\Facades\Auth;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Http\Request;

class AuthController extends Controller
{

    /**
     * Login and return a token.
     */
    public function login(Request $request)
    {
        // Validar los datos
        $credentials = $request->only('email', 'password');

        try {
            // Intentar autenticar al usuario y generar el token
            if (!$token = JWTAuth::attempt($credentials)) {
                return response()->json(['message' => 'Credenciales incorrectas', 'status' => false]);
            }

            // Si la autenticación es exitosa, obtenemos el usuario autenticado
            $user = Auth::user();
            $company = Company::where('id_company', $user->id_company)->first();

            $token = JWTAuth::customClaims([
                'identification' => $user->identification,
                'name' => $user->name,
                'email' => $user->email,
                'id_rol' => $user->id_rol,
                'state' => $user->state,
                'id_company' => $user->id_company,
                'name_company' => $company->name ?? null,
                'type_company' => $company->typ ?? null
            ])->fromUser($user);
        } catch (JWTException $e) {
            return response()->json(['message' => 'Error al generar el token', 'status' => false], 500);
        }

        // Retornar el token
        return response()->json([
            'token' => $token,
            'status' => true
        ], 200);
    }

    /**
     * Logout the user by invalidating the token.
     */
    public function logout()
    {
        // Invalida el token actual
        try {
            JWTAuth::invalidate(JWTAuth::getToken());
            return response()->json(['message' => 'User successfully logged out', 'status' => true], 200);
        } catch (JWTException $e) {
            return response()->json(['message' => 'Failed to logout, please try again', 'status' => false], 500);
        }
    }
}

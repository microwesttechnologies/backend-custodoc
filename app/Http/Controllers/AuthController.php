<?php

namespace App\Http\Controllers;

use App\Mail\PasswordResetMail;
use App\Models\Company;
use App\Models\User;
use Tymon\JWTAuth\Exceptions\JWTException;
use Illuminate\Support\Facades\Auth;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;

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
            $userAuth = Auth::user();

            $token = JWTAuth::customClaims([
                'identification' => $userAuth->identification,
            ])->fromUser($userAuth);
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

    public function sendLinkResetPassword(Request $request)
    {
        try {

            $user = User::where('email', $request->email)->first();

            if (!$user) {
                return response()->json(['message' => 'El correo electrónico no exite', 'status' => false]);
            }

            // Generar un token JWT con un payload específico
            $token = JWTAuth::claims([
                'email' => $user->email,
                'type' => 'password_reset',
                'exp' => now()->addMinutes(30)->timestamp, // Expiración en 30 minutos
            ])->fromUser($user);

            Mail::to($request->email)->send(new PasswordResetMail($token));

            return response()->json(['message' => 'Correo enviado exitosamente', 'status' => true]);
        } catch (\Throwable $th) {
            if ($th->getMessage() !== null) {
                return response()->json(['status' => false, 'message' => $th->getMessage() . " en la línea " . $th->getLine()]);
            } else {
                return response()->json(['status' => false, 'message' => $th]);
            }
        }
    }

    public function resetPassword(Request $request)
    {
        try {
            // Decodificar el token
            $payload = JWTAuth::parseToken()->getPayload();

            // Validar que sea un token de tipo password_reset
            if ($payload['type'] !== 'password_reset') {
                return response()->json(['message' => 'El token enviado no es valido', 'status' => false]);
            }
            // Validar que el token no haya expirado
            $email = $payload['email'];

            $user = User::where('email', $email)->first();

            if (!$user) {
                return response()->json(['message' => 'El correo electrónico no exite', 'status' => false]);
            }

            // Restablecer la contraseña
            $user->password = Hash::make($request->password);
            $user->save();

            // Inhabilitar el token después de un solo uso
            JWTAuth::invalidate($request->token);

            return response()->json(['message' => 'Contraseña restablecida correctamente.', 'status' => true]);
        } catch (TokenExpiredException $e) {
            return response()->json(['error' => 'El token ha expirado.'], 400);
        } catch (TokenInvalidException $e) {
            return response()->json(['error' => 'Token inválido.'], 400);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error al procesar la solicitud.'], 500);
        }
    }

    public function validateIfTokenIsValid()
    {
        try {
            // Obtener el payload del token
            $payload = JWTAuth::parseToken()->getPayload();

            // Validar el tipo de token
            if ($payload['type'] !== 'password_reset') {
                return response()->json(['message' => 'Token inválido para recuperación de contraseña'], 400);
            }

            // Obtener datos del token (por ejemplo, email)
            $email = $payload['email'];

            // Puedes realizar más validaciones aquí, como verificar si el usuario existe
            return response()->json(['message' => 'Token válido', 'email' => $email], 200);
        } catch (TokenExpiredException $e) {
            return response()->json(['message' => 'El token ha expirado.'], 400);
        } catch (TokenInvalidException $e) {
            return response()->json(['message' => 'El token no es válido.'], 400);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error al procesar el token.'], 500);
        }
    }
}

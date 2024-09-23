<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;


class UserController extends Controller
{
    public function index()
    {
        return User::all();
    }

    public function store(Request $request)
    {
        $users = $request->all();
        $errors = [];
    
        if (isset($users[0])) {
            foreach ($users as $index => $user) {
                try {
                    $this->validateUser($user);
                    User::create($user);
                } catch (\Illuminate\Validation\ValidationException $e) {
                    $errors['user_' . $index] = $e->errors();
                } catch (\Illuminate\Database\QueryException $e) {
                    $errors['user_' . $index] = ['error' => $e->getMessage()];
                }
            }
        } else {
            try {
                $this->validateUser($users);
                User::create($users);
            } catch (\Illuminate\Validation\ValidationException $e) {
                return response()->json(['error' => $e->errors()], 422);
            } catch (\Illuminate\Database\QueryException $e) {
                return response()->json(['error' => $e->getMessage()], 422);
            }
        }
    
        if (!empty($errors)) {
            return response()->json(['errors' => $errors], 422);
        }
    
        return response()->json(['message' => 'Usuarios creados correctamente'], 201);
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
    
    
    
    

    public function show($id)
    {
        return User::findOrFail($id);
    }

    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);
        $user->update($request->all());
        return $user;
    }

    public function destroy($id)
    {
        User::destroy($id);
        return response()->noContent();
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\HistoryUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class HistoryUserController extends Controller
{
    public function index()
    {
        return HistoryUser::with('user')->get();
    }

    public function store(Request $request)
    {
        $historyRecords = $request->all();
        $errors = [];
    
        if (isset($historyRecords[0])) {
            // Si es un array de registros de historial
            foreach ($historyRecords as $index => $record) {
                try {
                    $this->validateRecord($record);
                    HistoryUser::create($record);
                } catch (\Illuminate\Validation\ValidationException $e) {
                    // Guardar errores específicos de este registro
                    $errors['record_' . $index] = $e->errors();
                } catch (\Illuminate\Database\QueryException $e) {
                    $errors['record_' . $index] = ['error' => $e->getMessage()];
                }
            }
        } else {
            // Si es solo un registro de historial
            try {
                $this->validateRecord($historyRecords);
                HistoryUser::create($historyRecords);
            } catch (\Illuminate\Validation\ValidationException $e) {
                return response()->json(['error' => $e->errors()], 422);
            } catch (\Illuminate\Database\QueryException $e) {
                return response()->json(['error' => $e->getMessage()], 422);
            }
        }
    
        // Si hubo errores, devolverlos
        if (!empty($errors)) {
            return response()->json(['errors' => $errors], 422);
        }
    
        return response()->json(['message' => 'Registros de historial creados correctamente'], 201);
    }
    
    private function validateRecord($record)
    {
        return Validator::make($record, [
            'id_user' => 'required|integer|exists:users,id',
            'id_company' => 'required|integer|exists:companies,id',
            'nameDocument' => 'required|string',
            'routeDocument' => 'required|string',
            'description' => 'nullable|string'
        ])->validate();
    }
    

    public function show($id)
    {
        return HistoryUser::findOrFail($id);
    }

    public function update(Request $request, $id)
    {
        $historyUser = HistoryUser::findOrFail($id);
        $historyUser->update($request->all());
        return $historyUser;
    }

    public function destroy($id)
    {
        HistoryUser::destroy($id);
        return response()->noContent();
    }
}

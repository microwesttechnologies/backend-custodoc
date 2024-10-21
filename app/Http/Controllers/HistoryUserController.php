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

        // Validar que se reciba un archivo
        if ($request->hasFile('document')) {
            $file = $request->file('document');
            $path = $file->store('documents');

            if (isset($historyRecords[0])) {
                foreach ($historyRecords as $index => $record) {
                    try {
                        $this->validateRecord($record);
                        $record['routeDocument'] = $path;
                        HistoryUser::create($record);
                    } catch (\Illuminate\Validation\ValidationException $e) {
                        $errors['record_' . $index] = $e->errors();
                    } catch (\Illuminate\Database\QueryException $e) {
                        $errors['record_' . $index] = ['error' => $e->getMessage()];
                    }
                }
            } else {
                try {
                    $this->validateRecord($historyRecords);
                    $historyRecords['routeDocument'] = $path;
                    HistoryUser::create($historyRecords);
                } catch (\Illuminate\Validation\ValidationException $e) {
                    return response()->json(['error' => $e->errors()], 422);
                } catch (\Illuminate\Database\QueryException $e) {
                    return response()->json(['error' => $e->getMessage()], 422);
                }
            }
        } else {
            return response()->json(['error' => 'No se ha recibido ningún archivo'], 422);
        }

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

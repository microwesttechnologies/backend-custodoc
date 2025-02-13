<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Document;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CustomerController extends Controller
{
    public function getAllCustomers($id_company = null)
    {
        $userAuth = Auth::user();

        $queryCustomers = Customer::select([
            'customers.*',
            'co.name AS name_company',
            'td.name AS name_type_document'
        ])->join('companies AS co', 'customers.id_company', 'co.id_company')
            ->join('types_document AS td', 'customers.id_document', 'td.id_document')
            ->orderBy('created_at', 'DESC');

        if (($userAuth->id_rol !== 1 && $userAuth->id_rol !== 4) || $id_company) {
            $queryCustomers->where('customers.id_company', $id_company ?? $userAuth->id_company);
        }

        if ($userAuth->id_rol === 4) {
            $queryCustomers->where('co.type', 'IPS');
        }

        return response()->json($queryCustomers->get());
    }

    public function createCustomer(Request $request)
    {
        try {

            $customer  = Customer::where('identification', $request->identification)->orWhere('email', $request->email)->first();
            $userAuth = Auth::user();

            if ($customer) {
                return response()->json(['status' => false, 'message' => ($customer->email === $request->email ? 'El email' : 'La identification') . ' ya se encuentra registrado']);
            }

            $data = $request->all();

            if ($userAuth->id_rol !== 1 && $userAuth->id_rol !== 4) {
                $data['id_company'] = $userAuth->id_company;
            }

            Customer::create($data);
            return response()->json(['status' => true, 'message' => 'Registro exitoso']);
        } catch (\Throwable $th) {
            if ($th->getMessage() !== null) {
                return response()->json(['status' => false, 'message' => $th->getMessage() . " en la línea " . $th->getLine()]);
            } else {
                return response()->json(['status' => false, 'message' => $th]);
            }
        }
    }

    public function updateCustomer(Request $request)
    {
        try {

            $userAuth = Auth::user();

            $customer  = Customer::where([
                ['email', $request->email],
                ['identification', '!=', $request->identification]
            ])->first();

            if ($customer) {
                return response()->json(['status' => false, 'message' => 'El email ya se encuentra registrado']);
            }

            $dataToUpdate = [
                'email' => $request->email,
                'phone' => $request->phone,
                'name' => $request->name,
            ];

            if ($userAuth->id_rol === 1) {
                $dataToUpdate['id_company'] = $request->id_company;
            }

            Customer::where('identification', $request->identification)->update($dataToUpdate);
            return response()->json(['status' => true, 'message' => 'Registro exitoso']);
        } catch (\Throwable $th) {
            if ($th->getMessage() !== null) {
                return response()->json(['status' => false, 'message' => $th->getMessage() . " en la línea " . $th->getLine()]);
            } else {
                return response()->json(['status' => false, 'message' => $th]);
            }
        }
    }

    public function deleteCustomer($identification)
    {
        DB::beginTransaction();
        try {
            $documents = Document::select('path')
                ->where('identification', $identification)
                ->get();

            for ($i = 0; $i < count($documents); $i++) {
                $fullPath = storage_path('app/public/' . $documents[$i]->path);
                if (file_exists($fullPath)) {
                    unlink($fullPath); // Elimina el archivo
                }
            }

            Customer::where('identification', $identification)->delete();
            DB::commit();
            return response()->json(['status' => true, 'message' => 'Eliminación exitosa']);
        } catch (\Throwable $th) {
            DB::rollBack();
            if ($th->getMessage() !== null) {
                return response()->json(['status' => false, 'message' => $th->getMessage() . " en la línea " . $th->getLine()]);
            } else {
                return response()->json(['status' => false, 'message' => $th]);
            }
        }
    }
}

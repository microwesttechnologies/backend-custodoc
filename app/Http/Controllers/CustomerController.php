<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CustomerController extends Controller
{
    public function getAllCustomers()
    {
        $queryCustomers = Customer::select([
            'customers.*',
            'co.name AS name_company',
            'td.name AS name_type_document'
        ])->join('companies AS co', 'customers.id_company', 'co.id_company')
            ->join('types_document AS td', 'customers.id_document', 'td.id_document')
            ->orderBy('created_at', 'DESC');

        $userAuth = Auth::user();

        if ($userAuth->id_rol !== 1) {
            $queryCustomers->where('customers.id_company', $userAuth->id_company);
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

            if ($userAuth->id_rol !== 1) {
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
}

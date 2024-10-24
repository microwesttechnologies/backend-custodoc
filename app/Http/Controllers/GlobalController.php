<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Auth;
use App\Models\Customer;
use App\Models\Company;
use App\Models\Document;
use App\Models\User;

class GlobalController extends Controller
{
    public function getDetailCompany()
    {
        $user = Auth::user();
        $detail = [];

        if ($user->id_rol === 1) {
            $detail['company'] = ['label' => 'Compañías', 'amount' => Company::count(), 'icon' => 'pi pi-building'];
        }

        $queryCustomers = Customer::query();
        $queryDocuments = Document::query();
        $queryUsers = User::query();

        if ($user->id_rol === 2) {
            $queryUsers->where('id_company', $user->id_company);
        }

        if ($user->id_rol === 2 || $user->id_rol === 3) {
            $queryCustomers->where('id_company', $user->id_company);
            $queryDocuments->join('customers AS c', 'documents.identification', 'c.identification')
                ->where('c.id_company', $user->id_company);
        }

        if ($user->id_rol !== 3) {
            $detail['users'] = ['label' => 'Empleados', 'amount' => $queryUsers->count(), 'icon' => 'pi pi-users'];
        }

        $detail['customers'] = ['label' => 'Clientes', 'amount' => $queryCustomers->count(), 'icon' => 'pi pi-user'];
        $detail['documents'] = ['label' => 'Documentos', 'amount' => $queryDocuments->count(), 'icon' => 'pi pi-file'];

        return $detail;
    }

    public function uploadFile($file, $dirPath)
    {
        // Definir la ruta completa donde se guardará el archivo (dentro de storage/app/public)
        $path = storage_path('app/public/' . $dirPath);

        // Verificar si la carpeta existe, si no, crearla con permisos
        if (!File::exists($path)) {
            File::makeDirectory($path, 0755, true); // Crear directorio con permisos 0755, true para crear subdirectorios
        }

        // Definir un nombre único para el archivo para evitar colisiones
        $filename = time() . '_' . $file->getClientOriginalName();

        // Mover el archivo a la carpeta creada
        $file->move($path, $filename);

        // Asignar permisos al archivo
        chmod($path . '/' . $filename, 0644); // Permisos de lectura y escritura para el propietario, solo lectura para otros

        // Generar la URL pública para poder acceder al archivo desde el navegador
        return Storage::url($dirPath . '/' . $filename);
    }
}

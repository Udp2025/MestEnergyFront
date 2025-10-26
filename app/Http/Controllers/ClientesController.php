<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\ClienteFile;
use App\Models\User;
use App\Models\Dato; // Modelo para la tabla 'datos'
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;
use App\Models\InfoFiscalUsuario;
use App\Models\PlanUsuario;
use Illuminate\Validation\ValidationException;


class ClientesController extends Controller
{
    public function index()
{
    $clientes = Cliente::with(['files', 'user', 'locaciones', 'areas', 'medidores', 'reportes'])
                      ->orderBy('nombre')
                      ->get();

    // Clientes cuyo estado_cliente == 2 (Onboarding)
    $onboardingClients = $clientes->filter(function($c) {
        return intval($c->estado_cliente) === 2;
    })->values();

    // Cargar catálogo de estados (id => estado)
    $catalogoEstados = DB::table('catalogo_estados_usuarios')->pluck('estado', 'id')->toArray();

    return view('clientes.index', compact('clientes', 'onboardingClients', 'catalogoEstados'));
}



    // Mostrar el formulario para crear un nuevo cliente
    public function create()
    {
        return view('clientes.create');
    }

    // Guardar un nuevo cliente
    /*
    public function store(Request $request){
        $request->validate([
            'nombre'         => 'required|string|max:255',
            'razon_social'   => 'required|string|max:255',
            'email'          => 'required|email|unique:clientes,email',
            'telefono'       => 'required|string|max:15',
            'calle'          => 'required|string|max:255',
            'numero'         => 'required|string|max:10',
            'colonia'        => 'required|string|max:255',
            'codigo_postal'  => 'required|string|max:5',
            'ciudad'         => 'required|string|max:255',
            'estado'         => 'required|string|max:255',
            'pais'           => 'required|string|max:255',
            'cambio_dolar'   => 'required|numeric|min:0',
        ]);

        $cliente = Cliente::create($request->all());
        return redirect()->route('clientes.index')->with('success', 'Cliente creado correctamente.');
    }*/

        /*
    public function store(Request $request)
    {
        // Reglas de validación (nota: chequea unicidad tanto en clientes como en users)
        $rules = [
            'nombre'         => 'required|string|max:255',
            'razon_social'   => 'required|string|max:255',
            'email'          => 'required|email|unique:clientes,email|unique:users,email',
            'telefono'       => 'required|string|max:15',
            'calle'          => 'required|string|max:255',
            'numero'         => 'required|string|max:10',
            'colonia'        => 'required|string|max:255',
            'codigo_postal'  => 'required|string|max:5',
            'ciudad'         => 'required|string|max:255',
            'estado'         => 'required|string|max:255',
            'pais'           => 'required|string|max:255',
            'cambio_dolar'   => 'required|numeric|min:0',
            'site'           => 'required|numeric|min:0',
        ];

        // Puedes personalizar mensajes si quieres:
        $messages = [
            'email.unique' => 'El correo ya está registrado en el sistema.',
        ];

        $validator = Validator::make($request->all(), $rules, $messages);

        // Si falla validación, vuelve al formulario con los errores + un warning
        if ($validator->fails()) {
            return redirect()->back()
                            ->withErrors($validator)
                            ->withInput()
                            ->with('warning', 'Hay errores en el formulario. Por favor corrige y vuelve a intentar.');
        }

        // Todo ok: crear Cliente + Usuario dentro de una transacción
        DB::beginTransaction();
        try {
            $cliente = Cliente::create($request->only([
                'nombre','razon_social','email','telefono',
                'calle','numero','colonia','codigo_postal',
                'ciudad','estado','pais','cambio_dolar', 'site'
            ]));

            // Generar contraseña temporal y crear usuario
            $tempPassword = Str::random(10);
            $user = User::create([
                'name'       => $request->nombre,
                'email'      => $request->email,
                'password'   => Hash::make($tempPassword),
                'cliente_id' => $cliente->id,
                // 'role' => 'cliente' // opcional: ajusta según tu esquema de roles
            ]);

            DB::commit();

            // Redirigir con éxito y opcionalmente mostrar contraseña temporal (si decides hacerlo)
            return redirect()->route('clientes.index')
                            ->with('success', 'Cliente y usuario creados correctamente.')
                            ->with('temp_password', $tempPassword);

        } catch (\Exception $e) {
            DB::rollBack();
            // Si por alguna razón se creó $cliente parcialmente, intenta eliminarlo para no dejar datos huérfanos
            if (isset($cliente) && $cliente->exists) {
                try { $cliente->delete(); } catch (\Exception $_) {}
            }

            // Devuelve al formulario con mensaje de error (no se redirige como si se hubiera guardado)
            return redirect()->back()
                            ->withInput()
                            ->with('error', 'No se pudo crear el cliente/usuario. ' . $e->getMessage());
        }
    }*/


   

    public function store(Request $request)
{
    $rules = [
        'nombre' => 'required|string|max:255',
        'rfc'    => 'required|string|max:50',
        'email'  => 'required|email|unique:clientes,email|unique:users,email',
        'telefono' => 'nullable|string|max:50',
        'calle' => 'required|string',
        'numero' => 'nullable|string',
        'colonia' => 'nullable|string',
        'codigo_postal' => 'required|string|max:10',
        'ciudad' => 'required|string',
        'estado' => 'required|string',
        'pais' => 'required|string',
        'contrato' => 'nullable|file|mimes:pdf|max:10240',
    ];

    $validator = Validator::make($request->all(), $rules);

    if ($validator->fails()) {
        // Si es petición AJAX (o espera JSON) devolvemos 422 con mensajes
        if ($request->expectsJson() || $request->isJson() || $request->wantsJson()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }
        // Si no, redirigimos con errores
        return redirect()->back()->withErrors($validator)->withInput();
    }

    DB::beginTransaction();
    try {
        $input = $request->only([
            'nombre','rfc','email','telefono','calle','numero','colonia',
            'codigo_postal','ciudad','estado','pais','cambio_dolar','site',
            'tarifa_region','factor_carga','latitud','longitud','contacto_nombre',
            // 'estado_cliente','capacitacion' // los seteamos manualmente abajo
        ]);

        // valores por defecto garantizados desde backend
        $input['capacitacion'] = 0;                                   // siempre 0
        $input['estado_cliente'] = 2;                                 // por defecto 2

        // si por alguna razón viene en $request (no debería), prefieres sobreescribir:
        // $input['capacitacion'] = $request->has('capacitacion') ? 1 : 0;
        // $input['estado_cliente'] = $request->input('estado_cliente', 2);

        $cliente = Cliente::create($input);

        $contractPath = null;
        if ($request->hasFile('contrato')) {
            $contractPath = $request->file('contrato')->store($this->contractStorageDirectory(), 's3');
        }

        // info fiscal
        $infoFiscal = InfoFiscalUsuario::create([
            'cliente_id' => $cliente->id,
            'razon_social' => $request->input('razon_fiscal', $request->input('razon_social') ?? $request->input('nombre')),
            'regimen_fiscal' => $request->input('regimen'),
            'domicilio_fiscal' => $request->input('domicilio'),
            'uso_cfdi' => $request->input('uso_cfdi'),
            'contrato_aceptado' => $request->boolean('contrato_aceptado') ? 1 : 0,
            'notas' => $request->input('notas_contrato'),
            'csf' => $contractPath,
        ]);

        // plan
        $plan = PlanUsuario::create([
            'cliente_id' => $cliente->id,
            'plan' => $request->input('plan'),
            'monto' => $request->input('mrr'),
            'ciclo' => $request->input('ciclo'),
            'fecha_corte' => $request->input('dia_corte'),
            'metodo_pago' => $request->input('metodo_pago'),
            'fact_automatica' => $request->boolean('fact_auto') ? 1 : 0,
            'recordatorios_pago' => $request->boolean('recordatorios') ? 1 : 0,
        ]);

        // usuario
        $tempPassword = Str::random(10);
        $user = User::create([
            'name' => $cliente->nombre,
            'email' => $cliente->email,
            'password' => Hash::make($tempPassword),
            'cliente_id' => $cliente->id
        ]);

        DB::commit();

        if ($request->expectsJson() || $request->isJson() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Cliente creado correctamente',
                'temp_password' => $tempPassword,
                'cliente_id' => $cliente->id
            ], 201);
        }

        return redirect()->route('clientes.index')->with('success', 'Cliente y usuario creados correctamente.')->with('temp_password', $tempPassword);

    } catch (\Exception $e) {
        DB::rollBack();
        // log opcional: \Log::error($e->getMessage());

        if ($request->expectsJson() || $request->isJson() || $request->wantsJson()) {
            return response()->json([
                'success' => false,
                'message' => 'Error al crear cliente: ' . $e->getMessage()
            ], 500);
        }

        return redirect()->back()->withInput()->with('error', 'No se pudo crear el cliente/usuario. ' . $e->getMessage());
    }
}


    // Mostrar un cliente con sus archivos relacionados y usuarios (si los hay)
    public function show($id)
    {
        $cliente = Cliente::with(['files', 'user'])->findOrFail($id);
        $user = $cliente->user;
        return view('clientes.show', compact('cliente', 'user'));
    }

    // Mostrar el formulario para editar un cliente
    public function edit(Cliente $cliente)
    {
        return view('clientes.edit', compact('cliente'));
    }

    // Actualizar un cliente
    public function update(Request $request, Cliente $cliente)
    {
        $request->validate([
            'nombre'         => 'required|string|max:255',
            'razon_social'   => 'required|string|max:255',
            'email'          => 'required|email|unique:clientes,email,' . $cliente->id,
            'telefono'       => 'required|string|max:15',
            'calle'          => 'required|string|max:255',
            'numero'         => 'required|string|max:10',
            'colonia'        => 'required|string|max:255',
            'codigo_postal'  => 'required|string|max:5',
            'ciudad'         => 'required|string|max:255',
            'estado'         => 'required|string|max:255',
            'pais'           => 'required|string|max:255',
            'cambio_dolar'   => 'required|numeric|min:0',
            'site'           => 'required|numeric|min:0',
        ]);

        $cliente->update($request->all());
        return redirect()->route('clientes.index')->with('success', 'Cliente actualizado correctamente.');
    }

    // Eliminar un cliente y sus archivos asociados
    public function destroy(Cliente $cliente)
    {
        foreach ($cliente->files as $file) {
            Storage::disk('public')->delete($file->file_path);
        }
        $cliente->files()->delete();
        $cliente->delete();
        return redirect()->route('clientes.index')->with('success', 'Cliente eliminado correctamente.');
    }

    // Subir archivo para un cliente
    public function uploadFile(Request $request, Cliente $cliente)
    {
        $request->validate([
            'uploaded_file' => 'required|file|max:5120|mimes:pdf,jpg,jpeg,png,doc,docx',
        ]);

        $uploadedFile = $request->file('uploaded_file');
        $originalName = $uploadedFile->getClientOriginalName();
        $fileName = time() . '_' . $originalName;
        $path = $uploadedFile->storeAs('cliente_files', $fileName, 'public');

        $cliente->files()->create([
            'file_name' => $originalName,
            'file_path' => $path,
        ]);

        return redirect()->route('clientes.show', $cliente->id)
                         ->with('success', 'Archivo subido correctamente.');
    }

    // Descargar archivo de un cliente
    public function downloadFile($clienteId, $fileId)
    {
        $file = ClienteFile::findOrFail($fileId);
        if ($file->cliente_id != $clienteId) {
            abort(403, 'Acceso no autorizado');
        }
        return response()->download(storage_path('app/public/' . $file->file_path), $file->file_name);
    }

    public function downloadContract(Cliente $cliente)
    {
        $this->authorizeContractRead($cliente);

        $infoFiscal = $cliente->infoFiscal;
        if (!$infoFiscal || !$infoFiscal->csf) {
            abort(404, 'Contrato no disponible.');
        }

        return Storage::disk('s3')->download(
            $infoFiscal->csf,
            Str::slug($cliente->nombre) . '-contrato.pdf'
        );
    }

    public function updateContract(Request $request, Cliente $cliente)
    {
        $this->authorizeContractManage($cliente);

        $request->validate([
            'contrato' => 'required|file|mimes:pdf|max:10240',
        ]);

        $infoFiscal = $cliente->infoFiscal;
        if (!$infoFiscal) {
            abort(404, 'Información fiscal no disponible.');
        }

        if ($infoFiscal->csf) {
            Storage::disk('s3')->delete($infoFiscal->csf);
        }

        $path = $request->file('contrato')->store($this->contractStorageDirectory(), 's3');
        $infoFiscal->update(['csf' => $path]);

        return redirect()->back()->with('success', 'Contrato actualizado correctamente.');
    }

    public function deleteContract(Cliente $cliente)
    {
        $this->authorizeContractManage($cliente);

        $infoFiscal = $cliente->infoFiscal;
        if (!$infoFiscal || !$infoFiscal->csf) {
            return redirect()->back()->with('info', 'No existe contrato para eliminar.');
        }

        Storage::disk('s3')->delete($infoFiscal->csf);
        $infoFiscal->update(['csf' => null]);

        return redirect()->back()->with('success', 'Contrato eliminado correctamente.');
    }

    protected function authorizeContractRead(Cliente $cliente): void
    {
        $user = Auth::user();
        if (!$user) {
            abort(403, 'No autorizado.');
        }

        if ($user->isSuperAdmin() || (int) $user->cliente_id === (int) $cliente->id) {
            return;
        }

        abort(403, 'No autorizado.');
    }

    protected function authorizeContractManage(Cliente $cliente): void
    {
        $user = Auth::user();
        if (!$user || !$user->isSuperAdmin()) {
            abort(403, 'No autorizado.');
        }
    }

    protected function contractStorageDirectory(): string
    {
        $folder = config('filesystems.folders.contracts', 'frontend/contratos');

        return trim($folder, '/');
    }

    /**
     * Método para cargar la vista 'clidash'
     * donde se muestran los gráficos interactivos avanzados.
     */
    public function clidash()
    {
        // Se asume que el usuario autenticado tiene un 'cliente_id'
        $clienteId = Auth::user()->cliente_id;
        // Consultamos todos los datos asociados a ese cliente, ordenados por fecha
        $datos = Dato::where('cliente_id', $clienteId)
                     ->orderBy('fecha', 'asc')
                     ->get();
        return view('clientes.clidash', compact('datos'));
    }

    /**
     * API para obtener datos filtrados
     * Recibe parámetros vía GET: fecha_inicio, fecha_fin, campo (por ejemplo, energy, cost, power, etc.)
     */
    public function apiDatos(Request $request)
    {
        $clienteId = Auth::user()->cliente_id;
        $query = Dato::where('cliente_id', $clienteId);

        if ($request->has('fecha_inicio') && $request->has('fecha_fin')) {
            $query->whereBetween('fecha', [$request->fecha_inicio, $request->fecha_fin]);
        }

        if ($request->has('campo')) {
            $campo = $request->campo;
            $query->select('fecha', $campo);
        }

        return response()->json($query->get());
    }

    public function updateStatus(Request $request, Cliente $cliente)
{
    // Espera: { estado: 'Activo' | 'Inactivo' } enviado desde el front
    $estado = $request->input('estado', null);

    if (is_null($estado)) {
        return response()->json(['success' => false, 'message' => 'Estado no proporcionado'], 400);
    }

    $cliente->estado = $estado;
    $cliente->save();

    return response()->json(['success' => true, 'estado' => $cliente->estado]);
}

/**
 * Marca la capacitación como recibida (capacitacion = 1)
 */
public function confirmCapacitacion(Request $request, Cliente $cliente)
{
    try {
        $cliente->capacitacion = 1;
        $cliente->save();

        return response()->json([
            'success' => true,
            'message' => 'Capacitación registrada',
            'cliente_id' => $cliente->id
        ]);
    } catch (\Exception $e) {
        return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
    }
}

/**
 * Pone al cliente como Go-Live (por ejemplo: estado_cliente = 1)
 */
public function confirmGoLive(Request $request, Cliente $cliente)
{
    try {
        // Si quieres otro id para "activo" cambia aquí
        $cliente->estado_cliente = 1;
        $cliente->save();

        return response()->json([
            'success' => true,
            'message' => 'Cliente puesto como activo (Go-Live)',
            'cliente_id' => $cliente->id
        ]);
    } catch (\Exception $e) {
        return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
    }
}


}

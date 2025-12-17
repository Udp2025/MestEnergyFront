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

        // NUEVO: cargar catálogo de regiones (id, region)
        $catalogoRegiones = DB::table('catalogo_regiones')->orderBy('region')->get();

        // NUEVO: cargar grupo tarifarios (id, nombre, factor_carga)
        $grupoTarifarios = DB::table('grupo_tarifarios')->orderBy('nombre')->get();

        return view('clientes.index', compact('clientes', 'onboardingClients', 'catalogoEstados', 'catalogoRegiones', 'grupoTarifarios'));
    }

    // Mostrar el formulario para crear un nuevo cliente
    public function create()
    {
        return view('clientes.create');
    }

    // Guardar un nuevo cliente (ya existente en tu código; lo dejamos tal cual)
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
            if ($request->expectsJson() || $request->isJson() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }
            return redirect()->back()->withErrors($validator)->withInput();
        }

        DB::beginTransaction();
        try {
            $input = $request->only([
                'nombre','rfc','email','telefono','calle','numero','colonia',
                'codigo_postal','ciudad','estado','pais','cambio_dolar','site',
                'tarifa_region','factor_carga','latitud','longitud','contacto_nombre',
            ]);

            $input['capacitacion'] = 0;
            $input['estado_cliente'] = 2;

            $cliente = Cliente::create($input);

        $contractPath = null;
        $contractDisk = config('filesystems.default', 's3');
        if ($request->hasFile('contrato')) {
            try {
                $contractPath = Storage::disk($contractDisk)
                    ->putFile($this->contractStorageDirectory(), $request->file('contrato'), 'public');
            } catch (\Throwable $e) {
                DB::rollBack();
                \Log::error('clientes.contract.upload_failed', ['error' => $e->getMessage()]);
                return $this->respondError($request, 'No se pudo subir el contrato. Verifica conexión y permisos.');
            }
        }

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
            if ($request->expectsJson() || $request->isJson() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error al crear cliente: ' . $e->getMessage()
                ], 500);
            }
            return redirect()->back()->withInput()->with('error', 'No se pudo crear el cliente/usuario. ' . $e->getMessage());
        }
    }

    // Mostrar un cliente con sus archivos relacionados, usuario, info fiscal y plan
    public function show($id)
    {
        $cliente = Cliente::with(['files', 'user', 'infoFiscal', 'planUsuario'])->findOrFail($id);

        session()->put([
            'selected_cliente_id' => $cliente->id,
            'selected_cliente_name' => $cliente->nombre,
        ]);

        $user = Auth::user();
        $isSuperAdmin = session('is_super_admin', (int) ($user?->cliente_id ?? -1) === 0);
        if ($isSuperAdmin) {
            session()->put('site', $cliente->site);
        }

        return view('clientes.show', compact('cliente'));
    }

    // Mostrar el formulario para editar un cliente
    public function edit(Cliente $cliente)
    {
        // Si es petición AJAX o espera JSON respondemos con los datos (útil para modal)
        if (request()->wantsJson() || request()->ajax() || request()->header('Accept') === 'application/json') {
            $cliente->load(['infoFiscal', 'planUsuario']);
            // Normalizar estructura para frontend (dar ambas variantes por compatibilidad)
            $arr = $cliente->toArray();
            // Añadimos claves camelCase que usa el JS (si hace falta)
            $arr['infoFiscal'] = $arr['info_fiscal'] ?? null;
            $arr['planUsuario'] = $arr['plan_usuario'] ?? null;
            return response()->json($arr);
        }

        return view('clientes.edit', compact('cliente'));
    }

    // Actualizar un cliente
    public function update(Request $request, Cliente $cliente)
    {
        // Mapear razon_fiscal -> razon_social si viene con otro nombre
        if (!$request->has('razon_social') && $request->has('razon_fiscal')) {
            $request->merge(['razon_social' => $request->input('razon_fiscal')]);
        }

        $rules = [
            'nombre'         => 'required|string|max:255',
            'razon_social'   => 'required|string|max:255',
            'email'          => 'required|email|unique:clientes,email,' . $cliente->id,
            'telefono'       => 'nullable|string|max:50',
            'calle'          => 'nullable|string|max:255',
            'numero'         => 'nullable|string|max:10',
            'colonia'        => 'nullable|string|max:255',
            'codigo_postal'  => 'nullable|string|max:10',
            'ciudad'         => 'nullable|string|max:255',
            'estado'         => 'nullable|string|max:255',
            'pais'           => 'nullable|string|max:255',
            'cambio_dolar'   => 'nullable|numeric|min:0',
            'site'           => 'required|numeric|min:0',
            'contrato'       => 'nullable|file|mimes:pdf|max:10240',
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            if ($request->expectsJson() || $request->isJson() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }
            return redirect()->back()->withErrors($validator)->withInput();
        }

        DB::beginTransaction();
        try {
            // Campos cliente
            $clienteFields = [
                'nombre','rfc','email','telefono','calle','numero','colonia',
                'codigo_postal','ciudad','estado','pais','cambio_dolar','site',
                'tarifa_region','factor_carga','latitud','longitud','contacto_nombre',
                'estado_cliente','capacitacion'
            ];

            $input = $request->only($clienteFields);
            $cliente->update($input);

            // Contrato (s3) - si suben nuevo archivo reemplazamos
            $contractPath = null;
            if ($request->hasFile('contrato')) {
                $infoFiscal = $cliente->infoFiscal;
                if ($infoFiscal && $infoFiscal->csf) {
                    // eliminar viejo
                    try {
                        Storage::disk('s3')->delete($infoFiscal->csf);
                    } catch (\Exception $_) {}
                }
                $contractPath = $request->file('contrato')->store($this->contractStorageDirectory(), 's3');
            }

            // Info Fiscal (updateOrCreate)
            $infoFiscalData = [
                'razon_social' => $request->input('razon_fiscal', $request->input('razon_social', $cliente->nombre)),
                'regimen_fiscal' => $request->input('regimen'),
                'domicilio_fiscal' => $request->input('domicilio'),
                'uso_cfdi' => $request->input('uso_cfdi'),
                'contrato_aceptado' => $request->boolean('contrato_aceptado') ? 1 : 0,
                'notas' => $request->input('notas_contrato'),
            ];
            if ($contractPath) $infoFiscalData['csf'] = $contractPath;

            $cliente->infoFiscal()->updateOrCreate(
                ['cliente_id' => $cliente->id],
                $infoFiscalData
            );

            // Plan usuario (updateOrCreate)
            $planData = [
                'plan' => $request->input('plan'),
                'monto' => $request->input('mrr'),
                'ciclo' => $request->input('ciclo'),
                'fecha_corte' => $request->input('dia_corte'),
                'metodo_pago' => $request->input('metodo_pago'),
                'fact_automatica' => $request->boolean('fact_auto') ? 1 : 0,
                'recordatorios_pago' => $request->boolean('recordatorios') ? 1 : 0,
            ];

            $cliente->planUsuario()->updateOrCreate(
                ['cliente_id' => $cliente->id],
                $planData
            );

            DB::commit();

            if ($request->expectsJson() || $request->isJson() || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Cliente actualizado correctamente',
                    'cliente' => $cliente->fresh()->load(['infoFiscal', 'planUsuario'])
                ]);
            }

            return redirect()->route('clientes.index')->with('success', 'Cliente actualizado correctamente.');
        } catch (\Exception $e) {
            DB::rollBack();
            if ($request->expectsJson() || $request->isJson() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error al actualizar cliente: ' . $e->getMessage()
                ], 500);
            }
            return redirect()->back()->withInput()->with('error', 'No se pudo actualizar el cliente. ' . $e->getMessage());
        }
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

        $contractDisk = config('filesystems.default', 's3');
        try {
            if ($infoFiscal->csf) {
                Storage::disk($contractDisk)->delete($infoFiscal->csf);
            }
            $path = Storage::disk($contractDisk)
                ->putFile($this->contractStorageDirectory(), $request->file('contrato'), 'public');
            $infoFiscal->update(['csf' => $path]);
        } catch (\Throwable $e) {
            \Log::error('clientes.contract.update_failed', ['error' => $e->getMessage()]);
            return $this->respondError($request, 'No se pudo actualizar el contrato. Intenta de nuevo.');
        }

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

    protected function respondError(Request $request, string $message)
    {
        if ($request->expectsJson()) {
            return response()->json(['success' => false, 'message' => $message], 500);
        }
        return redirect()->back()->with('error', $message);
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
     */
    public function clidash()
    {
        $clienteId = Auth::user()->cliente_id;
        $datos = Dato::where('cliente_id', $clienteId)
                     ->orderBy('fecha', 'asc')
                     ->get();
        return view('clientes.clidash', compact('datos'));
    }

    /**
     * API para obtener datos filtrados
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
        $estado = $request->input('estado', null);

        if (is_null($estado)) {
            return response()->json(['success' => false, 'message' => 'Estado no proporcionado'], 400);
        }

        $cliente->estado = $estado;
        $cliente->save();

        return response()->json(['success' => true, 'estado' => $cliente->estado]);
    }

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

    public function confirmGoLive(Request $request, Cliente $cliente)
    {
        try {
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

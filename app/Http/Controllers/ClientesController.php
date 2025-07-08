<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\ClienteFile;
use App\Models\User;
use App\Models\Dato; // Modelo para la tabla 'datos'
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;

class ClientesController extends Controller
{
    public function index()
    {
        // Carga la relación 'files' y 'user' para cada cliente
        $clientes = Cliente::with(['files', 'user'])->get();
        return view('clientes.index', compact('clientes'));
    }

    // Mostrar el formulario para crear un nuevo cliente
    public function create()
    {
        return view('clientes.create');
    }

    // Guardar un nuevo cliente
    public function store(Request $request)
    {
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
}

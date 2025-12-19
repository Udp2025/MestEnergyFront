<?php

namespace App\Http\Controllers;

use App\Models\Site;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\QueryException;
use Illuminate\Validation\ValidationException;

class SitesController extends Controller
{
    public function index(Request $request)
    {
        $sites = Site::orderBy('site_id')->get();

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'sites' => $sites]);
        }

        return view('sites.index', compact('sites'));
    }

    public function store(Request $request)
    {
        $rules = [
            'site_id'   => 'required|integer|unique:sites,site_id',
            'site_name' => 'required|string|max:255',
        ];

        try {
            $validated = $request->validate($rules);

            DB::beginTransaction();

            $site = Site::create([
                'site_id'   => intval($validated['site_id']),
                'site_name' => trim($validated['site_name']),
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Site creado correctamente',
                'site'    => $site
            ], 201);
        } catch (ValidationException $ve) {
            return response()->json([
                'success' => false,
                'message' => 'Errores de validación',
                'errors'  => $ve->errors()
            ], 422);
        } catch (QueryException $qe) {
            DB::rollBack();
            Log::error('sites.store.query', ['error' => $qe->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Error en la base de datos: ' . $qe->getMessage()
            ], 500);
        } catch (\Throwable $t) {
            DB::rollBack();
            Log::error('sites.store.exception', ['error' => $t->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Error al crear el site: ' . $t->getMessage()
            ], 500);
        }
    }

    public function updateName(Request $request)
    {
        $rules = [
            'site_id'   => 'required|integer',
            'site_name' => 'required|string|max:255',
        ];

        try {
            $validated = $request->validate($rules);

            $site = Site::where('site_id', intval($validated['site_id']))->first();

            if (!$site) {
                return response()->json([
                    'success' => false,
                    'message' => 'Site no encontrado'
                ], 404);
            }

            $site->site_name = trim($validated['site_name']);
            $site->save();

            return response()->json([
                'success' => true,
                'message' => 'Nombre del site actualizado',
                'site'    => $site
            ], 200);
        } catch (ValidationException $ve) {
            return response()->json([
                'success' => false,
                'message' => 'Errores de validación',
                'errors'  => $ve->errors()
            ], 422);
        } catch (QueryException $qe) {
            Log::error('sites.updateName.query', ['error' => $qe->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Error en la base de datos: ' . $qe->getMessage()
            ], 500);
        } catch (\Throwable $t) {
            Log::error('sites.updateName.exception', ['error' => $t->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar el site: ' . $t->getMessage()
            ], 500);
        }
    }

    public function destroyBySiteId(Request $request, $siteId)
    {
        try {
            $site = Site::where('site_id', intval($siteId))->first();
            if (!$site) {
                return response()->json(['success' => false, 'message' => 'Site no encontrado'], 404);
            }

            $site->delete();

            return response()->json(['success' => true, 'message' => 'Site eliminado']);
        } catch (\Throwable $t) {
            Log::error('sites.destroy.exception', ['error' => $t->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Error al eliminar site: ' . $t->getMessage()], 500);
        }
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Panel;
use App\Models\Widget;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class WidgetController extends Controller
{
    // Crea un nuevo widget en un panel
    public function store(Request $request, Panel $panel)
    {
        if ($panel->user_id !== Auth::id()) {
            abort(403);
        }

        $data = $request->validate([
            'name'   => 'required|string|max:255',
            'type'   => 'required|string',
            'config' => 'required|array',
        ]);

        $widget = $panel->widgets()->create($data);

        return response()->json(['success' => true, 'graph' => $widget]);
    }

    // Actualiza un widget
    public function update(Request $request, Panel $panel, Widget $widget)
    {
        if ($panel->user_id !== Auth::id() || $widget->panel_id !== $panel->id) {
            abort(403);
        }

        $data = $request->validate([
            'name'   => 'required|string|max:255',
            'type'   => 'required|string',
            'config' => 'required|array',
        ]);

        $widget->update($data);

        return response()->json(['success' => true, 'graph' => $widget]);
    }

    // Elimina un widget
    public function destroy(Panel $panel, Widget $widget)
    {
        if ($panel->user_id !== Auth::id() || $widget->panel_id !== $panel->id) {
            abort(403);
        }

        $widget->delete();
        return response()->json(['success' => true]);
    }
}

<?php
// app/Models/DashboardWidget.php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DashboardWidget extends Model
{
    use HasFactory;

    protected $fillable = [
        'dashboard_id',
        'widget_definition_id',
        'position_index',
        'layout',
        'visual_config',
        'data_filters',
    ];

    protected $casts = [
        'layout' => 'array',
        'visual_config' => 'array',
        'data_filters' => 'array',
    ];

    public function dashboard()
    {
        return $this->belongsTo(Dashboard::class);
    }

    public function definition()
    {
        return $this->belongsTo(WidgetDefinition::class, 'widget_definition_id');
    }
}

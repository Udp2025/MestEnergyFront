<?php
// app/Models/WidgetDefinition.php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WidgetDefinition extends Model
{
    use HasFactory;

    protected $fillable = [
        'slug',
        'name',
        'kind',
        'description',
        'source_dataset',
        'default_config',
    ];

    protected $casts = [
        'default_config' => 'array',
    ];

    public function widgets()
    {
        return $this->hasMany(DashboardWidget::class);
    }
}

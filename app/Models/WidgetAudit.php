<?php
// app/Models/WidgetAudit.php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WidgetAudit extends Model
{
    use HasFactory;

    protected $table = 'widget_audit';

    public const UPDATED_AT = null;

    protected $fillable = [
        'dashboard_widget_id',
        'user_id',
        'action',
        'payload',
    ];

    protected $casts = [
        'payload' => 'array',
        'created_at' => 'datetime',
    ];

    public function widget()
    {
        return $this->belongsTo(DashboardWidget::class, 'dashboard_widget_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

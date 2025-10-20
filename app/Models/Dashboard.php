<?php
// app/Models/Dashboard.php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Dashboard extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'title',
        'layout_settings',
    ];

    protected $casts = [
        'layout_settings' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function widgets()
    {
        return $this->hasMany(DashboardWidget::class)->orderBy('position_index');
    }
}

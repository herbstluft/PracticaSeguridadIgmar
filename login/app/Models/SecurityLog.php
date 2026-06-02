<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SecurityLog extends Model
{
    use HasFactory;

    /**
     * Los atributos que se pueden asignar masivamente.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'ip_address',
        'email',
        'event',
        'status',
    ];

    /**
     * Obtener el usuario asociado con el registro de seguridad.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

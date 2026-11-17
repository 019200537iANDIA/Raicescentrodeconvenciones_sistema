<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Client extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'full_name',
        'mobile',
        'dni_encrypted',
        'address_encrypted',
        'district',
        'client_type',
        'notes',
        'created_by',
        'deleted_by',
    ];

    protected function casts(): array
    {
        return [
            'dni_encrypted' => 'encrypted',
            'address_encrypted' => 'encrypted',
        ];
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    protected $fillable = ['name', 'category', 'selection_group', 'description', 'unit_label', 'is_optional', 'is_active'];

    protected function casts(): array
    {
        return ['is_optional' => 'boolean', 'is_active' => 'boolean'];
    }

    public function rates()
    {
        return $this->hasMany(ServiceRate::class);
    }

    public function packageServices()
    {
        return $this->hasMany(PackageService::class);
    }
}

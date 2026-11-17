<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PackageService extends Model
{
    protected $table = 'package_services';

    public $timestamps = false;

    protected $fillable = [
        'package_id', 'service_id', 'default_quantity', 'is_optional', 'quantity_rule',
        'quantity_rule_value', 'event_type_names', 'replaces_service_id', 'sort_order', 'display_quantity', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'default_quantity' => 'decimal:2',
            'quantity_rule_value' => 'decimal:2',
            'is_optional' => 'boolean',
            'display_quantity' => 'boolean',
            'event_type_names' => 'array',
        ];
    }

    public function service()
    {
        return $this->belongsTo(Service::class);
    }

    public function replacingService()
    {
        return $this->belongsTo(Service::class, 'replaces_service_id');
    }
}

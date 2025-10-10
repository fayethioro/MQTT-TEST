<?php

namespace App\Models;

use App\Models\Driver;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class DriverLocationHistorique extends Model
{
    use HasFactory;
    protected $fillable = [
        'id',
        'driver_id',
        'latitude',
        'longitude',
        'speed',
        'bearing',
        'accuracy',
        'provider',
        'captured_at',
    ];

    protected $casts = [
        'captured_at' => 'datetime',
    ];

    protected $primaryKey = 'id';
    protected $keyType = 'string';

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->id)) {
                $model->id = (string) Str::uuid();
            }
        });
    }

    public function driver()
    {
        return $this->belongsTo(Driver::class);
    }


}

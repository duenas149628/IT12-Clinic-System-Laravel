<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Appointment extends Model
{
    use HasFactory;

    protected $table = 'appointments';

    protected $primaryKey = 'appointment_id';

    protected $fillable = [
        'patient_id',
        'preferred_date',
        'preferred_start_time',
        'preferred_end_time',
        'confirmed_date',
        'confirmed_start_time',
        'confirmed_end_time',
        'reason',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'preferred_date' => 'date',
            'confirmed_date' => 'date',
        ];
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class, 'patient_id', 'patient_id');
    }

    public function visitRecord(): HasOne
    {
        return $this->hasOne(VisitRecord::class, 'appointment_id', 'appointment_id');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VisitRecord extends Model
{
    use HasFactory;

    protected $table = 'visit_records';

    protected $primaryKey = 'visit_id';

    protected $fillable = [
        'patient_id',
        'appointment_id',
        'visit_date',
        'chief_complaint',
        'findings',
        'treatment',
        'notes',
        'follow_up',
    ];

    protected function casts(): array
    {
        return [
            'visit_date' => 'date',
        ];
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class, 'patient_id', 'patient_id');
    }

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class, 'appointment_id', 'appointment_id');
    }
}

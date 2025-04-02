<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TimeEntry extends Model
{
    protected $table = 'time_entries';

    protected $fillable = [
        'user_id',
        'project_id',
        'date',
        'phase',
        'hours',
        'description',
    ];

    protected $casts = [
        'date' => 'date',
        'hours' => 'decimal:2',
    ];

    public const PHASES = [
        'dia' => 'Día',
        'noche' => 'Noche',
        'madrugada' => 'Madrugada',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}

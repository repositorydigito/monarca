<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TimeEntryProjectPhaseReport extends TimeEntry
{
    protected $table = 'time_entries';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'project_id',
        'date',
        'phase',
        'hours',
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

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Project extends Model
{
    protected $fillable = [
        'name',
        'code',
        'entity_id',
        'business_line_id',
        'start_date',
        'end_date',
        'description',
        'created_by',
        'updated_by',
    ];

    public function entity(): BelongsTo
    {
        return $this->belongsTo(Entity::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function modifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function equipmentLogs(): HasMany
    {
        return $this->hasMany(EquipmentLog::class);
    }

    public function incomes(): HasMany
    {
        return $this->hasMany(Income::class);
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class);
    }

    public function businessLine(): BelongsTo
    {
        return $this->belongsTo(BusinessLine::class);
    }

    public function timeEntries()
    {
        return $this->hasMany(TimeEntry::class);
    }
}

<?php

namespace App\Models;

use Spatie\Permission\Models\Role as SpatieRole;

class Role extends SpatieRole
{
    protected $fillable = [
        'name',
        'guard_name',
        'can_view_all_users',
    ];

    protected $casts = [
        'can_view_all_users' => 'boolean',
    ];
}

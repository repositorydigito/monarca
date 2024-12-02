<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Foundation\Auth\User as AuthUser;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasRoles;

    protected $fillable = [
        'name',
        'email',
        'password',
    ];



    protected $hidden = [
        'password',
        'remember_token',
    ];

    public function entities(): HasMany
    {
        return $this->hasMany(Entity::class, 'created_by');
    }




    public function projects(): HasMany
    {
        return $this->hasMany(Project::class, 'created_by');
    }

    public function responsibleExpenses()
    {
        return $this->hasMany(Expense::class, 'responsible_id');
    }

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}

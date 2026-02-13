<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Role extends Model
{
    const SUPERADMIN = 1;
    const ADMIN = 2;
    const DIRECTORATE_USER = 3;
    const PROJECT_USER = 4;
    const DEPARTMENT_USER = 5;
    const CORPORATE_USER = 6;

    use HasFactory;
    use SoftDeletes;

    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected $fillable = [
        'title',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(
            Permission::class
        );
    }

    public function givePermissionTo(Permission $permission): mixed
    {
        return $this->permission()->save($permission);
    }

    public function hasPermission(Permission $permission, User $user): mixed
    {
        return $this->hasRole($permission->roles);
    }

    public function inRole($permission): bool
    {
        if (is_string($permission)) {
            return $this->permissions->contains('title', $permission);
        }

        return (bool) $permission->intersect($this->permissions)->count();
    }
}

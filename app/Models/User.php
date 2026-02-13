<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Trait\RoleBasedAccess;
use Illuminate\Support\Collection;
use Spatie\Activitylog\LogOptions;
use Illuminate\Support\Facades\Auth;
use Illuminate\Notifications\Notifiable;
use Spatie\Activitylog\Traits\LogsActivity;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class User extends Authenticatable
{
    use HasFactory, Notifiable, SoftDeletes, LogsActivity;
    use RoleBasedAccess;

    protected $fillable = [
        'directorate_id',
        'name',
        'mobile_number',
        'employee_id',
        'email',
        'password',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'last_login_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    public function initials(): string
    {
        return Str::of($this->name)
            ->explode(' ')
            ->map(fn(string $name) => Str::of($name)->substr(0, 1))
            ->implode('');
    }

    public function authenticated(Request $request, $user): void
    {
        $user->update([
            'last_login_at' => Carbon::now()->toDateTimeString(),
            'last_login_ip' => $request->getClientIp(),
        ]);
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class);
    }

    public function directorate(): BelongsTo
    {
        return $this->belongsTo(Directorate::class);
    }

    public function projects(): BelongsToMany
    {
        return $this->belongsToMany(Project::class, 'project_user', 'user_id', 'project_id');
    }

    public function isProjectManager(): bool
    {
        return Project::where('project_manager', $this->id)->exists();
    }

    public function assignRole(Role $role): mixed
    {
        return $this->roles()->save($role);
    }

    public function comments(): BelongsToMany
    {
        return $this->belongsToMany(Comment::class, 'comment_user')
            ->withPivot('read_at')
            ->withTimestamps();
    }

    public function hasRole($roles): bool
    {
        if (! $this->relationLoaded('roles')) {
            $this->load('roles');
        }

        if ($roles instanceof Collection || is_array($roles)) {
            $inputRoles = collect($roles);

            return $inputRoles->filter(function ($item) {
                if ($item instanceof Role) {
                    return $this->roles->contains('id', $item->id);
                }
                if (is_int($item)) {
                    return $this->roles->contains('id', $item);
                }
                if (is_string($item)) {
                    return $this->roles->contains('title', $item);
                }

                return false;
            })->isNotEmpty();
        }

        if (is_string($roles)) {
            return $this->roles->contains('title', $roles);
        }

        if (is_int($roles)) {
            return $this->roles->contains('id', $roles);
        }

        if ($roles instanceof Role) {
            return $this->roles->contains($roles);
        }

        return false;
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logExcept(['password']) // Exclude sensitive password field
            ->logOnlyDirty()
            ->useLogName('user')
            ->setDescriptionForEvent(function (string $eventName) {
                $user = Auth::user()?->name ?? 'System';
                return "User {$eventName} by {$user}";
            });
    }
}

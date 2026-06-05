<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;
use function Illuminate\Support\enum_value;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasRoles {
        HasRoles::hasPermissionTo as protected hasPermissionToViaSpatie;
        HasRoles::checkPermissionTo as protected checkPermissionToViaSpatie;
    }

    protected $fillable = ['company_id', 'name', 'email', 'password', 'is_super_admin', 'is_active', 'blocked_permissions'];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_super_admin' => 'boolean',
            'is_active' => 'boolean',
            'blocked_permissions' => 'array',
        ];
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function isSuperAdmin(): bool
    {
        return $this->is_super_admin;
    }

    public function hasBlockedPermission(string $permission): bool
    {
        return in_array($permission, $this->blocked_permissions ?? [], true);
    }

    public function hasPermissionTo($permission, ?string $guardName = null): bool
    {
        $permissionName = $this->resolvePermissionName($permission);

        if ($permissionName !== null && $this->hasBlockedPermission($permissionName)) {
            return false;
        }

        return $this->hasPermissionToViaSpatie($permission, $guardName);
    }

    public function checkPermissionTo($permission, ?string $guardName = null): bool
    {
        $permissionName = $this->resolvePermissionName($permission);

        if ($permissionName !== null && $this->hasBlockedPermission($permissionName)) {
            return false;
        }

        return $this->checkPermissionToViaSpatie($permission, $guardName);
    }

    public function effectivePermissionNames(): array
    {
        $blockedPermissions = $this->blocked_permissions ?? [];

        return $this->getAllPermissions()
            ->pluck('name')
            ->reject(fn($permission) => in_array($permission, $blockedPermissions, true))
            ->values()
            ->all();
    }

    private function resolvePermissionName($permission): ?string
    {
        $permission = enum_value($permission);

        if (is_string($permission)) {
            return $permission;
        }

        if (is_object($permission) && isset($permission->name) && is_string($permission->name)) {
            return $permission->name;
        }

        return null;
    }
}

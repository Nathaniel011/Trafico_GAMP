<?php
namespace App\Services;

use App\User;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class UserRoleRules
{
    public static function isAdministrator(?string $roles): bool
    {
        return in_array('ADMIN', preg_split('/[\s,;|]+/', (string)$roles), true);
    }

    public static function administrators(Collection $users): Collection
    {
        return $users->filter(fn ($user)=>self::isAdministrator($user->roles));
    }

    public static function lockUsers(): Collection
    {
        User::orderBy('id')->lockForUpdate()->get(['id']);
        // Query again after the lock so an assignment committed by a waiting request is visible.
        return User::orderBy('id')->get();
    }

    public static function assertAssignment(Collection $users, string $role, ?int $userId=null): void
    {
        $admins=self::administrators($users);
        if ($role === 'ADMIN' && $admins->contains(fn ($user)=>$user->id !== $userId)) {
            throw ValidationException::withMessages(['role'=>'Ya existe un Administrador. Solo puede haber una cuenta con ese rol.']);
        }
        $current=$users->firstWhere('id',$userId);
        if ($role !== 'ADMIN' && $current && self::isAdministrator($current->roles) && $admins->count() === 1) {
            throw ValidationException::withMessages(['role'=>'Debe conservar al único Administrador del sistema.']);
        }
    }
}

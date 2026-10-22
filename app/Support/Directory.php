<?php

namespace App\Support;

use App\Enums\UserRole;
use App\Models\User;

final class Directory
{
    /**
     * @return list<array{id: int, name: string}>
     */
    public static function options(UserRole $role): array
    {
        return User::query()
            ->where('role', $role)
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (User $user): array => [
                'id' => $user->id,
                'name' => $user->name,
            ])
            ->all();
    }
}

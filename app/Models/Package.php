<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['title', 'price', 'duration_days', 'sessions_count'])]
class Package extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
        ];
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(StudentSubscription::class);
    }

    public function isSessionPack(): bool
    {
        return $this->sessions_count !== null;
    }
}

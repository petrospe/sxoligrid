<?php

namespace App\Models;

use App\Enums\SubscriptionStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['student_id', 'package_id', 'remaining_sessions', 'starts_at', 'expires_at', 'status'])]
class StudentSubscription extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'starts_at' => 'date',
            'expires_at' => 'date',
            'status' => SubscriptionStatus::class,
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function package(): BelongsTo
    {
        return $this->belongsTo(Package::class);
    }

    public function isUsableOn(string $date): bool
    {
        if ($this->status !== SubscriptionStatus::Active) {
            return false;
        }

        if ($this->starts_at->toDateString() > $date) {
            return false;
        }

        return $this->expires_at === null || $this->expires_at->toDateString() >= $date;
    }
}

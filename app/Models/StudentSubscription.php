<?php

namespace App\Models;

use App\Enums\PaymentStatus;
use App\Enums\SubscriptionStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['student_id', 'course_id', 'title', 'total_amount', 'starts_at', 'ends_at', 'status'])]
class StudentSubscription extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'total_amount' => 'decimal:2',
            'starts_at' => 'date',
            'ends_at' => 'date',
            'status' => SubscriptionStatus::class,
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class, 'subscription_id');
    }

    public function paidAmount(): float
    {
        if (array_key_exists('paid_amount', $this->attributes)) {
            return round((float) $this->attributes['paid_amount'], 2);
        }

        return round((float) $this->payments()->where('status', PaymentStatus::Paid)->sum('amount'), 2);
    }

    public function remainingAmount(): float
    {
        return round((float) $this->total_amount - $this->paidAmount(), 2);
    }

    public function isUsableOn(string $date): bool
    {
        if ($this->status !== SubscriptionStatus::Active) {
            return false;
        }

        if ($this->starts_at->toDateString() > $date) {
            return false;
        }

        return $this->ends_at === null || $this->ends_at->toDateString() >= $date;
    }
}

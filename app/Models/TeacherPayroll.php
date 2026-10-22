<?php

namespace App\Models;

use App\Enums\PayrollStatus;
use App\Enums\PayrollType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['teacher_id', 'type', 'base_amount', 'payment_period', 'status'])]
class TeacherPayroll extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'base_amount' => 'decimal:2',
            'type' => PayrollType::class,
            'status' => PayrollStatus::class,
        ];
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['title', 'description', 'event_date', 'location'])]
class Event extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'event_date' => 'datetime',
        ];
    }

    public function participants(): HasMany
    {
        return $this->hasMany(EventParticipant::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

#[Fillable([
    'logo_path',
    'name_el',
    'name_en',
    'title_el',
    'title_en',
    'subtitle_el',
    'subtitle_en',
    'description_el',
    'description_en',
    'details_el',
    'details_en',
])]
class LandingProfile extends Model
{
    /**
     * @return array<string, string>
     */
    public static function defaults(): array
    {
        return [
            'name_el' => 'SxoliGrid',
            'name_en' => 'SxoliGrid',
            'title_el' => 'Σύστημα διαχείρισης σχολών και στούντιο',
            'title_en' => 'Academy & Studio Management System',
            'subtitle_el' => 'Σχολές · στούντιο · φροντιστήρια',
            'subtitle_en' => 'Schools · studios · tutoring',
            'description_el' => 'Δημόσια προβολή μαθημάτων, αιθουσών και ωρών. Τα ονόματα των καθηγητών δεν εμφανίζονται στο πρόγραμμα.',
            'description_en' => 'Public view of courses, rooms, and times. Teacher names are not shown on the schedule.',
            'details_el' => 'Μαθήματα σε τμήματα και ιδιαίτερα, με πληρωμή ανά μήνα, τρίμηνο, πακέτο ή συνεδρία, και ωράριο ανά ημέρα και αίθουσα.',
            'details_en' => 'Group and private classes, paid monthly, quarterly, by package, or per session, with a timetable by day and room.',
        ];
    }

    public static function current(): self
    {
        $profile = static::query()->first();

        if ($profile instanceof self) {
            return $profile;
        }

        return static::query()->create(static::defaults());
    }

    /**
     * @return array{name: string, title: string, subtitle: string, description: string, details: string, logo_url: string}
     */
    public function forLocale(string $locale): array
    {
        $locale = $locale === 'en' ? 'en' : 'el';

        return [
            'name' => (string) $this->getAttribute('name_'.$locale),
            'title' => (string) $this->getAttribute('title_'.$locale),
            'subtitle' => (string) $this->getAttribute('subtitle_'.$locale),
            'description' => (string) $this->getAttribute('description_'.$locale),
            'details' => (string) $this->getAttribute('details_'.$locale),
            'logo_url' => $this->logoUrl(),
        ];
    }

    /**
     * @return array{el: array<string, string>, en: array<string, string>, logo_url: string}
     */
    public function formState(): array
    {
        return [
            'el' => [
                'name' => (string) $this->name_el,
                'title' => (string) $this->title_el,
                'subtitle' => (string) $this->subtitle_el,
                'description' => (string) $this->description_el,
                'details' => (string) $this->details_el,
            ],
            'en' => [
                'name' => (string) $this->name_en,
                'title' => (string) $this->title_en,
                'subtitle' => (string) $this->subtitle_en,
                'description' => (string) $this->description_en,
                'details' => (string) $this->details_en,
            ],
            'logo_url' => $this->logoUrl(),
        ];
    }

    public function logoUrl(): string
    {
        if (is_string($this->logo_path) && $this->logo_path !== '') {
            return Storage::disk('public')->url($this->logo_path);
        }

        return asset('branding/logo.png');
    }
}

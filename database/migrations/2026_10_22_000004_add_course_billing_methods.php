<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->json('billing_methods')->default('["monthly"]');
        });

        DB::table('courses')->where('type', 'drop_in')->update([
            'type' => 'group',
            'billing_methods' => json_encode(['drop_in']),
        ]);

        DB::table('courses')->where('title', 'English B2')->update([
            'billing_methods' => json_encode(['monthly', 'quarterly']),
        ]);
        DB::table('courses')->where('title', 'Salsa')->update([
            'billing_methods' => json_encode(['monthly', 'drop_in']),
        ]);
        DB::table('courses')->where('title', 'Πιάνο')->update([
            'billing_methods' => json_encode(['drop_in']),
        ]);

        DB::table('landing_profiles')
            ->where('details_el', 'Μαθήματα σε τμήματα, ιδιαίτερα και drop-in, με ωράριο ανά ημέρα και αίθουσα.')
            ->update(['details_el' => 'Μαθήματα σε τμήματα και ιδιαίτερα, με πληρωμή ανά μήνα, τρίμηνο, πακέτο ή συνεδρία, και ωράριο ανά ημέρα και αίθουσα.']);
        DB::table('landing_profiles')
            ->where('details_en', 'Group classes, private lessons, and drop-in sessions, with a timetable by day and room.')
            ->update(['details_en' => 'Group and private classes, paid monthly, quarterly, by package, or per session, with a timetable by day and room.']);
    }

    public function down(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->dropColumn('billing_methods');
        });
    }
};

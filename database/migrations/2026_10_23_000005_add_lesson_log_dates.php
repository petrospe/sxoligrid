<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lesson_logs', function (Blueprint $table) {
            $table->date('happened_on')->nullable()->after('teacher_id');
        });

        foreach (DB::table('lesson_logs')->orderBy('id')->get() as $log) {
            DB::table('lesson_logs')->where('id', $log->id)->update([
                'happened_on' => substr((string) $log->created_at, 0, 10),
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('lesson_logs', function (Blueprint $table) {
            $table->dropColumn('happened_on');
        });
    }
};

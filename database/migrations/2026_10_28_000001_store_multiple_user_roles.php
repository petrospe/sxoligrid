<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->json('roles')->nullable();
        });

        foreach (DB::table('users')->select('id', 'role')->get() as $user) {
            DB::table('users')->where('id', $user->id)->update([
                'roles' => json_encode([$user->role]),
            ]);
        }

        Schema::table('users', function (Blueprint $table): void {
            $table->dropIndex(['role']);
            $table->dropColumn('role');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('role')->default('student')->index();
        });

        foreach (DB::table('users')->select('id', 'roles')->get() as $user) {
            $roles = json_decode((string) $user->roles, true);
            $role = is_array($roles) && isset($roles[0]) ? $roles[0] : 'student';

            DB::table('users')->where('id', $user->id)->update(['role' => $role]);
        }

        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn('roles');
        });
    }
};

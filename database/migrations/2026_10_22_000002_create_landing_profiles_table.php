<?php

use App\Models\LandingProfile;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('landing_profiles', function (Blueprint $table) {
            $table->id();
            $table->string('logo_path')->nullable();
            $table->string('name_el');
            $table->string('name_en');
            $table->string('title_el');
            $table->string('title_en');
            $table->string('subtitle_el');
            $table->string('subtitle_en');
            $table->text('description_el');
            $table->text('description_en');
            $table->text('details_el');
            $table->text('details_en');
            $table->timestamps();
        });

        DB::table('landing_profiles')->insert([
            ...LandingProfile::defaults(),
            'logo_path' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('landing_profiles');
    }
};

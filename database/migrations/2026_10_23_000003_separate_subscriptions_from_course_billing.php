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
        Schema::table('student_subscriptions', function (Blueprint $table) {
            $table->foreignId('course_id')->nullable()->after('student_id')->constrained()->nullOnDelete();
            $table->string('title')->default('');
            $table->decimal('total_amount', 10, 2)->default(0);
            $table->date('ends_at')->nullable();
        });

        $packages = DB::table('packages')->get()->keyBy(fn (object $package): string => (string) $package->id);

        foreach (DB::table('student_subscriptions')->orderBy('id')->get() as $row) {
            $package = $packages->get((string) $row->package_id);

            DB::table('student_subscriptions')->where('id', $row->id)->update([
                'course_id' => $package->course_id ?? null,
                'title' => $package->title ?? 'Συνδρομή',
                'total_amount' => $package->price ?? 0,
                'ends_at' => $row->expires_at,
            ]);
        }

        DB::table('student_subscriptions')->where('status', 'depleted')->update(['status' => 'active']);

        Schema::table('subscriptions_and_payments', function (Blueprint $table) {
            $table->foreignId('subscription_id')->nullable()->after('student_id')->constrained('student_subscriptions')->nullOnDelete();
        });

        foreach (DB::table('subscriptions_and_payments')->orderBy('id')->get() as $payment) {
            $dropIn = in_array($payment->type, ['drop_in_ticket', 'private_session', 'drop_in'], true);
            $subscriptionId = null;

            if (! $dropIn) {
                $matches = DB::table('student_subscriptions')->where('student_id', $payment->student_id);

                if ($payment->course_id !== null) {
                    $byCourse = (clone $matches)->where('course_id', $payment->course_id)->pluck('id');

                    if ($byCourse->count() === 1) {
                        $subscriptionId = (int) $byCourse->first();
                    }
                }

                if ($subscriptionId === null) {
                    $any = $matches->pluck('id');

                    if ($any->count() === 1) {
                        $subscriptionId = (int) $any->first();
                    }
                }
            }

            DB::table('subscriptions_and_payments')->where('id', $payment->id)->update([
                'type' => $dropIn ? 'drop_in' : 'installment',
                'subscription_id' => $subscriptionId,
            ]);
        }

        Schema::table('student_subscriptions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('package_id');
            $table->dropColumn(['remaining_sessions', 'expires_at']);
        });

        Schema::dropIfExists('packages');

        Schema::table('courses', function (Blueprint $table) {
            $table->dropColumn(['price', 'prices', 'billing_methods']);
        });

        Schema::table('enrollments', function (Blueprint $table) {
            $table->dropColumn('billing_method');
        });

        Schema::dropIfExists('billing_policies');

        $defaults = LandingProfile::defaults();

        DB::table('landing_profiles')
            ->whereIn('details_el', [
                'Μαθήματα σε τμήματα, ιδιαίτερα και drop-in, με ωράριο ανά ημέρα και αίθουσα.',
                'Μαθήματα σε τμήματα και ιδιαίτερα, με πληρωμή ανά μήνα, τρίμηνο, πακέτο ή συνεδρία, και ωράριο ανά ημέρα και αίθουσα.',
            ])
            ->update([
                'details_el' => $defaults['details_el'],
                'details_en' => $defaults['details_en'],
            ]);
    }

    public function down(): void
    {
        Schema::create('billing_policies', function (Blueprint $table) {
            $table->id();
            $table->json('multi_course_discounts');
            $table->timestamps();
        });

        Schema::create('packages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('billing_method')->nullable();
            $table->string('title');
            $table->decimal('price', 10, 2);
            $table->unsignedInteger('duration_days')->nullable();
            $table->unsignedInteger('sessions_count')->nullable();
            $table->timestamps();
            $table->unique(['course_id', 'billing_method']);
        });

        Schema::table('courses', function (Blueprint $table) {
            $table->decimal('price', 10, 2)->default(0);
            $table->json('prices')->nullable();
            $table->json('billing_methods')->default('["monthly"]');
        });

        Schema::table('enrollments', function (Blueprint $table) {
            $table->string('billing_method')->default('monthly');
        });

        Schema::table('subscriptions_and_payments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('subscription_id');
        });

        Schema::table('student_subscriptions', function (Blueprint $table) {
            $table->foreignId('package_id')->nullable()->constrained()->restrictOnDelete();
            $table->unsignedInteger('remaining_sessions')->nullable();
            $table->date('expires_at')->nullable();
            $table->dropConstrainedForeignId('course_id');
            $table->dropColumn(['title', 'total_amount', 'ends_at']);
        });
    }
};

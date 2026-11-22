<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plan_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->string('plan_slug'); // basic | standard | premium | business
            $table->decimal('amount', 12, 2); // payable amount at request time
            $table->string('status')->default('pending'); // pending | approved | rejected
            $table->timestamps();
            $table->index(['business_id', 'status']);
        });

        // Which plan a subscription payment was for (null = recorded before plans existed).
        Schema::table('subscription_payments', function (Blueprint $table) {
            $table->string('plan_slug')->nullable()->after('months');
        });
    }

    public function down(): void
    {
        Schema::table('subscription_payments', function (Blueprint $table) {
            $table->dropColumn('plan_slug');
        });
        Schema::dropIfExists('plan_requests');
    }
};

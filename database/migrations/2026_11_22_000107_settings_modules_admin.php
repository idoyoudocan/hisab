<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('businesses', function (Blueprint $table) {
            // Per-business module toggles, e.g. {"cash_count": false}. Empty = all on.
            $table->json('settings')->nullable();
            // Paid subscription end date; null = never paid.
            $table->date('paid_until')->nullable();
        });

        Schema::create('subscription_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount', 12, 2);
            $table->string('method')->default('cash'); // cash | bkash | nagad | bank
            $table->unsignedInteger('months')->default(0); // months this payment covers
            $table->date('paid_at');
            $table->text('note')->nullable();
            $table->string('recorded_by')->nullable(); // admin email who recorded it
            $table->timestamps();
            $table->index(['business_id', 'paid_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscription_payments');
        Schema::table('businesses', function (Blueprint $table) {
            $table->dropColumn(['settings', 'paid_until']);
        });
    }
};

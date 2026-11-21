<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->string('party_type'); // customer | supplier
            $table->unsignedBigInteger('party_id');
            $table->decimal('amount', 12, 2);
            $table->string('direction'); // in = collection from customer, out = payment to supplier
            $table->string('method')->default('cash');
            $table->date('date');
            $table->text('note')->nullable();
            $table->timestamps();
            $table->index(['business_id', 'party_type', 'party_id']);
        });

        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->string('type'); // income | expense
            $table->string('category');
            $table->decimal('amount', 12, 2);
            $table->date('date');
            $table->string('method')->default('cash');
            $table->string('scope')->default('shop'); // shop | personal
            $table->text('note')->nullable();
            $table->timestamps();
            $table->index(['business_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
        Schema::dropIfExists('payments');
    }
};

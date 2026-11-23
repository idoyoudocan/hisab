<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Admin can disable a shopkeeper's account (login blocked, data kept).
        Schema::table('businesses', function (Blueprint $table) {
            $table->string('status', 20)->default('active')->after('plan');
            $table->string('disabled_reason', 255)->nullable()->after('status');
            $table->dateTime('disabled_at')->nullable()->after('disabled_reason');
            $table->index(['status']);
        });

        // Messages the admin sends to a shopkeeper (shown on their dashboard).
        Schema::create('admin_notices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->text('message');
            $table->string('created_by', 255)->nullable();
            $table->dateTime('read_at')->nullable();
            $table->timestamps();
            $table->index(['business_id', 'read_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_notices');
        Schema::table('businesses', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropColumn(['status', 'disabled_reason', 'disabled_at']);
        });
    }
};

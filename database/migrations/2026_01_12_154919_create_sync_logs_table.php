<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('sync_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('affiliate_provider_id')->constrained()->onDelete('cascade');

            $table->string('status', 50); // started, success, failed, partial
            $table->timestamp('started_at');
            $table->timestamp('completed_at')->nullable();

            // Metrics
            $table->integer('properties_fetched')->default(0);
            $table->integer('properties_created')->default(0);
            $table->integer('properties_updated')->default(0);
            $table->integer('properties_deactivated')->default(0);

            // Error tracking
            $table->text('error_message')->nullable();
            $table->text('error_trace')->nullable();

            $table->timestamp('created_at')->useCurrent();

            // Indexes
            $table->index('affiliate_provider_id');
            $table->index('status');
            $table->index('started_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sync_logs');
    }
};

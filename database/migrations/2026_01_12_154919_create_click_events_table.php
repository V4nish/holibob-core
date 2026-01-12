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
        Schema::create('click_events', function (Blueprint $table) {
            $table->id();

            // What was clicked
            $table->foreignId('property_id')->constrained()->onDelete('cascade');
            $table->foreignId('affiliate_provider_id')->constrained()->onDelete('cascade');

            // Who clicked
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->string('session_id');
            $table->inet('ip_address')->nullable();
            $table->text('user_agent')->nullable();

            // Search context (what led to this click)
            $table->string('search_query', 500)->nullable();
            $table->foreignId('search_location_id')->nullable()->constrained('locations')->onDelete('set null');
            $table->date('search_dates_from')->nullable();
            $table->date('search_dates_to')->nullable();
            $table->smallInteger('search_guests')->nullable();

            // Tracking
            $table->text('tracked_url');
            $table->text('referrer')->nullable();

            // Conversion tracking (updated later if webhook received)
            $table->boolean('converted')->default(false);
            $table->decimal('conversion_value', 10, 2)->nullable();
            $table->timestamp('conversion_date')->nullable();

            $table->timestamp('clicked_at')->useCurrent();

            // Indexes
            $table->index('property_id');
            $table->index('user_id');
            $table->index('session_id');
            $table->index('clicked_at');
            $table->index('converted');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('click_events');
    }
};

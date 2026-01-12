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
        Schema::create('search_logs', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->string('session_id');

            $table->string('query', 500)->nullable();
            $table->foreignId('location_id')->nullable()->constrained()->onDelete('set null');
            $table->date('dates_from')->nullable();
            $table->date('dates_to')->nullable();
            $table->smallInteger('guests')->nullable();

            // Filters applied
            $table->json('filters')->nullable();

            $table->integer('results_count')->nullable();
            $table->foreignId('clicked_result_id')->nullable()->constrained('properties')->onDelete('set null');

            $table->timestamp('searched_at')->useCurrent();

            // Indexes
            $table->index('session_id');
            $table->index('searched_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('search_logs');
    }
};

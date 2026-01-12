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
        Schema::create('locations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained('locations')->onDelete('cascade');
            $table->string('name');
            $table->string('slug');
            $table->string('type', 50); // country, region, area, town, postcode

            // Geographic center
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();

            // Counts for filtering
            $table->integer('property_count')->default(0);

            $table->timestamps();

            // Indexes
            $table->index('parent_id');
            $table->index('slug');
            $table->index('type');
            $table->unique(['parent_id', 'slug', 'type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('locations');
    }
};

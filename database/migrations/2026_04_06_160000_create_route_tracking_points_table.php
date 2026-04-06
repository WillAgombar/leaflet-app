<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('route_tracking_points', function (Blueprint $table) {
            $table->id();
            $table->foreignId('route_assignment_id')->constrained('route_assignments')->cascadeOnDelete();
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->decimal('snapped_latitude', 10, 7)->nullable();
            $table->decimal('snapped_longitude', 10, 7)->nullable();
            $table->decimal('accuracy', 8, 2)->nullable();
            $table->timestamp('captured_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('route_tracking_points');
    }
};

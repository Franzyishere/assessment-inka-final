<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('simulation_session_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('simulation_session_id')->constrained()->cascadeOnDelete();
            $table->string('event_type', 50)->index();
            $table->json('metadata')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamp('occurred_at')->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('simulation_session_events');
    }
};

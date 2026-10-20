<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assessment_access_codes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assessment_participant_id')->unique()->constrained()->cascadeOnDelete();
            $table->text('secret');
            $table->string('secret_hash', 64)->unique();
            $table->string('version', 64);
            $table->timestamp('used_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();
        });
        Schema::create('assessment_archive_grants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('simulation_session_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('assessor_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('assigned_by')->constrained('users');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assessment_archive_grants');
        Schema::dropIfExists('assessment_access_codes');
    }
};

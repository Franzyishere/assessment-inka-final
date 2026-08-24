<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('simulation_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assessment_program_simulation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('assessment_participant_id')->constrained()->cascadeOnDelete();
            $table->string('status')->default('not_started')->index();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->string('session_token', 64)->nullable()->unique();
            $table->string('device_identifier')->nullable();
            $table->string('last_ip_address', 45)->nullable();
            $table->text('last_user_agent')->nullable();
            $table->timestamps();

            $table->unique(
                ['assessment_program_simulation_id', 'assessment_participant_id'],
                'simulation_participant_session_unique'
            );
        });

        Schema::create('simulation_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('simulation_session_id')->constrained()->cascadeOnDelete();
            $table->longText('response_text')->nullable();
            $table->string('original_filename')->nullable();
            $table->string('storage_path')->nullable();
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('file_size')->nullable();
            $table->string('file_checksum', 64)->nullable();
            $table->unsignedSmallInteger('revision')->default(1);
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();

            $table->unique(['simulation_session_id', 'revision'], 'session_submission_revision_unique');
        });

        Schema::create('simulation_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('simulation_session_id')->constrained()->cascadeOnDelete();
            $table->foreignId('assessor_id')->constrained('users')->restrictOnDelete();
            $table->string('recommendation')->nullable()->index();
            $table->longText('assessment_notes')->nullable();
            $table->string('status')->default('draft')->index();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->unique(['simulation_session_id', 'assessor_id'], 'session_assessor_review_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('simulation_reviews');
        Schema::dropIfExists('simulation_submissions');
        Schema::dropIfExists('simulation_sessions');
    }
};

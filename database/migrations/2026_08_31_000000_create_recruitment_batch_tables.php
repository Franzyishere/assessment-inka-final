<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recruitment_batches', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->timestamp('starts_at')->nullable()->index();
            $table->timestamp('ends_at')->nullable()->index();
            $table->string('status')->default('draft')->index();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();
        });

        Schema::create('recruitment_participants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('participant_number')->unique();
            $table->timestamps();
        });

        Schema::create('recruitment_batch_participants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('recruitment_batch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('recruitment_participant_id')->constrained()->cascadeOnDelete();
            $table->string('status')->default('registered')->index();
            $table->timestamp('assigned_at');
            $table->timestamps();

            $table->unique(
                ['recruitment_batch_id', 'recruitment_participant_id'],
                'recruitment_batch_participant_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recruitment_batch_participants');
        Schema::dropIfExists('recruitment_participants');
        Schema::dropIfExists('recruitment_batches');
    }
};

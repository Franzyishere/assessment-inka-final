<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assessment_invitations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assessment_participant_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('email');
            $table->string('token_hash', 64)->unique();
            $table->timestamp('valid_from');
            $table->timestamp('expires_at');
            $table->timestamp('revoked_at')->nullable();
            $table->timestamp('last_login_at')->nullable();
            $table->foreignId('sent_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
        Schema::create('assessment_otp_challenges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assessment_invitation_id')->constrained()->cascadeOnDelete();
            $table->string('code_hash');
            $table->string('browser_hash', 64);
            $table->timestamp('expires_at');
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->timestamp('consumed_at')->nullable();
            $table->timestamp('invalidated_at')->nullable();
            $table->timestamps();
        });
        Schema::create('assessment_email_deliveries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assessment_invitation_id')->constrained()->cascadeOnDelete();
            $table->string('kind', 20);
            $table->string('status', 20)->default('queued');
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assessment_email_deliveries');
        Schema::dropIfExists('assessment_otp_challenges');
        Schema::dropIfExists('assessment_invitations');
    }
};

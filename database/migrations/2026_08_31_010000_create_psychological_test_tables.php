<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('psychological_tests', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });

        Schema::create('psychological_test_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('psychological_test_id')->constrained()->cascadeOnDelete();
            $table->string('version');
            $table->unsignedSmallInteger('item_count')->default(90);
            $table->unsignedSmallInteger('duration_minutes')->nullable();
            $table->unsignedSmallInteger('expected_role_total')->default(45);
            $table->unsignedSmallInteger('expected_need_total')->default(45);
            $table->string('status')->default('draft')->index();
            $table->timestamp('validated_at')->nullable();
            $table->foreignId('validated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('published_at')->nullable();
            $table->foreignId('published_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['psychological_test_id', 'version'], 'psychological_test_version_unique');
        });

        Schema::create('papi_dimensions', function (Blueprint $table) {
            $table->id();
            $table->char('code', 1)->unique();
            $table->string('category')->index();
            $table->string('name');
            $table->text('description')->nullable();
            $table->unsignedSmallInteger('display_order');
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });

        Schema::create('psychological_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('psychological_test_version_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('number');
            $table->boolean('is_required')->default(true);
            $table->timestamps();

            $table->unique(['psychological_test_version_id', 'number'], 'psychological_version_question_unique');
        });

        Schema::create('psychological_question_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('psychological_question_id')->constrained()->cascadeOnDelete();
            $table->char('code', 1);
            $table->text('statement');
            $table->unsignedSmallInteger('display_order');
            $table->timestamps();

            $table->unique(['psychological_question_id', 'code'], 'psychological_question_option_unique');
        });

        Schema::create('papi_scoring_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('psychological_question_option_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('papi_dimension_id')->constrained()->restrictOnDelete();
            $table->unsignedSmallInteger('weight')->default(1);
            $table->timestamps();
        });

        Schema::create('recruitment_batch_psychological_tests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('recruitment_batch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('psychological_test_version_id')->constrained()->restrictOnDelete();
            $table->timestamp('available_from')->nullable();
            $table->timestamp('available_until')->nullable();
            $table->unsignedSmallInteger('duration_minutes')->nullable();
            $table->unsignedSmallInteger('max_attempts')->default(1);
            $table->boolean('is_active')->default(false)->index();
            $table->timestamps();

            $table->unique(['recruitment_batch_id', 'psychological_test_version_id'], 'batch_psychological_test_unique');
        });

        Schema::create('psychological_test_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('recruitment_batch_psychological_test_id')->constrained()->cascadeOnDelete();
            $table->foreignId('recruitment_participant_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('attempt_number')->default(1);
            $table->string('status')->default('not_started')->index();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('last_activity_at')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->unsignedSmallInteger('last_question_number')->nullable();
            $table->string('session_token', 64)->nullable()->unique();
            $table->timestamps();

            $table->unique(
                ['recruitment_batch_psychological_test_id', 'recruitment_participant_id', 'attempt_number'],
                'psychological_participant_attempt_unique'
            );
        });

        Schema::create('psychological_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('psychological_test_session_id')->constrained()->cascadeOnDelete();
            $table->foreignId('psychological_question_id')->constrained()->restrictOnDelete();
            $table->foreignId('psychological_question_option_id')->constrained()->restrictOnDelete();
            $table->timestamp('answered_at');
            $table->timestamps();

            $table->unique(['psychological_test_session_id', 'psychological_question_id'], 'psychological_session_answer_unique');
        });

        Schema::create('psychological_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('psychological_test_session_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('status')->default('pending')->index();
            $table->unsignedSmallInteger('total_role')->nullable();
            $table->unsignedSmallInteger('total_need')->nullable();
            $table->string('scoring_version');
            $table->text('invalid_reason')->nullable();
            $table->timestamp('scored_at')->nullable();
            $table->timestamps();
        });

        Schema::create('psychological_result_scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('psychological_result_id')->constrained()->cascadeOnDelete();
            $table->foreignId('papi_dimension_id')->constrained()->restrictOnDelete();
            $table->unsignedSmallInteger('score');
            $table->timestamps();

            $table->unique(['psychological_result_id', 'papi_dimension_id'], 'psychological_result_dimension_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('psychological_result_scores');
        Schema::dropIfExists('psychological_results');
        Schema::dropIfExists('psychological_answers');
        Schema::dropIfExists('psychological_test_sessions');
        Schema::dropIfExists('recruitment_batch_psychological_tests');
        Schema::dropIfExists('papi_scoring_rules');
        Schema::dropIfExists('psychological_question_options');
        Schema::dropIfExists('psychological_questions');
        Schema::dropIfExists('papi_dimensions');
        Schema::dropIfExists('psychological_test_versions');
        Schema::dropIfExists('psychological_tests');
    }
};

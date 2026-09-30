<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Preserve programs already archived by the previous implementation.
        Schema::table('assessment_programs', fn (Blueprint $table) => $table->renameColumn('deleted_at', 'archived_at'));
    }

    public function down(): void
    {
        Schema::table('assessment_programs', fn (Blueprint $table) => $table->renameColumn('archived_at', 'deleted_at'));
    }
};

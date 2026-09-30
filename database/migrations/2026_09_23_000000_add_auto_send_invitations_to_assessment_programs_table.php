<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('assessment_programs', function (Blueprint $table) {
            $table->boolean('auto_send_invitations')->default(true)->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('assessment_programs', function (Blueprint $table) {
            $table->dropColumn('auto_send_invitations');
        });
    }
};


<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('employee_number')->nullable()->unique()->after('email');
            $table->string('hris_employee_id')->nullable()->unique()->after('employee_number');
            $table->string('identity_source')->default('manual')->index()->after('hris_employee_id');
            $table->timestamp('hris_synced_at')->nullable()->after('identity_source');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['employee_number']);
            $table->dropUnique(['hris_employee_id']);
            $table->dropIndex(['identity_source']);
            $table->dropColumn(['employee_number', 'hris_employee_id', 'identity_source', 'hris_synced_at']);
        });
    }
};

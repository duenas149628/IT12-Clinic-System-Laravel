<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('users')) {
            return;
        }

        if (Schema::hasColumn('users', 'id') && ! Schema::hasColumn('users', 'user_id')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->renameColumn('id', 'user_id');
            });
        }

        if (! Schema::hasColumn('users', 'role')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->enum('role', ['patient', 'staff'])->default('patient');
            });
        }

        if (! Schema::hasColumn('users', 'must_change_password')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->boolean('must_change_password')->default(false);
            });
        }
    }

    public function down(): void
    {
        // Keep the users table compatible with the application if this migration is rolled back.
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('announcements', function (Blueprint $table) {
            if (!Schema::hasColumn('announcements', 'target_type')) {
                $table->enum('target_type', ['all', 'organization', 'course'])
                      ->default('all')
                      ->after('organization_id');
            }
            if (!Schema::hasColumn('announcements', 'course_id')) {
                $table->foreignId('course_id')
                      ->nullable()
                      ->after('target_type')
                      ->constrained('courses')
                      ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('announcements', function (Blueprint $table) {
            if (Schema::hasColumn('announcements', 'course_id')) {
                $table->dropForeign(['course_id']);
                $table->dropColumn('course_id');
            }
            if (Schema::hasColumn('announcements', 'target_type')) {
                $table->dropColumn('target_type');
            }
        });
    }
};

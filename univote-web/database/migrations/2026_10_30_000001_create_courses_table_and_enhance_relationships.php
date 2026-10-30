<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── 1. Create courses table ────────────────────────────────────────────
        if (!Schema::hasTable('courses')) {
            Schema::create('courses', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('code', 20);
                $table->text('description')->nullable();
                $table->foreignId('organization_id')
                      ->nullable()
                      ->constrained()
                      ->nullOnDelete();
                $table->enum('status', ['assigned', 'pending'])->default('pending');
                $table->timestamps();
            });
        }

        // ── 2. Add course_id to voters ────────────────────────────────────────
        if (!Schema::hasColumn('voters', 'course_id')) {
            Schema::table('voters', function (Blueprint $table) {
                $table->foreignId('course_id')
                      ->nullable()
                      ->after('course')
                      ->constrained('courses')
                      ->nullOnDelete();
            });
        }

        // ── 3. Add election_type + course_id to elections ─────────────────────
        Schema::table('elections', function (Blueprint $table) {
            if (!Schema::hasColumn('elections', 'election_type')) {
                $table->enum('election_type', ['campus', 'organization', 'course'])
                      ->default('campus')
                      ->after('organization_id');
            }
            if (!Schema::hasColumn('elections', 'course_id')) {
                $table->foreignId('course_id')
                      ->nullable()
                      ->after('election_type')
                      ->constrained('courses')
                      ->nullOnDelete();
            }
        });

        // ── 4. Add target_type + course_id to announcements ───────────────────
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
        // Remove announcements columns
        Schema::table('announcements', function (Blueprint $table) {
            if (Schema::hasColumn('announcements', 'course_id')) {
                $table->dropForeign(['course_id']);
                $table->dropColumn('course_id');
            }
            if (Schema::hasColumn('announcements', 'target_type')) {
                $table->dropColumn('target_type');
            }
        });

        // Remove elections columns
        Schema::table('elections', function (Blueprint $table) {
            if (Schema::hasColumn('elections', 'course_id')) {
                $table->dropForeign(['course_id']);
                $table->dropColumn('course_id');
            }
            if (Schema::hasColumn('elections', 'election_type')) {
                $table->dropColumn('election_type');
            }
        });

        // Remove voters course_id
        Schema::table('voters', function (Blueprint $table) {
            if (Schema::hasColumn('voters', 'course_id')) {
                $table->dropForeign(['course_id']);
                $table->dropColumn('course_id');
            }
        });

        // Drop courses table
        Schema::dropIfExists('courses');
    }
};


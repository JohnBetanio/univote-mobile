<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('audit_logs')) {
            Schema::create('audit_logs', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id')->nullable()->index();
                $table->string('user_type')->nullable()->index();
                $table->string('actor_name')->nullable();
                $table->string('action')->index();
                $table->string('model_type')->nullable()->index();
                $table->unsignedBigInteger('model_id')->nullable()->index();
                $table->json('old_values')->nullable();
                $table->json('new_values')->nullable();
                $table->string('entity_type')->nullable()->index();
                $table->unsignedBigInteger('entity_id')->nullable()->index();
                $table->text('description')->nullable();
                $table->json('metadata')->nullable();
                $table->string('ip_address', 45)->nullable();
                $table->text('user_agent')->nullable();
                $table->string('platform', 30)->nullable()->index();
                $table->string('severity', 20)->default('info');
                $table->timestamps();
            });
        } else {
            Schema::table('audit_logs', function (Blueprint $table) {
                if (!Schema::hasColumn('audit_logs', 'actor_name')) {
                    $table->string('actor_name')->nullable()->after('user_type');
                }
                if (!Schema::hasColumn('audit_logs', 'model_type')) {
                    $table->string('model_type')->nullable()->after('action')->index();
                }
                if (!Schema::hasColumn('audit_logs', 'model_id')) {
                    $table->unsignedBigInteger('model_id')->nullable()->after('model_type')->index();
                }
                if (!Schema::hasColumn('audit_logs', 'old_values')) {
                    $table->json('old_values')->nullable()->after('model_id');
                }
                if (!Schema::hasColumn('audit_logs', 'new_values')) {
                    $table->json('new_values')->nullable()->after('old_values');
                }
                if (!Schema::hasColumn('audit_logs', 'platform')) {
                    $table->string('platform', 30)->nullable()->after('user_agent')->index();
                }
                if (!Schema::hasColumn('audit_logs', 'updated_at')) {
                    $table->timestamp('updated_at')->nullable()->after('created_at');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('audit_logs')) {
            Schema::dropIfExists('audit_logs');
        }
    }
};

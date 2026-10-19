<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('monthly_submissions')) {
            Schema::create('monthly_submissions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('department_id')->constrained('departments')->cascadeOnDelete();
                $table->string('year', 50);
                $table->string('month', 50);
                $table->dateTime('deadline_at')->nullable();
                $table->dateTime('submitted_at')->nullable();
                $table->enum('status', ['DRAFT', 'SUBMITTED', 'LATE'])->default('DRAFT');
                $table->enum('submission_channel', ['WEB', 'TELEGRAM'])->default('WEB');
                $table->foreignId('submitted_by')->nullable()->constrained('users')->nullOnDelete();
                $table->enum('approval_status', ['PENDING', 'APPROVED', 'NEEDS_REVISION'])->default('PENDING');
                $table->text('gm_feedback')->nullable();
                $table->dateTime('gm_feedback_at')->nullable();
                $table->text('remarks')->nullable();
                $table->timestamps();

                $table->unique(['department_id', 'year', 'month'], 'uk_dept_monthly_submission');
            });
        }

        if (Schema::hasTable('users') && !Schema::hasColumn('users', 'remember_token')) {
            Schema::table('users', function (Blueprint $table) {
                $table->rememberToken()->after('password_hash');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('monthly_submissions');
        if (Schema::hasTable('users') && Schema::hasColumn('users', 'remember_token')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropRememberToken();
            });
        }
    }
};

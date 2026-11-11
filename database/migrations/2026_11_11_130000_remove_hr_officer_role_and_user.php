<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Delete user 'hr_officer' or users with role 'hr' / 'hr_officer'
        $hrRole = DB::table('roles')->whereIn('name', ['hr', 'hr_officer'])->first();
        if ($hrRole) {
            DB::table('users')->where('role_id', $hrRole->id)->delete();
            DB::table('roles')->where('id', $hrRole->id)->delete();
        }
        DB::table('users')->where('username', 'hr_officer')->delete();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No-op
    }
};

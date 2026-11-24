<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Teams may only be led by users with the team_leader role: promote the employees who already lead one.
     */
    public function up(): void
    {
        DB::table('users')
            ->where('role', 'employee')
            ->whereIn('id', DB::table('teams')->whereNotNull('leader_id')->select('leader_id'))
            ->update(['role' => 'team_leader']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('users')->where('role', 'team_leader')->update(['role' => 'employee']);
    }
};

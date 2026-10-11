<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admin_role_migration_users', function (Blueprint $table): void {
            $table->unsignedBigInteger('user_id')->primary();
        });

        DB::table('admin_role_migration_users')->insertUsing(
            ['user_id'],
            DB::table('users')->select('id')->where('role', 'admin'),
        );

        DB::table('users')->where('role', 'admin')->update(['role' => 'kepala_tu']);
    }

    public function down(): void
    {
        DB::table('users')
            ->whereIn('id', DB::table('admin_role_migration_users')->select('user_id'))
            ->update(['role' => 'admin']);

        Schema::dropIfExists('admin_role_migration_users');
    }
};

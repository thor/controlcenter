<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Create new table (safe for SQLite and MySQL)
        Schema::create('role_user', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('role');
            $table->unsignedInteger('area_id')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'role', 'area_id']);
        });

        // 2. Migrate data from old permissions table
        if (Schema::hasTable('permissions')) {
            $permissions = DB::table('permissions')->get();
            foreach ($permissions as $p) {
                $role = null;
                $area_id = $p->area_id;

                if ($p->group_id == 1) {
                    $role = 'admin';
                    $area_id = null;
                } elseif ($p->group_id == 2) {
                    $role = 'moderator';
                } elseif ($p->group_id == 3) {
                    $role = 'mentor';
                } elseif ($p->group_id == 4) {
                    $role = 'buddy';
                } else {
                    continue;
                } // Unknown group

                DB::table('role_user')->insert([
                    'user_id' => $p->user_id,
                    'role' => $role,
                    'area_id' => $area_id,
                    'created_at' => $p->created_at,
                    'updated_at' => $p->updated_at,
                ]);
            }
            Schema::dropIfExists('permissions');
        }

        // 3. Drop old tables
        Schema::dropIfExists('groups');
    }

    public function down(): void
    {
        Schema::dropIfExists('role_user');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The original create_rooms_table / create_tenants_table migrations ran while
     * those files only declared id + timestamps, so the real columns were never
     * created. This migration adds them to the existing tables.
     */
    public function up(): void
    {
        Schema::table('rooms', function (Blueprint $table) {
            if (!Schema::hasColumn('rooms', 'room_number')) {
                $table->string('room_number', 20)->after('id');
            }
            if (!Schema::hasColumn('rooms', 'room_type')) {
                $table->string('room_type', 50)->default('Standard')->after('room_number');
            }
            if (!Schema::hasColumn('rooms', 'price')) {
                $table->decimal('price', 10, 2)->default(0)->after('room_type');
            }
            if (!Schema::hasColumn('rooms', 'status')) {
                $table->string('status', 20)->default('available')->after('price');
            }
        });

        $hasUnique = collect(DB::select("SHOW INDEX FROM rooms WHERE Key_name = 'rooms_room_number_unique'"))->isNotEmpty();
        if (!$hasUnique) {
            DB::statement('ALTER TABLE rooms ADD UNIQUE INDEX rooms_room_number_unique (room_number)');
        }

        Schema::table('tenants', function (Blueprint $table) {
            if (!Schema::hasColumn('tenants', 'name')) {
                $table->string('name', 100)->nullable()->after('id');
            }
            if (!Schema::hasColumn('tenants', 'email')) {
                $table->string('email', 120)->nullable()->after('name');
            }
            if (!Schema::hasColumn('tenants', 'phone')) {
                $table->string('phone', 30)->nullable()->after('email');
            }
            if (!Schema::hasColumn('tenants', 'room_id')) {
                $table->unsignedBigInteger('room_id')->nullable()->after('phone');
            }
        });

        // Rows created by the broken form contain no data at all.
        DB::table('tenants')
            ->whereNull('name')
            ->whereNull('email')
            ->whereNull('phone')
            ->delete();

        // Foreign key added after cleanup so orphaned rows cannot block it.
        $hasFk = collect(DB::select("SELECT COUNT(*) AS c FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tenants' AND COLUMN_NAME = 'room_id' AND REFERENCED_TABLE_NAME IS NOT NULL"))->first()->c ?? 0;
        if ($hasFk == 0) {
            Schema::table('tenants', function (Blueprint $table) {
                $table->foreign('room_id')->references('id')->on('rooms')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            if (Schema::hasColumn('tenants', 'room_id')) {
                $table->dropForeign(['room_id']);
                $table->dropColumn('room_id');
            }
            foreach (['name', 'email', 'phone'] as $column) {
                if (Schema::hasColumn('tenants', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::table('rooms', function (Blueprint $table) {
            $hasUnique = collect(DB::select("SHOW INDEX FROM rooms WHERE Key_name = 'rooms_room_number_unique'"))->isNotEmpty();
            if ($hasUnique) {
                $table->dropUnique('rooms_room_number_unique');
            }
            foreach (['room_number', 'room_type', 'price', 'status'] as $column) {
                if (Schema::hasColumn('rooms', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};

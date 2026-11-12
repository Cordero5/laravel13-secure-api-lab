<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * How many tenants a room can hold. Existing rooms default to 1 so the
     * previous one-tenant-per-room behaviour is preserved.
     */
    public function up(): void
    {
        if (!Schema::hasColumn('rooms', 'capacity')) {
            Schema::table('rooms', function (Blueprint $table) {
                $table->unsignedSmallInteger('capacity')->default(1)->after('price');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('rooms', 'capacity')) {
            Schema::table('rooms', function (Blueprint $table) {
                $table->dropColumn('capacity');
            });
        }
    }
};

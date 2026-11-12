<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The payments, check_ins, house_rules and announcements tables were created
     * by stub migrations that only declared id + timestamps, so none of the real
     * columns exist. This adds them, plus the tenant-to-account link the module
     * describes and the device token needed for announcement push notifications.
     */
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            if (!Schema::hasColumn('payments', 'tenant_id')) {
                $table->unsignedBigInteger('tenant_id')->nullable()->after('id');
            }
            if (!Schema::hasColumn('payments', 'amount')) {
                $table->decimal('amount', 10, 2)->default(0)->after('tenant_id');
            }
            if (!Schema::hasColumn('payments', 'payment_date')) {
                $table->date('payment_date')->nullable()->after('amount');
            }
            if (!Schema::hasColumn('payments', 'status')) {
                $table->string('status', 20)->default('pending')->after('payment_date');
            }
            if (!Schema::hasColumn('payments', 'reference_number')) {
                $table->string('reference_number', 40)->nullable()->after('status');
            }
        });

        Schema::table('check_ins', function (Blueprint $table) {
            if (!Schema::hasColumn('check_ins', 'tenant_id')) {
                $table->unsignedBigInteger('tenant_id')->nullable()->after('id');
            }
            if (!Schema::hasColumn('check_ins', 'room_id')) {
                $table->unsignedBigInteger('room_id')->nullable()->after('tenant_id');
            }
            if (!Schema::hasColumn('check_ins', 'check_in_date')) {
                $table->dateTime('check_in_date')->nullable()->after('room_id');
            }
            if (!Schema::hasColumn('check_ins', 'check_out_date')) {
                $table->dateTime('check_out_date')->nullable()->after('check_in_date');
            }
            if (!Schema::hasColumn('check_ins', 'status')) {
                $table->string('status', 20)->default('checked_in')->after('check_out_date');
            }
        });

        Schema::table('house_rules', function (Blueprint $table) {
            if (!Schema::hasColumn('house_rules', 'title')) {
                $table->string('title', 150)->nullable()->after('id');
            }
            if (!Schema::hasColumn('house_rules', 'description')) {
                $table->text('description')->nullable()->after('title');
            }
            if (!Schema::hasColumn('house_rules', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('description');
            }
        });

        Schema::table('announcements', function (Blueprint $table) {
            if (!Schema::hasColumn('announcements', 'title')) {
                $table->string('title', 150)->nullable()->after('id');
            }
            if (!Schema::hasColumn('announcements', 'content')) {
                $table->text('content')->nullable()->after('title');
            }
            if (!Schema::hasColumn('announcements', 'published_at')) {
                $table->dateTime('published_at')->nullable()->after('content');
            }
        });

        Schema::table('tenants', function (Blueprint $table) {
            if (!Schema::hasColumn('tenants', 'user_id')) {
                $table->unsignedBigInteger('user_id')->nullable()->after('id');
            }
        });

        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'role')) {
                $table->string('role', 20)->default('tenant')->after('email');
            }
            if (!Schema::hasColumn('users', 'fcm_token')) {
                $table->string('fcm_token', 255)->nullable()->after('password');
            }
        });

        // Foreign keys are added last, once every referenced column exists.
        $this->link('payments', 'tenant_id', 'tenants', 'id');
        $this->link('check_ins', 'tenant_id', 'tenants', 'id');
        $this->link('check_ins', 'room_id', 'rooms', 'id');
        $this->link('tenants', 'user_id', 'users', 'id');
    }

    /**
     * Attach a nullable foreign key unless one is already present.
     */
    private function link(string $table, string $column, string $references, string $on): void
    {
        $existing = collect(DB::select(
            "SELECT COUNT(*) AS c FROM information_schema.KEY_COLUMN_USAGE
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?
               AND REFERENCED_TABLE_NAME IS NOT NULL",
            [$table, $column]
        ))->first()->c ?? 0;

        if ((int) $existing === 0) {
            Schema::table($table, function (Blueprint $blueprint) use ($column, $references, $on) {
                $blueprint->foreign($column)->references($on)->on($references)->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        foreach ([
            'payments'      => ['tenant_id', 'amount', 'payment_date', 'status', 'reference_number'],
            'check_ins'     => ['tenant_id', 'room_id', 'check_in_date', 'check_out_date', 'status'],
            'house_rules'   => ['title', 'description', 'is_active'],
            'announcements' => ['title', 'content', 'published_at'],
            'tenants'       => ['user_id'],
            'users'         => ['role', 'fcm_token'],
        ] as $table => $columns) {
            Schema::table($table, function (Blueprint $blueprint) use ($table, $columns) {
                foreach ($columns as $column) {
                    if (Schema::hasColumn($table, $column)) {
                        $blueprint->dropColumn($column);
                    }
                }
            });
        }
    }
};

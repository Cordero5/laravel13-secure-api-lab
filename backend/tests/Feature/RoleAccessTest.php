<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase as BaseTestCase;

class RoleAccessTest extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
            'database.connections.sqlite.foreign_key_constraints' => false,
        ]);
        DB::purge('sqlite');

        Schema::connection('sqlite')->create('users', function ($table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->string('role')->default('tenant');
            $table->string('remember_token')->nullable();
            $table->timestamps();
        });

        Schema::connection('sqlite')->create('rooms', function ($table) {
            $table->id();
            $table->string('room_number');
            $table->string('room_type');
            $table->decimal('price', 10, 2)->default(0);
            $table->unsignedInteger('capacity')->default(1);
            $table->string('status')->default('available');
            $table->timestamps();
        });

        Schema::connection('sqlite')->create('tenants', function ($table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('name');
            $table->string('email');
            $table->string('phone')->nullable();
            $table->unsignedBigInteger('room_id')->nullable();
            $table->timestamps();
        });

        Schema::connection('sqlite')->create('payments', function ($table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->nullable();
            $table->decimal('amount', 10, 2)->default(0);
            $table->date('payment_date')->nullable();
            $table->string('status')->default('pending');
            $table->timestamps();
        });

        Schema::connection('sqlite')->create('check_ins', function ($table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->nullable();
            $table->unsignedBigInteger('room_id')->nullable();
            $table->dateTime('check_in_date')->nullable();
            $table->dateTime('check_out_date')->nullable();
            $table->string('status')->default('checked_in');
            $table->timestamps();
        });

        Schema::connection('sqlite')->create('personal_access_tokens', function ($table) {
            $table->id();
            $table->morphs('tokenable');
            $table->string('name');
            $table->string('token', 64)->unique();
            $table->text('abilities')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });
    }

    public function test_guests_are_redirected_to_the_matching_login_page(): void
    {
        $this->get('/admin/dashboard')->assertRedirect(route('admin.login'));
        $this->get('/tenant/dashboard')->assertRedirect(route('tenant.login'));
    }

    public function test_tenant_login_stays_authenticated_on_direct_dashboard_request(): void
    {
        User::create([
            'name' => 'Tenant User',
            'email' => 'tenant@example.test',
            'password' => Hash::make('tenant-password'),
            'role' => 'tenant',
        ]);

        $this->post('/tenant/login', [
            'email' => 'tenant@example.test',
            'password' => 'tenant-password',
        ])->assertRedirect(route('tenant.dashboard'));

        $this->get('/tenant/dashboard')->assertOk();
    }

    public function test_tenant_is_blocked_from_admin_pages_and_actions(): void
    {
        $tenant = User::create([
            'name' => 'Tenant User',
            'email' => 'tenant@example.test',
            'password' => Hash::make('tenant-password'),
            'role' => 'tenant',
        ]);

        $this->actingAs($tenant)->get('/admin/dashboard')->assertForbidden();
        $this->actingAs($tenant)->post('/admin/logout')->assertForbidden();
    }

    public function test_admin_api_route_returns_401_to_guests_and_403_to_tenants(): void
    {
        $this->getJson('/api/admin/overview')->assertUnauthorized();

        $tenant = User::create([
            'name' => 'Tenant User',
            'email' => 'tenant@example.test',
            'password' => Hash::make('tenant-password'),
            'role' => 'tenant',
        ]);

        $this->actingAs($tenant, 'sanctum')
            ->getJson('/api/admin/overview')
            ->assertForbidden();
    }

    public function test_both_roles_can_view_the_shared_house_rooms_page(): void
    {
        $roomId = DB::table('rooms')->insertGetId([
            'room_number' => '101',
            'room_type' => 'Single',
            'price' => 1000,
            'capacity' => 2,
            'status' => 'available',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('tenants')->insert([
            'name' => 'Occupant',
            'email' => 'occupant@example.test',
            'room_id' => $roomId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        foreach (['admin', 'tenant'] as $role) {
            $user = User::create([
                'name' => ucfirst($role).' User',
                'email' => $role.'@example.test',
                'password' => Hash::make('password'),
                'role' => $role,
            ]);

            $this->actingAs($user)
                ->get('/house/rooms')
                ->assertOk()
                ->assertSee('101')
                ->assertSee('1 slot(s) available');
        }
    }

    public function test_deleting_a_tenant_removes_its_login_account_and_keeps_history(): void
    {
        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@example.test',
            'password' => Hash::make('password'),
            'role' => 'admin',
        ]);
        $tenantUser = User::create([
            'name' => 'Tenant User',
            'email' => 'tenant@example.test',
            'password' => Hash::make('password'),
            'role' => 'tenant',
        ]);
        $tenantId = DB::table('tenants')->insertGetId([
            'user_id' => $tenantUser->id,
            'name' => 'Tenant User',
            'email' => 'tenant@example.test',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('payments')->insert([
            'tenant_id' => $tenantId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('check_ins')->insert([
            'tenant_id' => $tenantId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $tenantUser->createToken('test-token');

        $this->actingAs($admin)
            ->delete(route('admin.tenants.destroy', $tenantId))
            ->assertRedirect(route('admin.tenants.index'));

        $this->assertDatabaseMissing('tenants', ['id' => $tenantId]);
        $this->assertDatabaseMissing('users', ['id' => $tenantUser->id]);
        $this->assertDatabaseMissing('personal_access_tokens', [
            'tokenable_id' => $tenantUser->id,
            'tokenable_type' => User::class,
        ]);
        $this->assertDatabaseHas('payments', ['tenant_id' => $tenantId]);
        $this->assertDatabaseHas('check_ins', ['tenant_id' => $tenantId]);
        $this->assertDatabaseHas('users', ['id' => $admin->id]);
    }
}

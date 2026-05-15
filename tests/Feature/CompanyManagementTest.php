<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Laravel\Sanctum\Sanctum;

class CompanyManagementTest extends TestCase
{
    use RefreshDatabase;

    protected $superAdmin;
    protected $company;

    protected function setUp(): void
    {
        parent::setUp();

        $this->superAdmin = User::create([
            'name' => 'Super Admin',
            'email' => 'admin@accountify.com',
            'password' => bcrypt('password'),
            'type' => 'super admin',
        ]);

        $this->company = User::create([
            'name' => 'Test Company',
            'email' => 'company@test.com',
            'password' => bcrypt('password'),
            'type' => 'company',
            'is_active' => 1,
            'is_enable_login' => 1,
        ]);
    }

    public function test_super_admin_can_suspend_company()
    {
        Sanctum::actingAs($this->superAdmin);

        $response = $this->postJson("/api/companies/{$this->company->id}/suspend");

        $response->assertStatus(200);
        $this->assertDatabaseHas('users', [
            'id' => $this->company->id,
            'is_active' => 0,
            'is_enable_login' => 0,
        ]);
    }

    public function test_super_admin_can_activate_company()
    {
        $this->company->update(['is_active' => 0, 'is_enable_login' => 0]);
        Sanctum::actingAs($this->superAdmin);

        $response = $this->postJson("/api/companies/{$this->company->id}/activate");

        $response->assertStatus(200);
        $this->assertDatabaseHas('users', [
            'id' => $this->company->id,
            'is_active' => 1,
            'is_enable_login' => 1,
        ]);
    }

    public function test_super_admin_can_soft_delete_company()
    {
        Sanctum::actingAs($this->superAdmin);

        $response = $this->deleteJson("/api/companies/{$this->company->id}");

        $response->assertStatus(200);
        $this->assertSoftDeleted('users', [
            'id' => $this->company->id,
        ]);
    }

    public function test_super_admin_can_impersonate_company_admin()
    {
        Sanctum::actingAs($this->superAdmin);

        $response = $this->postJson("/api/companies/{$this->company->id}/impersonate");

        $response->assertStatus(200);
        $response->assertJsonStructure(['token', 'user']);
    }

    public function test_non_super_admin_cannot_manage_companies()
    {
        $anotherUser = User::create([
            'name' => 'Regular User',
            'email' => 'user@test.com',
            'password' => bcrypt('password'),
            'type' => 'user',
        ]);

        Sanctum::actingAs($anotherUser);

        $this->postJson("/api/companies/{$this->company->id}/suspend")->assertStatus(403);
        $this->postJson("/api/companies/{$this->company->id}/activate")->assertStatus(403);
        $this->deleteJson("/api/companies/{$this->company->id}")->assertStatus(403);
        $this->postJson("/api/companies/{$this->company->id}/impersonate")->assertStatus(403);
    }
}

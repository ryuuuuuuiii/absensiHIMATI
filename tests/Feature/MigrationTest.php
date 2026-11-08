<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Meeting;
use App\Models\Member;
use App\Models\Setting;
use Tests\TestCase;

class MigrationTest extends TestCase
{
    public function test_login_page_is_accessible(): void
    {
        $response = $this->get('/login');
        $response->assertStatus(200);
        $response->assertSee('Masuk Akun Admin');
    }

    public function test_admin_authentication_and_dashboard(): void
    {
        $response = $this->post('/login', [
            'email' => 'admin@himati.id',
            'password' => 'admin123',
        ]);

        $response->assertRedirect('/dashboard');

        $admin = Admin::where('email', 'admin@himati.id')->first();
        $this->actingAs($admin);

        $dash = $this->get('/dashboard');
        $dash->assertStatus(200);
        $dash->assertSee('Total Pengurus');
    }

    public function test_admin_pages_accessible(): void
    {
        $admin = Admin::where('email', 'admin@himati.id')->first();
        $this->actingAs($admin);

        $this->get('/members')->assertStatus(200);
        $this->get('/members/create')->assertStatus(200);
        $this->get('/members/generate-qr')->assertStatus(200);
        $this->get('/meetings')->assertStatus(200);
        $this->get('/meetings/create')->assertStatus(200);
        $this->get('/events')->assertStatus(200);
        $this->get('/reports')->assertStatus(200);

        $settings = $this->get('/settings');
        $settings->assertStatus(200);
        $settings->assertDontSee('Kelola Logo Instansi');
        $settings->assertDontSee('Ganti Logo');

        $this->assertNotNull(Setting::getLogo1Url());
        $this->assertNotNull(Setting::getLogo2Url());
    }

    public function test_public_routes_accessible(): void
    {
        $this->get('/rapat')->assertRedirect(route('login'));

        $member = Member::first();
        if ($member) {
            $this->actingAs($member, 'member')->get('/rapat')->assertStatus(200);
        }

        $this->get('/my-qr')->assertStatus(200);

        $meeting = Meeting::first();
        if ($meeting) {
            $this->get("/scan/{$meeting->unique_link}")->assertStatus(200);
            $this->get("/attend/{$meeting->unique_link}")->assertStatus(200);
            $this->get("/absent/{$meeting->absent_link}")->assertStatus(200);
            $this->get("/hasil/{$meeting->unique_link}")->assertStatus(200);
        }
    }

    public function test_legacy_redirects(): void
    {
        $meeting = Meeting::first();
        if ($meeting) {
            $this->get("/meeting/detail.php?id={$meeting->id}")
                ->assertRedirect("/meetings/{$meeting->id}");
            $this->get("/meeting/scan.php?id={$meeting->id}")
                ->assertRedirect("/meetings/{$meeting->id}/scan");
        }
    }
}

<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Attendance;
use App\Models\Meeting;
use App\Models\Member;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

class MemberPortalTest extends TestCase
{
    use DatabaseTransactions;

    public function test_portal_login_page_redirects_to_unified_login(): void
    {
        $this->get('/portal/login')
            ->assertRedirect(route('login'));
    }

    public function test_member_can_login_to_portal_and_see_dashboard(): void
    {
        $member = $this->createMember();

        $response = $this->post('/portal/login', [
            'email' => $member->email,
            'password' => 'password123',
        ]);

        $response->assertRedirect(route('portal.dashboard'));
        $this->assertAuthenticatedAs($member, 'member');
        $this->assertNotNull($member->fresh()->last_login_at);

        $this->get('/portal')
            ->assertOk()
            ->assertSee($member->nama_lengkap)
            ->assertSee('Riwayat Absensi Terbaru');
    }

    public function test_member_can_login_via_unified_login_and_redirected_to_portal(): void
    {
        $member = $this->createMember();

        $response = $this->post('/login', [
            'email' => $member->email,
            'password' => 'password123',
        ]);

        $response->assertRedirect(route('portal.dashboard'));
        $this->assertAuthenticatedAs($member, 'member');
        $this->assertNotNull($member->fresh()->last_login_at);
    }

    public function test_admin_can_login_via_unified_login_and_redirected_to_dashboard(): void
    {
        $admin = Admin::where('email', 'admin@himati.id')->first();
        if (! $admin) {
            $admin = Admin::create([
                'nama' => 'Administrator',
                'email' => 'admin@himati.id',
                'password' => Hash::make('admin123'),
            ]);
        }

        $response = $this->post('/login', [
            'email' => 'admin@himati.id',
            'password' => 'admin123',
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($admin, 'web');
    }

    public function test_member_cannot_login_with_wrong_password(): void
    {
        $member = $this->createMember();

        $this->from('/portal/login')
            ->post('/portal/login', [
                'email' => $member->email,
                'password' => 'wrong-password',
            ])
            ->assertRedirect('/portal/login')
            ->assertSessionHasErrors('email');

        $this->assertGuest('member');
    }

    public function test_member_without_account_cannot_login(): void
    {
        $member = $this->createMember(['password' => null]);

        $this->from('/portal/login')
            ->post('/portal/login', [
                'email' => $member->email,
                'password' => 'password123',
            ])
            ->assertRedirect('/portal/login')
            ->assertSessionHasErrors('email');

        $this->assertGuest('member');
    }

    public function test_guest_is_redirected_to_portal_login(): void
    {
        $this->get('/portal')->assertRedirect(route('login'));
    }

    public function test_member_can_see_attendance_track_record(): void
    {
        $member = $this->createMember();
        $meeting = $this->createMeeting('Rapat Evaluasi Portal');

        Attendance::create([
            'meeting_id' => $meeting->id,
            'user_id' => $member->id,
            'status' => 'hadir',
            'scan_method' => 'qr_scan',
            'scanned_at' => now(),
            'created_at' => now(),
        ]);

        $this->actingAs($member, 'member');

        $this->get('/portal')
            ->assertOk()
            ->assertSee('Rapat Evaluasi Portal')
            ->assertSee('Hadir');

        $this->get('/portal/riwayat')
            ->assertOk()
            ->assertSee('Rapat Evaluasi Portal')
            ->assertSee('Track Record Kehadiran');
    }

    public function test_member_can_view_portal_pages(): void
    {
        $member = $this->createMember();
        $this->actingAs($member, 'member');

        $this->get('/portal/absen')->assertOk()->assertSee('Form Izin / Sakit');
        $this->get('/portal/profil')->assertOk()->assertSee('Profil Pengurus');
    }

    public function test_member_can_mark_presence_for_active_meeting(): void
    {
        $member = $this->createMember();
        $meeting = $this->createMeeting('Rapat Presensi Portal', 'scanning', 'online');

        $attendance = Attendance::create([
            'meeting_id' => $meeting->id,
            'user_id' => $member->id,
            'status' => 'alpha',
            'created_at' => now(),
        ]);

        $this->actingAs($member, 'member');

        $this->from('/portal')
            ->post(route('portal.presence.store', $attendance->id))
            ->assertRedirect('/portal');

        $this->assertDatabaseHas('attendances', [
            'id' => $attendance->id,
            'status' => 'hadir',
            'scan_method' => 'online_link',
        ]);
    }

    public function test_member_cannot_self_mark_presence_for_offline_meeting(): void
    {
        $member = $this->createMember();
        $meeting = $this->createMeeting('Rapat Presensi Offline', 'scanning', 'offline');

        $attendance = Attendance::create([
            'meeting_id' => $meeting->id,
            'user_id' => $member->id,
            'status' => 'alpha',
            'created_at' => now(),
        ]);

        $this->actingAs($member, 'member');

        $this->from('/portal')
            ->post(route('portal.presence.store', $attendance->id))
            ->assertRedirect('/portal')
            ->assertSessionHas('error');

        $this->assertDatabaseHas('attendances', [
            'id' => $attendance->id,
            'status' => 'alpha',
        ]);
    }

    public function test_member_cannot_mark_presence_for_completed_meeting(): void
    {
        $member = $this->createMember();
        $meeting = $this->createMeeting('Rapat Sudah Selesai');

        $attendance = Attendance::create([
            'meeting_id' => $meeting->id,
            'user_id' => $member->id,
            'status' => 'alpha',
            'created_at' => now(),
        ]);

        $this->actingAs($member, 'member');

        $this->from('/portal')
            ->post(route('portal.presence.store', $attendance->id))
            ->assertRedirect('/portal')
            ->assertSessionHas('error');

        $this->assertDatabaseHas('attendances', [
            'id' => $attendance->id,
            'status' => 'alpha',
        ]);
    }

    public function test_member_cannot_mark_presence_for_another_member(): void
    {
        $member = $this->createMember();
        $otherMember = $this->createMember();
        $meeting = $this->createMeeting('Rapat Presensi Orang Lain', 'scanning');

        $attendance = Attendance::create([
            'meeting_id' => $meeting->id,
            'user_id' => $otherMember->id,
            'status' => 'alpha',
            'created_at' => now(),
        ]);

        $this->actingAs($member, 'member');

        $this->post(route('portal.presence.store', $attendance->id))
            ->assertNotFound();
    }

    public function test_member_can_submit_absence_for_own_meeting(): void
    {
        $member = $this->createMember();
        $meeting = $this->createMeeting('Rapat Yang Akan Diizinkan', 'scanning');

        $attendance = Attendance::create([
            'meeting_id' => $meeting->id,
            'user_id' => $member->id,
            'status' => 'alpha',
            'created_at' => now(),
        ]);

        $this->actingAs($member, 'member');

        $this->post('/portal/absen', [
            'attendance_id' => $attendance->id,
            'status' => 'izin',
            'keterangan' => 'Ada keperluan keluarga.',
        ])->assertRedirect(route('portal.attendance'));

        $this->assertDatabaseHas('attendances', [
            'id' => $attendance->id,
            'status' => 'izin',
            'scan_method' => 'absent_form',
            'keterangan' => 'Ada keperluan keluarga.',
        ]);
    }

    public function test_member_cannot_submit_absence_for_another_member(): void
    {
        $member = $this->createMember();
        $otherMember = $this->createMember();
        $meeting = $this->createMeeting('Rapat Orang Lain', 'scanning');

        $attendance = Attendance::create([
            'meeting_id' => $meeting->id,
            'user_id' => $otherMember->id,
            'status' => 'alpha',
            'created_at' => now(),
        ]);

        $this->actingAs($member, 'member');

        $this->post('/portal/absen', [
            'attendance_id' => $attendance->id,
            'status' => 'izin',
            'keterangan' => 'Mencoba mengubah absen orang lain.',
        ])->assertNotFound();

        $this->assertDatabaseHas('attendances', [
            'id' => $attendance->id,
            'status' => 'alpha',
        ]);
    }

    public function test_member_can_update_profile_photo_and_password(): void
    {
        $member = $this->createMember();
        $this->actingAs($member, 'member');

        $this->put('/portal/profil', [
            'email' => 'profil.'.Str::lower(Str::random(6)).'@himati.test',
            'profile_photo' => UploadedFile::fake()->image('avatar.jpg', 120, 120),
        ])->assertRedirect();

        $member->refresh();
        $this->assertNotNull($member->profile_photo);

        $photoPath = public_path('uploads/profiles/'.$member->profile_photo);
        $this->assertFileExists($photoPath);

        $this->put('/portal/profil/password', [
            'current_password' => 'password123',
            'password' => 'new-password-123',
            'password_confirmation' => 'new-password-123',
        ])->assertRedirect();

        $this->assertTrue(Hash::check('new-password-123', $member->fresh()->password));

        File::delete($photoPath);
    }

    public function test_member_can_download_own_qr_code(): void
    {
        Http::fake([
            '*' => Http::response(str_repeat('x', 256), 200),
        ]);

        $member = $this->createMember();
        $this->actingAs($member, 'member');

        $this->get('/portal/qr')
            ->assertOk()
            ->assertSee($member->qr_code);

        $response = $this->get('/portal/qr/download');
        $response->assertOk();
        $response->assertDownload();

        $filename = $member->fresh()->qr_image;
        $this->assertNotNull($filename);

        File::delete(public_path('assets/img/qrcodes/'.$filename));
    }

    public function test_admin_can_generate_and_reset_member_accounts(): void
    {
        $admin = Admin::where('email', 'admin@himati.id')->firstOrFail();
        $member = $this->createMember(['password' => null]);
        $defaultPassword = config('himati.default_member_password');

        $this->actingAs($admin);

        $this->get('/members/accounts')
            ->assertOk()
            ->assertSee('Akun Login Portal Pengurus');

        $this->post('/members/accounts/generate')
            ->assertRedirect();

        $this->assertTrue(Hash::check($defaultPassword, $member->fresh()->password));

        $member->update(['password' => 'temporary-password']);

        $this->post(route('members.accounts.reset', $member->id))
            ->assertRedirect();

        $this->assertTrue(Hash::check($defaultPassword, $member->fresh()->password));

        $this->post(route('members.accounts.disable', $member->id))
            ->assertRedirect();

        $this->assertNull($member->fresh()->password);
    }

    public function test_admin_can_reset_member_password_when_member_forgets_password(): void
    {
        $admin = Admin::where('email', 'admin@himati.id')->firstOrFail();
        $defaultPassword = config('himati.default_member_password', 'password123');

        $member = $this->createMember([
            'email' => 'budi.santoso@himati.test',
            'password' => 'secretCustomPass999',
        ]);

        $this->actingAs($admin);

        // Reset password action
        $response = $this->post(route('members.accounts.reset', $member->id));
        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Check password in DB matches default password
        $this->assertTrue(Hash::check($defaultPassword, $member->fresh()->password));

        // Pengurus can now log in using default password
        $loginResponse = $this->post('/portal/login', [
            'email' => 'budi.santoso@himati.test',
            'password' => $defaultPassword,
        ]);
        $loginResponse->assertRedirect(route('portal.dashboard'));
        $this->assertAuthenticatedAs($member, 'member');
    }

    public function test_admin_can_reset_member_password_via_member_update_checkbox(): void
    {
        $admin = Admin::where('email', 'admin@himati.id')->firstOrFail();
        $defaultPassword = config('himati.default_member_password', 'password123');

        $member = $this->createMember([
            'nama_lengkap' => 'Ahmad Dahlan',
            'email' => 'ahmad@himati.test',
            'password' => 'oldSecretPassword123',
        ]);

        $this->actingAs($admin);

        $response = $this->put(route('members.update', $member->id), [
            'nama_lengkap' => 'Ahmad Dahlan',
            'departemen' => 'PSDM',
            'jabatan' => 'Anggota',
            'email' => 'ahmad@himati.test',
            'reset_password_to_default' => '1',
        ]);

        $response->assertRedirect(route('members.index'));
        $this->assertTrue(Hash::check($defaultPassword, $member->fresh()->password));
    }

    public function test_admin_cannot_reset_password_for_member_without_email(): void
    {
        $admin = Admin::where('email', 'admin@himati.id')->firstOrFail();
        $member = $this->createMember(['email' => null, 'password' => null]);

        $this->actingAs($admin);

        $response = $this->post(route('members.accounts.reset', $member->id));
        $response->assertRedirect();
        $response->assertSessionHas('error');
    }

    public function test_logged_in_member_sees_verified_identity_on_create_meeting_page(): void
    {
        $member = $this->createMember(['nama_lengkap' => 'Muhammad Budi Santoso', 'jabatan' => 'Staff PSDM']);

        $this->actingAs($member, 'member')
            ->get('/rapat')
            ->assertOk()
            ->assertSee('Muhammad Budi Santoso')
            ->assertSee('Akun Login Terverifikasi')
            ->assertSee('Staff PSDM');
    }

    public function test_logged_in_member_creates_meeting_automatically_uses_account_identity(): void
    {
        $member = $this->createMember(['nama_lengkap' => 'Rina Kartika', 'jabatan' => 'Koor Medinfo']);

        $response = $this->actingAs($member, 'member')
            ->post('/rapat', [
                'jenis_rapat' => 'internal_dept',
                'departemen' => 'PSDM',
                'fokus_bahasan_internal' => 'Pembahasan Program Kerja Semester',
                'mode_rapat' => 'offline',
                'tanggal' => now()->toDateString(),
                'waktu_mulai' => '10:00',
            ]);

        $meeting = Meeting::where('fokus_bahasan', 'Pembahasan Program Kerja Semester')->first();
        $this->assertNotNull($meeting);
        $this->assertEquals($member->id, $meeting->created_by);
        $this->assertEquals('Rina Kartika', $meeting->creator->nama_lengkap);

        $response->assertRedirect(route('public.hasil', $meeting->unique_link));

        $this->get(route('public.hasil', $meeting->unique_link))
            ->assertOk()
            ->assertSee('Rina Kartika')
            ->assertSee('Koor Medinfo');
    }

    private function createMember(array $attributes = []): Member
    {
        return Member::create(array_merge([
            'nama_lengkap' => 'Pengurus Test '.Str::random(6),
            'departemen' => 'PSDM',
            'jabatan' => 'Anggota',
            'email' => 'member.'.Str::lower(Str::random(8)).'@himati.test',
            'password' => 'password123',
            'qr_code' => 'HIMATI-TEST-'.Str::upper(Str::random(8)),
            'is_bph' => false,
        ], $attributes));
    }

    private function createMeeting(string $agenda, string $status = 'completed', string $mode = 'offline'): Meeting
    {
        $creator = Member::first() ?? $this->createMember();

        return Meeting::create([
            'created_by' => $creator->id,
            'jenis_rapat' => 'internal_dept',
            'mode_rapat' => $mode,
            'departemen' => 'PSDM',
            'fokus_bahasan' => $agenda,
            'tanggal' => now()->toDateString(),
            'waktu_mulai' => '09:00:00',
            'status' => $status,
            'unique_link' => Str::lower(Str::random(12)),
            'absent_link' => Str::lower(Str::random(12)),
        ]);
    }
}

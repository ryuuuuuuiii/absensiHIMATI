<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Announcement;
use App\Models\Attendance;
use App\Models\Meeting;
use App\Models\Member;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class SpMechanismTest extends TestCase
{
    use DatabaseTransactions;

    private function createTestMember(array $overrides = []): Member
    {
        return Member::create(array_merge([
            'nama_lengkap' => 'Pengurus Test SP',
            'jabatan' => 'Anggota',
            'departemen' => 'PTKP',
            'email' => 'sptest_'.Str::lower(Str::random(6)).'@himati.id',
            'password' => Hash::make('password123'),
            'qr_code' => 'HIMATI-SPTEST-'.Str::upper(Str::random(8)),
            'sp_level' => 0,
            'sp_base_level' => 0,
            'is_active' => true,
        ], $overrides));
    }

    private function createCompletedMeetingWithAttendance(Member $member, string $attendanceStatus, int $dayOffset): Meeting
    {
        $meeting = Meeting::create([
            'created_by' => $member->id,
            'fokus_bahasan' => 'Rapat Evaluasi '.$dayOffset,
            'tanggal' => now()->addDays($dayOffset)->toDateString(),
            'waktu_mulai' => '09:00:00',
            'waktu_selesai' => '11:00:00',
            'status' => 'scanning',
            'jenis_rapat' => 'perdana',
            'mode_rapat' => 'offline',
            'unique_link' => Str::random(16),
            'absent_link' => 'abs_'.Str::random(12),
        ]);

        Attendance::create([
            'meeting_id' => $meeting->id,
            'user_id' => $member->id,
            'status' => $attendanceStatus,
            'waktu_hadir' => $attendanceStatus === 'hadir' ? now() : null,
        ]);

        $meeting->update(['status' => 'completed']);

        return $meeting;
    }

    public function test_three_consecutive_alphas_in_completed_meetings_automatically_issue_sp1(): void
    {
        $member = $this->createTestMember();

        $this->createCompletedMeetingWithAttendance($member, 'alpha', 1);
        $this->createCompletedMeetingWithAttendance($member, 'alpha', 2);
        $member->refresh();
        $this->assertSame(0, $member->sp_level);

        $this->createCompletedMeetingWithAttendance($member, 'alpha', 3);
        $member->refresh();

        $this->assertSame(1, $member->sp_level);
        $this->assertTrue($member->is_active);
        $this->assertSame('SP 1', $member->sp_label);
        $this->assertTrue($member->isSpFromAlpha());
        $this->assertStringContainsString('Apabila kembali Alpha 3 kali berturut-turut', (string) $member->sp_warning_text);

        $notif = Announcement::where('target_type', 'personal')
            ->where('target_user_id', $member->id)
            ->latest('id')
            ->first();
        $this->assertNotNull($notif);
        $this->assertStringContainsString('Tidak Hadir 3x Berturut-turut', $notif->judul);
        $this->assertStringContainsString('Apabila kembali Alpha 3 kali berturut-turut', $notif->konten);
    }

    public function test_interrupted_alpha_streak_does_not_trigger_sp1(): void
    {
        $member = $this->createTestMember();

        $this->createCompletedMeetingWithAttendance($member, 'alpha', 1);
        $this->createCompletedMeetingWithAttendance($member, 'alpha', 2);
        $this->createCompletedMeetingWithAttendance($member, 'hadir', 3);
        $this->createCompletedMeetingWithAttendance($member, 'alpha', 4);
        $this->createCompletedMeetingWithAttendance($member, 'alpha', 5);

        $member->refresh();
        $this->assertSame(0, $member->sp_level);
        $this->assertSame(2, $member->consecutive_alpha_count);
    }

    public function test_repeated_three_consecutive_alphas_escalate_to_sp2_and_sp3_deactivates_account(): void
    {
        $member = $this->createTestMember();

        // 3 consecutive alphas -> SP 1
        for ($i = 1; $i <= 3; $i++) {
            $this->createCompletedMeetingWithAttendance($member, 'alpha', $i);
        }
        $member->refresh();
        $this->assertSame(1, $member->sp_level);
        $this->assertTrue($member->is_active);
        $this->assertTrue($member->isSpFromAlpha());

        // 3 more consecutive alphas -> SP 2
        for ($i = 4; $i <= 6; $i++) {
            $this->createCompletedMeetingWithAttendance($member, 'alpha', $i);
        }
        $member->refresh();
        $this->assertSame(2, $member->sp_level);
        $this->assertTrue($member->is_active);

        // 3 more consecutive alphas -> SP 3 (Pemecatan & Nonaktif)
        for ($i = 7; $i <= 9; $i++) {
            $this->createCompletedMeetingWithAttendance($member, 'alpha', $i);
        }
        $member->refresh();
        $this->assertSame(3, $member->sp_level);
        $this->assertFalse($member->is_active);
        $this->assertTrue($member->isDismissed());
    }

    public function test_admin_can_manually_add_sp_and_remove_sp(): void
    {
        $admin = Admin::first() ?? Admin::create([
            'username' => 'admin_sp_test',
            'email' => 'admin_sp_test@himati.id',
            'password' => Hash::make('admin123'),
        ]);
        $this->actingAs($admin);

        $member = $this->createTestMember();

        // Admin gives SP 1 directly with Pelanggaran and Rincian Pelanggaran
        $this->post(route('members.sp.add', $member), [
            'sp_reason' => 'Pelanggaran disiplin rapat',
            'sp_detail' => 'Tidak menjalankan tugas koordinasi divisi pada kegiatan kampus.',
        ])->assertRedirect();

        $member->refresh();
        $this->assertSame(1, $member->sp_level);
        $this->assertTrue($member->is_active);
        $this->assertTrue($member->isSpFromAdmin());
        $this->assertFalse($member->isSpFromAlpha());
        $this->assertSame('Pelanggaran disiplin rapat', $member->sp_reason_display);
        $this->assertSame('Tidak menjalankan tugas koordinasi divisi pada kegiatan kampus.', $member->sp_detail_display);
        $this->assertStringContainsString('melakukan pelanggaran', (string) $member->sp_warning_text);
        $this->assertStringNotContainsString('Apabila kembali Alpha 3 kali berturut-turut', (string) $member->sp_warning_text);

        $adminSpNotif = Announcement::where('target_type', 'personal')
            ->where('target_user_id', $member->id)
            ->latest('id')
            ->first();
        $this->assertNotNull($adminSpNotif);
        $this->assertStringContainsString('Pelanggaran', $adminSpNotif->judul);
        $this->assertStringContainsString('Pelanggaran disiplin rapat', $adminSpNotif->konten);
        $this->assertStringContainsString('Tidak menjalankan tugas koordinasi divisi pada kegiatan kampus.', $adminSpNotif->konten);
        $this->assertStringNotContainsString('Apabila kembali Alpha 3 kali berturut-turut', $adminSpNotif->konten);

        // Memberalready has SP 1, Admin adds SP again -> automatically becomes SP 2
        $this->post(route('members.sp.add', $member), [
            'reason' => 'Pelanggaran berulang',
        ])->assertRedirect();

        $member->refresh();
        $this->assertSame(2, $member->sp_level);
        $this->assertTrue($member->is_active);

        // Admin adds SP again -> becomes SP 3 and deactivated in database
        $this->post(route('members.sp.add', $member), [
            'reason' => 'Pemberhentian pengurus',
        ])->assertRedirect();

        $member->refresh();
        $this->assertSame(3, $member->sp_level);
        $this->assertFalse($member->is_active);

        // Admin removes SP -> resets to SP 0 and reactivates account
        $this->post(route('members.sp.remove', $member))->assertRedirect();

        $member->refresh();
        $this->assertSame(0, $member->sp_level);
        $this->assertSame(0, $member->sp_base_level);
        $this->assertTrue($member->is_active);
    }

    public function test_attendance_scan_includes_sp_info_and_blocks_sp3_member(): void
    {
        $member = $this->createTestMember([
            'sp_level' => 1,
            'sp_base_level' => 1,
            'sp_reason' => 'Alpha 3x berturut-turut',
            'is_active' => true,
        ]);

        $meeting = Meeting::create([
            'created_by' => $member->id,
            'fokus_bahasan' => 'Rapat Scan SP',
            'tanggal' => now()->toDateString(),
            'waktu_mulai' => '08:00:00',
            'waktu_selesai' => '12:00:00',
            'status' => 'scanning',
            'jenis_rapat' => 'perdana',
            'mode_rapat' => 'offline',
            'unique_link' => Str::random(16),
            'absent_link' => 'abs_'.Str::random(12),
        ]);

        $scanResponse = $this->postJson('/meeting/process/process_scan.php', [
            'meeting_id' => $meeting->id,
            'qr_code' => $member->qr_code,
        ]);

        $scanResponse->assertStatus(200)
            ->assertJson([
                'success' => true,
                'user' => [
                    'nama' => $member->nama_lengkap,
                    'sp_level' => 1,
                    'sp_label' => 'SP 1',
                ],
            ]);
        $this->assertStringContainsString('SP 1', $scanResponse->json('message'));

        // Now set member to SP 3 (dismissed & inactive)
        $member->update([
            'sp_level' => 3,
            'sp_base_level' => 3,
            'is_active' => false,
        ]);

        $blockedScan = $this->postJson('/meeting/process/process_scan.php', [
            'meeting_id' => $meeting->id,
            'qr_code' => $member->qr_code,
        ]);

        $blockedScan->assertStatus(403)
            ->assertJson([
                'success' => false,
            ]);
        $this->assertStringContainsString('SP 3', $blockedScan->json('message'));

        // Login should also be rejected for SP 3 member
        $loginResponse = $this->post('/login', [
            'email' => $member->email,
            'password' => 'password123',
        ]);
        $loginResponse->assertSessionHasErrors('email');
        $this->assertGuest('member');
    }
}

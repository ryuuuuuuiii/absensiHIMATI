<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Announcement;
use App\Models\Attendance;
use App\Models\Meeting;
use App\Models\Member;
use App\Services\GamificationService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class MeetingCancellationAndDeletionTest extends TestCase
{
    use DatabaseTransactions;

    private function createAdmin(): Admin
    {
        return Admin::create([
            'username' => 'admin_del_'.Str::lower(Str::random(6)),
            'nama' => 'Admin Uji Hapus Rapat',
            'email' => 'admin_del_'.Str::lower(Str::random(6)).'@himati.test',
            'password' => Hash::make('password123'),
        ]);
    }

    private function createMember(array $overrides = []): Member
    {
        return Member::create(array_merge([
            'nama_lengkap' => 'Pengurus '.Str::random(6),
            'departemen' => 'KOMINFO',
            'jabatan' => 'Anggota',
            'email' => 'member_'.Str::lower(Str::random(8)).'@himati.test',
            'password' => Hash::make('password123'),
            'qr_code' => 'HIMATI-DEL-'.Str::upper(Str::random(8)),
            'exp' => 0,
            'sp_level' => 0,
            'sp_base_level' => 0,
            'is_active' => true,
        ], $overrides));
    }

    private function createMeeting(Member $creator, array $overrides = []): Meeting
    {
        return Meeting::create(array_merge([
            'created_by' => $creator->id,
            'jenis_rapat' => 'perdana',
            'kategori' => 'rapat',
            'mode_rapat' => 'offline',
            'nama_event' => 'Rapat Perdana '.Str::random(5),
            'fokus_bahasan' => 'Rapat Perdana Uji',
            'tanggal' => now()->toDateString(),
            'waktu_mulai' => '09:00:00',
            'waktu_selesai' => '11:00:00',
            'status' => 'scanning',
            'unique_link' => Str::lower(Str::random(12)),
            'absent_link' => Str::lower(Str::random(12)),
        ], $overrides));
    }

    public function test_deleting_completed_meeting_removes_alpha_and_recalculates_auto_sp_but_preserves_exp(): void
    {
        $admin = $this->createAdmin();
        $memberAlpha = $this->createMember(['nama_lengkap' => 'Pengurus Alpha Test', 'exp' => 0]);
        $memberHadir = $this->createMember(['nama_lengkap' => 'Pengurus Hadir Test', 'exp' => 200]);

        // Buat 3 rapat selesai berturut-turut di mana memberAlpha selalu alpha -> otomatis SP 1
        // Pada rapat ke-3 (Rapat Perdana), memberHadir tercatat hadir dan mendapat +100 EXP (total 300)
        $meetings = [];
        for ($i = 1; $i <= 3; $i++) {
            $meeting = $this->createMeeting($memberHadir, [
                'nama_event' => 'Rapat Perdana #'.$i,
                'tanggal' => now()->subDays(4 - $i)->toDateString(),
                'status' => 'scanning',
            ]);

            Attendance::create([
                'meeting_id' => $meeting->id,
                'user_id' => $memberAlpha->id,
                'status' => 'alpha',
                'exp_awarded' => false,
            ]);

            $hadirAtt = Attendance::create([
                'meeting_id' => $meeting->id,
                'user_id' => $memberHadir->id,
                'status' => 'hadir',
                'scanned_at' => now(),
                'exp_awarded' => false,
            ]);

            if ($i === 3) {
                app(GamificationService::class)->awardAttendanceExp($hadirAtt, $memberHadir);
            }

            $meeting->update(['status' => 'completed']);
            $meetings[$i] = $meeting;
        }

        $memberAlpha->refresh();
        $memberHadir->refresh();

        $this->assertSame(1, $memberAlpha->sp_level);
        $this->assertTrue($memberAlpha->isSpFromAlpha());
        $this->assertSame(300, $memberHadir->exp);

        // Hangatkan cache portal_stats untuk memastikan cache juga dibersihkan saat rapat dihapus
        $dashboardBefore = $this->actingAs($memberAlpha, 'member')
            ->get(route('portal.dashboard'))
            ->assertOk();
        $this->assertSame(3, $dashboardBefore->viewData('stats')['alpha']);

        // Admin menghapus Rapat Perdana #3 yang sudah selesai
        auth('member')->logout();
        $targetMeeting = $meetings[3];
        $this->actingAs($admin, 'web')
            ->delete(route('meetings.destroy', $targetMeeting->id))
            ->assertRedirect(route('meetings.index'))
            ->assertSessionHas('success');

        // Rapat dan seluruh presensinya (termasuk alpha memberAlpha) hilang
        $this->assertDatabaseMissing('meetings', ['id' => $targetMeeting->id]);
        $this->assertDatabaseMissing('attendances', ['meeting_id' => $targetMeeting->id]);

        // SP otomatis memberAlpha turun kembali ke 0 karena alpha tinggal 2
        $memberAlpha->refresh();
        $this->assertSame(0, $memberAlpha->sp_level);
        $this->assertSame(2, $memberAlpha->consecutive_alpha_count);
        $this->assertTrue($memberAlpha->is_active);

        // Statistik portal memberAlpha langsung ter-update (tidak menampilkan alpha dari rapat yang dihapus)
        auth('web')->logout();
        $dashboardAfter = $this->actingAs($memberAlpha, 'member')
            ->get(route('portal.dashboard'))
            ->assertOk();
        $this->assertSame(2, $dashboardAfter->viewData('stats')['alpha']);

        $pinnedSpNotif = Announcement::where('target_type', 'personal')
            ->where('target_user_id', $memberAlpha->id)
            ->where('judul', 'like', '[SURAT PERINGATAN]%')
            ->where('pinned', true)
            ->first();
        $this->assertNull($pinnedSpNotif);

        // EXP memberHadir dari rapat yang sudah selesai TIDAK hilang (tetap 300)
        $memberHadir->refresh();
        $this->assertSame(300, $memberHadir->exp);
    }

    public function test_deleting_completed_meeting_preserves_manual_admin_sp(): void
    {
        $admin = $this->createAdmin();
        $member = $this->createMember([
            'sp_level' => 1,
            'sp_base_level' => 1,
            'sp_reason' => 'Pelanggaran disiplin oleh Admin',
        ]);

        $meeting = $this->createMeeting($member, ['status' => 'scanning']);
        Attendance::create([
            'meeting_id' => $meeting->id,
            'user_id' => $member->id,
            'status' => 'alpha',
            'exp_awarded' => false,
        ]);
        $meeting->update(['status' => 'completed']);

        $this->actingAs($admin)
            ->delete(route('meetings.destroy', $meeting->id))
            ->assertRedirect(route('meetings.index'));

        $member->refresh();
        $this->assertSame(1, $member->sp_level);
        $this->assertSame(1, $member->sp_base_level);
        $this->assertTrue($member->isSpFromAdmin());
    }

    public function test_cancelling_meeting_from_admin_revokes_awarded_exp_idempotently(): void
    {
        $admin = $this->createAdmin();
        $creator = $this->createMember(['exp' => 100]);
        $attendee = $this->createMember(['exp' => 50]);

        $meeting = $this->createMeeting($creator, ['status' => 'scanning']);

        // Creator auto-hadir + attendee hadir
        $creatorAtt = Attendance::create([
            'meeting_id' => $meeting->id,
            'user_id' => $creator->id,
            'status' => 'hadir',
            'scanned_at' => now(),
            'exp_awarded' => false,
        ]);
        $attendeeAtt = Attendance::create([
            'meeting_id' => $meeting->id,
            'user_id' => $attendee->id,
            'status' => 'hadir',
            'scanned_at' => now(),
            'exp_awarded' => false,
        ]);

        $gamification = app(GamificationService::class);
        $gamification->awardAttendanceExp($creatorAtt, $creator);
        $gamification->awardAttendanceExp($attendeeAtt, $attendee);

        $this->assertSame(200, $creator->fresh()->exp);
        $this->assertSame(150, $attendee->fresh()->exp);

        // Admin membatalkan rapat
        $this->actingAs($admin)
            ->post(route('meetings.cancel', $meeting->id))
            ->assertRedirect(route('meetings.show', $meeting->id))
            ->assertSessionHas('warning');

        $this->assertSame('cancelled', $meeting->fresh()->status);
        $this->assertSame(100, $creator->fresh()->exp);
        $this->assertSame(50, $attendee->fresh()->exp);
        $this->assertFalse((bool) $creatorAtt->fresh()->exp_awarded);
        $this->assertFalse((bool) $attendeeAtt->fresh()->exp_awarded);

        // Pembatalan ulang harus idempotent (tidak memotong EXP 2 kali)
        $this->actingAs($admin)
            ->post(route('meetings.cancel', $meeting->id))
            ->assertRedirect(route('meetings.show', $meeting->id));

        $this->assertSame(100, $creator->fresh()->exp);
        $this->assertSame(50, $attendee->fresh()->exp);
    }

    public function test_cancelling_meeting_from_public_link_revokes_awarded_exp(): void
    {
        $creator = $this->createMember(['exp' => 0]);
        $attendee = $this->createMember(['exp' => 0]);

        $meeting = $this->createMeeting($creator, ['status' => 'scanning']);

        $creatorAtt = Attendance::create([
            'meeting_id' => $meeting->id,
            'user_id' => $creator->id,
            'status' => 'hadir',
            'scanned_at' => now(),
            'exp_awarded' => false,
        ]);
        $attendeeAtt = Attendance::create([
            'meeting_id' => $meeting->id,
            'user_id' => $attendee->id,
            'status' => 'hadir',
            'scanned_at' => now(),
            'exp_awarded' => false,
        ]);

        $gamification = app(GamificationService::class);
        $gamification->awardAttendanceExp($creatorAtt, $creator);
        $gamification->awardAttendanceExp($attendeeAtt, $attendee);

        $this->assertSame(100, $creator->fresh()->exp);
        $this->assertSame(100, $attendee->fresh()->exp);

        $this->postJson(route('public.meeting.cancel', $meeting->unique_link))
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertSame('cancelled', $meeting->fresh()->status);
        $this->assertSame(0, $creator->fresh()->exp);
        $this->assertSame(0, $attendee->fresh()->exp);
        $this->assertFalse((bool) $creatorAtt->fresh()->exp_awarded);
        $this->assertFalse((bool) $attendeeAtt->fresh()->exp_awarded);
    }

    public function test_deleting_unfinished_meeting_revokes_exp_and_removes_attendances(): void
    {
        $admin = $this->createAdmin();
        $attendee = $this->createMember(['exp' => 0]);

        $meeting = $this->createMeeting($attendee, ['status' => 'scanning']);
        $att = Attendance::create([
            'meeting_id' => $meeting->id,
            'user_id' => $attendee->id,
            'status' => 'hadir',
            'scanned_at' => now(),
            'exp_awarded' => false,
        ]);

        app(GamificationService::class)->awardAttendanceExp($att, $attendee);
        $this->assertSame(100, $attendee->fresh()->exp);

        // Rapat belum selesai dihapus langsung oleh admin -> EXP dibatalkan
        $this->actingAs($admin)
            ->delete(route('meetings.destroy', $meeting->id))
            ->assertRedirect(route('meetings.index'));

        $this->assertDatabaseMissing('meetings', ['id' => $meeting->id]);
        $this->assertDatabaseMissing('attendances', ['meeting_id' => $meeting->id]);
        $this->assertSame(0, $attendee->fresh()->exp);
    }

    public function test_completed_meeting_cannot_be_cancelled_and_exp_remains_intact(): void
    {
        $admin = $this->createAdmin();
        $attendee = $this->createMember(['exp' => 0]);

        $meeting = $this->createMeeting($attendee, ['status' => 'scanning']);
        $att = Attendance::create([
            'meeting_id' => $meeting->id,
            'user_id' => $attendee->id,
            'status' => 'hadir',
            'scanned_at' => now(),
            'exp_awarded' => false,
        ]);

        app(GamificationService::class)->awardAttendanceExp($att, $attendee);
        $meeting->update(['status' => 'completed']);

        $this->actingAs($admin)
            ->post(route('meetings.cancel', $meeting->id))
            ->assertSessionHas('danger');

        $this->assertSame('completed', $meeting->fresh()->status);
        $this->assertSame(100, $attendee->fresh()->exp);
        $this->assertTrue((bool) $att->fresh()->exp_awarded);
    }

    public function test_report_excludes_cancelled_and_deleted_meeting_attendances(): void
    {
        $admin = $this->createAdmin();
        $member = $this->createMember([
            'nama_lengkap' => 'Pengurus Laporan Uji',
            'departemen' => 'KOMINFO',
        ]);

        $date = now()->startOfMonth();

        // 1. Rapat dibatalkan (alpha tidak boleh terhitung di laporan)
        $cancelledMeeting = $this->createMeeting($member, [
            'tanggal' => $date->toDateString(),
            'status' => 'scanning',
        ]);
        Attendance::create([
            'meeting_id' => $cancelledMeeting->id,
            'user_id' => $member->id,
            'status' => 'alpha',
        ]);

        $this->actingAs($admin)
            ->post(route('meetings.cancel', $cancelledMeeting->id));

        // 2. Rapat selesai yang lalu dihapus (alpha harus hilang dari laporan)
        $deletedMeeting = $this->createMeeting($member, [
            'tanggal' => $date->toDateString(),
            'status' => 'scanning',
        ]);
        Attendance::create([
            'meeting_id' => $deletedMeeting->id,
            'user_id' => $member->id,
            'status' => 'alpha',
        ]);
        $deletedMeeting->update(['status' => 'completed']);

        $this->actingAs($admin)
            ->delete(route('meetings.destroy', $deletedMeeting->id));

        $response = $this->actingAs($admin)
            ->get(route('reports.index', [
                'month' => $date->month,
                'year' => $date->year,
                'dept' => 'KOMINFO',
            ]))
            ->assertOk();

        $reportData = $response->viewData('reportData');
        $row = $reportData->firstWhere('id', $member->id);

        $this->assertNotNull($row);
        $this->assertSame(0, (int) $row->total_rapat);
        $this->assertSame(0, (int) $row->alpha);
    }
}

<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Attendance;
use App\Models\Event;
use App\Models\EventDivision;
use App\Models\EventMember;
use App\Models\Meeting;
use App\Models\Member;
use App\Models\MemberBadge;
use App\Services\GamificationService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class GamificationAndEventBadgeTest extends TestCase
{
    use DatabaseTransactions;

    protected function createAdmin(): Admin
    {
        return Admin::create([
            'username' => 'admin_test_'.Str::random(6),
            'nama' => 'Admin Gamification Test',
            'email' => 'admin_'.Str::random(6).'@example.com',
            'password' => Hash::make('password123'),
        ]);
    }

    protected function createMember(array $attributes = []): Member
    {
        return Member::create(array_merge([
            'qr_code' => 'TEST-'.Str::upper(Str::random(6)),
            'nama_lengkap' => 'Pengurus Test '.Str::random(4),
            'email' => 'pengurus_'.Str::random(6).'@himati.id',
            'password' => Hash::make('password123'),
            'departemen' => 'KOMINFO',
            'jabatan' => 'Staff Ahli',
            'exp' => 0,
            'is_bph' => false,
            'is_active' => true,
        ], $attributes));
    }

    public function test_member_level_and_progress_calculation(): void
    {
        $member = $this->createMember(['exp' => 0]);
        $data = $member->level_data;

        $this->assertEquals(1, $data['level']);
        $this->assertEquals('Pengurus Pemula', $data['title']);
        $this->assertEquals(0, $data['progress']);
        $this->assertEquals(0.0, $data['percentage']);

        // 100 EXP triggers Level 2
        $member->exp = 100;
        $data2 = $member->level_data;
        $this->assertEquals(2, $data2['level']);
        $this->assertEquals('Pengurus Pemula', $data2['title']);
        $this->assertEquals(0, $data2['progress']); // Current min is 100, next is 250 (step 150)

        // 250 EXP triggers Level 3
        $member->exp = 250;
        $data3 = $member->level_data;
        $this->assertEquals(3, $data3['level']);
        $this->assertEquals('Pengurus Aktif', $data3['title']);
    }

    public function test_offline_qr_scan_awards_exp_idempotently(): void
    {
        $admin = $this->createAdmin();
        $member = $this->createMember(['exp' => 0]);

        $meeting = Meeting::create([
            'nama_rapat' => 'Rapat Pleno Uji Coba EXP',
            'created_by' => $member->id,
            'tanggal' => now()->toDateString(),
            'waktu_mulai' => '09:00:00',
            'waktu_selesai' => '11:00:00',
            'tipe_rapat' => 'internal',
            'jenis_rapat' => 'internal_dept',
            'departemen' => 'KOMINFO',
            'mode_rapat' => 'offline',
            'status' => 'scanning',
            'unique_link' => Str::random(32),
            'absent_link' => Str::random(32),
        ]);

        $attendance = Attendance::create([
            'meeting_id' => $meeting->id,
            'user_id' => $member->id,
            'status' => 'alpha',
            'exp_awarded' => false,
        ]);

        $this->actingAs($admin);

        // First scan
        $response = $this->postJson(route('meetings.process_scan', $meeting->id), [
            'qr_code' => $member->qr_code,
        ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
            ]);

        $this->assertEquals(GamificationService::ATTENDANCE_EXP, $member->fresh()->exp);
        $this->assertTrue((bool) $attendance->fresh()->exp_awarded);

        // Duplicate scan
        $duplicateResponse = $this->postJson(route('meetings.process_scan', $meeting->id), [
            'qr_code' => $member->qr_code,
        ]);

        $duplicateResponse->assertOk()
            ->assertJson([
                'success' => false,
                'duplicate' => true,
            ]);

        // EXP remains 50
        $this->assertEquals(GamificationService::ATTENDANCE_EXP, $member->fresh()->exp);
    }

    public function test_admin_updating_attendance_status_awards_and_revokes_exp(): void
    {
        $admin = $this->createAdmin();
        $member = $this->createMember(['exp' => 0]);

        $meeting = Meeting::create([
            'nama_rapat' => 'Rapat Evaluasi',
            'created_by' => $member->id,
            'tanggal' => now()->toDateString(),
            'waktu_mulai' => '13:00:00',
            'waktu_selesai' => '15:00:00',
            'tipe_rapat' => 'internal',
            'mode_rapat' => 'offline',
            'status' => 'scanning',
            'unique_link' => Str::random(32),
            'absent_link' => Str::random(32),
        ]);

        $attendance = Attendance::create([
            'meeting_id' => $meeting->id,
            'user_id' => $member->id,
            'status' => 'alpha',
            'exp_awarded' => false,
        ]);

        $this->actingAs($admin);

        // Change from alpha to hadir -> awards 50 EXP
        $this->post(route('meetings.update_attendance', [$meeting->id, $attendance->id]), [
            'status' => 'hadir',
            'keterangan' => 'Hadir manual oleh admin',
        ])->assertSessionHas('success');

        $this->assertEquals(50, $member->fresh()->exp);
        $this->assertTrue((bool) $attendance->fresh()->exp_awarded);

        // Change from hadir to izin -> revokes 50 EXP
        $this->post(route('meetings.update_attendance', [$meeting->id, $attendance->id]), [
            'status' => 'izin',
            'keterangan' => 'Ada keperluan keluarga',
        ])->assertSessionHas('success');

        $this->assertEquals(0, $member->fresh()->exp);
        $this->assertFalse((bool) $attendance->fresh()->exp_awarded);
    }

    public function test_admin_can_complete_event_and_award_badges_with_scaled_exp(): void
    {
        $admin = $this->createAdmin();
        $ketua = $this->createMember(['exp' => 0]);
        $koordinator = $this->createMember(['exp' => 0]);
        $anggota = $this->createMember(['exp' => 0]);

        $event = Event::create([
            'nama_event' => 'Dies Natalis HIMA TI #7',
            'deskripsi' => 'Perayaan hari jadi jurusan TI',
            'status' => 'active',
        ]);

        $division = EventDivision::create([
            'event_id' => $event->id,
            'nama_divisi' => 'Divisi Acara',
        ]);

        EventMember::create([
            'event_id' => $event->id,
            'division_id' => $division->id,
            'user_id' => $ketua->id,
            'jabatan_panitia' => 'Ketua Pelaksana',
        ]);

        EventMember::create([
            'event_id' => $event->id,
            'division_id' => $division->id,
            'user_id' => $koordinator->id,
            'jabatan_panitia' => 'Koordinator Acara',
        ]);

        EventMember::create([
            'event_id' => $event->id,
            'division_id' => $division->id,
            'user_id' => $anggota->id,
            'jabatan_panitia' => 'Anggota Panitia',
        ]);

        $this->actingAs($admin);

        // Admin completes the event
        $response = $this->post(route('events.complete', $event->id));
        $response->assertSessionHas('success');

        $this->assertEquals('completed', $event->fresh()->status);
        $this->assertNotNull($event->fresh()->completed_at);

        // Ketua gets 500 EXP
        $this->assertEquals(GamificationService::EVENT_LEADER_EXP, $ketua->fresh()->exp);
        // Koordinator gets 350 EXP
        $this->assertEquals(GamificationService::EVENT_COORDINATOR_EXP, $koordinator->fresh()->exp);
        // Anggota gets 250 EXP
        $this->assertEquals(GamificationService::EVENT_STAFF_EXP, $anggota->fresh()->exp);

        // Check badge records
        $this->assertDatabaseHas('member_badges', [
            'user_id' => $ketua->id,
            'event_id' => $event->id,
            'event_name' => 'Dies Natalis HIMA TI #7',
            'jabatan' => 'Ketua Pelaksana',
            'exp_earned' => 500,
        ]);

        $this->assertDatabaseHas('member_badges', [
            'user_id' => $anggota->id,
            'event_id' => $event->id,
            'event_name' => 'Dies Natalis HIMA TI #7',
            'jabatan' => 'Anggota Panitia',
            'exp_earned' => 250,
        ]);

        // Member profile should render the badge
        $this->actingAs($ketua, 'member');
        $profileResponse = $this->get(route('portal.profile'));
        $profileResponse->assertOk()
            ->assertSee('Dies Natalis HIMA TI #7')
            ->assertSee('Pimpinan / Ketua')
            ->assertSee('+500 EXP');
    }

    public function test_admin_can_reopen_event_and_safely_revoke_badges_and_exp(): void
    {
        $admin = $this->createAdmin();
        $member = $this->createMember(['exp' => 0]);

        $event = Event::create([
            'nama_event' => 'Seminar Nasional 2026',
            'status' => 'active',
        ]);

        $div = EventDivision::create([
            'event_id' => $event->id,
            'nama_divisi' => 'Divisi Pubdok',
        ]);

        EventMember::create([
            'event_id' => $event->id,
            'division_id' => $div->id,
            'user_id' => $member->id,
            'jabatan_panitia' => 'Anggota',
        ]);

        $this->actingAs($admin);

        // Complete event -> awards 250 EXP
        $this->post(route('events.complete', $event->id));
        $this->assertEquals(250, $member->fresh()->exp);
        $this->assertCount(1, MemberBadge::where('event_id', $event->id)->get());

        // Reopen event -> revokes 250 EXP & deletes badge
        $reopenResponse = $this->post(route('events.reopen', $event->id));
        $reopenResponse->assertSessionHas('success');

        $this->assertEquals('active', $event->fresh()->status);
        $this->assertNull($event->fresh()->completed_at);
        $this->assertEquals(0, $member->fresh()->exp);
        $this->assertCount(0, MemberBadge::where('event_id', $event->id)->get());
    }
}

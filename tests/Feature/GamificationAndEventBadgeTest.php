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
        $this->assertEquals('Kayu', $data['title']);
        $this->assertEquals('Kayu', $data['tier_name']);
        $this->assertEquals('I', $data['sub_rank']);
        $this->assertEquals('wood.png', $data['icon_file']);
        $this->assertStringContainsString('assets/img/tiers/wood.png', $data['tier_icon_url']);
        $this->assertEquals(0, $data['progress']);
        $this->assertEquals(0.0, $data['percentage']);

        // 300 EXP triggers Level 2 -> Tier Kayu (3 kali absen @ 100 EXP)
        $member->exp = 300;
        $data2 = $member->level_data;
        $this->assertEquals(2, $data2['level']);
        $this->assertEquals('Kayu', $data2['title']);
        $this->assertEquals(0, $data2['progress']);

        // 600 EXP triggers Level 3 -> Tier Kayu
        $member->exp = 600;
        $data3 = $member->level_data;
        $this->assertEquals(3, $data3['level']);
        $this->assertEquals('Kayu', $data3['title']);

        // 1500 EXP triggers Level 6 -> Tier Batu
        $member->exp = 1500;
        $data6 = $member->level_data;
        $this->assertEquals(6, $data6['level']);
        $this->assertEquals('Batu', $data6['title']);
        $this->assertEquals('Batu', $data6['tier_name']);
        $this->assertEquals('stone.png', $data6['icon_file']);
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

        // Change from alpha to hadir -> awards 100 EXP
        $this->post(route('meetings.update_attendance', [$meeting->id, $attendance->id]), [
            'status' => 'hadir',
            'keterangan' => 'Hadir manual oleh admin',
        ])->assertSessionHas('success');

        $this->assertEquals(100, $member->fresh()->exp);
        $this->assertTrue((bool) $attendance->fresh()->exp_awarded);

        // Change from hadir to izin -> revokes 100 EXP
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

        // Ketua gets 900 EXP (+3 Level)
        $this->assertEquals(GamificationService::EVENT_LEADER_EXP, $ketua->fresh()->exp);
        // Koordinator gets 600 EXP (+2 Level)
        $this->assertEquals(GamificationService::EVENT_COORDINATOR_EXP, $koordinator->fresh()->exp);
        // Anggota gets 300 EXP (+1 Level)
        $this->assertEquals(GamificationService::EVENT_STAFF_EXP, $anggota->fresh()->exp);

        // Check badge records
        $this->assertDatabaseHas('member_badges', [
            'user_id' => $ketua->id,
            'event_id' => $event->id,
            'event_name' => 'Dies Natalis HIMA TI #7',
            'jabatan' => 'Ketua Pelaksana',
            'exp_earned' => 900,
        ]);

        $this->assertDatabaseHas('member_badges', [
            'user_id' => $anggota->id,
            'event_id' => $event->id,
            'event_name' => 'Dies Natalis HIMA TI #7',
            'jabatan' => 'Anggota Panitia',
            'exp_earned' => 300,
        ]);

        // Member profile should render the badge
        $this->actingAs($ketua, 'member');
        $profileResponse = $this->get(route('portal.profile'));
        $profileResponse->assertOk()
            ->assertSee('Dies Natalis HIMA TI #7')
            ->assertSee('Pimpinan / Ketua')
            ->assertSee('+900 EXP');
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

        // Complete event -> awards 300 EXP
        $this->post(route('events.complete', $event->id));
        $this->assertEquals(300, $member->fresh()->exp);
        $this->assertCount(1, MemberBadge::where('event_id', $event->id)->get());

        // Reopen event -> revokes 300 EXP & deletes badge
        $reopenResponse = $this->post(route('events.reopen', $event->id));
        $reopenResponse->assertSessionHas('success');

        $this->assertEquals('active', $event->fresh()->status);
        $this->assertNull($event->fresh()->completed_at);
        $this->assertEquals(0, $member->fresh()->exp);
        $this->assertCount(0, MemberBadge::where('event_id', $event->id)->get());
    }

    public function test_gamification_service_recognizes_pj_wakil_koor_and_bph_roles(): void
    {
        $service = app(GamificationService::class);

        // Leader tier (+900 EXP / +3 Level)
        $this->assertEquals(900, $service->calculateEventMemberExp('Penanggung Jawab'));
        $this->assertEquals(900, $service->calculateEventMemberExp('PJ'));
        $this->assertEquals(900, $service->calculateEventMemberExp('pj acara'));
        $this->assertEquals(900, $service->calculateEventMemberExp('Ketua Pelaksana'));
        $this->assertEquals(900, $service->calculateEventMemberExp('Project Officer'));
        $this->assertEquals(900, $service->calculateEventMemberExp('Wakil Ketua Pelaksana'));
        $this->assertEquals(900, $service->calculateEventMemberExp('Vice PO'));

        // Coordinator & Core tier (+600 EXP / +2 Level)
        $this->assertEquals(600, $service->calculateEventMemberExp('koor'));
        $this->assertEquals(600, $service->calculateEventMemberExp('Koor Divisi'));
        $this->assertEquals(600, $service->calculateEventMemberExp('Koordinator Acara'));
        $this->assertEquals(600, $service->calculateEventMemberExp('Sekretaris'));
        $this->assertEquals(600, $service->calculateEventMemberExp('Sekre'));
        $this->assertEquals(600, $service->calculateEventMemberExp('Bendahara'));
        $this->assertEquals(600, $service->calculateEventMemberExp('BPH'));

        // Staff / Anggota tier (+300 EXP / +1 Level)
        $this->assertEquals(300, $service->calculateEventMemberExp('Anggota Panitia'));
        $this->assertEquals(300, $service->calculateEventMemberExp('Staff'));
        $this->assertEquals(300, $service->calculateEventMemberExp(''));
        $this->assertEquals(300, $service->calculateEventMemberExp(null));
    }

    public function test_admin_can_update_member_role_before_and_after_event_completion(): void
    {
        $admin = $this->createAdmin();
        $member = $this->createMember(['exp' => 0]);

        $event = Event::create([
            'nama_event' => 'HIMA Fest 2026',
            'status' => 'active',
        ]);

        $division = EventDivision::create([
            'event_id' => $event->id,
            'nama_divisi' => 'Divisi Perlengkapan',
        ]);

        $eventMember = EventMember::create([
            'event_id' => $event->id,
            'division_id' => $division->id,
            'user_id' => $member->id,
            'jabatan_panitia' => 'koor',
        ]);

        $this->actingAs($admin);

        // Update role while event is still active
        $response = $this->put(route('events.members.update_role', [$event->id, $eventMember->id]), [
            'jabatan_panitia' => 'Penanggung Jawab',
        ]);
        $response->assertSessionHas('success');
        $this->assertEquals('Penanggung Jawab', $eventMember->fresh()->jabatan_panitia);

        // Complete event -> should award 900 EXP
        $this->post(route('events.complete', $event->id));
        $this->assertEquals(900, $member->fresh()->exp);

        $badge = MemberBadge::where('event_id', $event->id)->where('user_id', $member->id)->first();
        $this->assertNotNull($badge);
        $this->assertEquals('Penanggung Jawab', $badge->jabatan);
        $this->assertEquals(900, $badge->exp_earned);

        // Update role after event is already completed to 'Koordinator Divisi' -> adjustments occur
        $updateCompletedResponse = $this->put(route('events.members.update_role', [$event->id, $eventMember->id]), [
            'jabatan_panitia' => 'Koordinator Divisi',
        ]);
        $updateCompletedResponse->assertSessionHas('success');

        $this->assertEquals('Koordinator Divisi', $eventMember->fresh()->jabatan_panitia);
        $this->assertEquals('Koordinator Divisi', $badge->fresh()->jabatan);
        $this->assertEquals(600, $badge->fresh()->exp_earned);
        // 900 - (900 - 600) = 600 EXP
        $this->assertEquals(600, $member->fresh()->exp);
    }

    public function test_event_member_track_record_calculation_and_badge_persistence(): void
    {
        $admin = $this->createAdmin();
        $memberActive = $this->createMember(['nama_lengkap' => 'Panitia Rajin', 'exp' => 0]);
        $memberLazy = $this->createMember(['nama_lengkap' => 'Panitia Alpha', 'exp' => 0]);

        $event = Event::create([
            'nama_event' => 'Expo TI 2026',
            'status' => 'active',
        ]);

        $division = EventDivision::create([
            'event_id' => $event->id,
            'nama_divisi' => 'Divisi Acara',
        ]);

        $emActive = EventMember::create([
            'event_id' => $event->id,
            'division_id' => $division->id,
            'user_id' => $memberActive->id,
            'jabatan_panitia' => 'Koordinator Acara',
        ]);

        $emLazy = EventMember::create([
            'event_id' => $event->id,
            'division_id' => $division->id,
            'user_id' => $memberLazy->id,
            'jabatan_panitia' => 'Anggota Divisi',
        ]);

        // Buat 2 rapat untuk event ini
        $m1 = Meeting::create([
            'nama_rapat' => 'Rapat Perdana Expo TI',
            'created_by' => $memberActive->id,
            'event_id' => $event->id,
            'tanggal' => now()->toDateString(),
            'waktu_mulai' => '09:00:00',
            'waktu_selesai' => '11:00:00',
            'tipe_rapat' => 'event',
            'mode_rapat' => 'offline',
            'status' => 'completed',
            'unique_link' => Str::random(32),
            'absent_link' => Str::random(32),
        ]);

        $m2 = Meeting::create([
            'nama_rapat' => 'Rapat Pemantapan Expo TI',
            'created_by' => $memberActive->id,
            'event_id' => $event->id,
            'tanggal' => now()->toDateString(),
            'waktu_mulai' => '13:00:00',
            'waktu_selesai' => '15:00:00',
            'tipe_rapat' => 'event',
            'mode_rapat' => 'offline',
            'status' => 'completed',
            'unique_link' => Str::random(32),
            'absent_link' => Str::random(32),
        ]);

        // Panitia Rajin hadir di kedua rapat
        Attendance::create(['meeting_id' => $m1->id, 'user_id' => $memberActive->id, 'status' => 'hadir']);
        Attendance::create(['meeting_id' => $m2->id, 'user_id' => $memberActive->id, 'status' => 'hadir']);

        // Panitia Alpha: 1 izin, 1 alpha (0 hadir)
        Attendance::create(['meeting_id' => $m1->id, 'user_id' => $memberLazy->id, 'status' => 'izin']);
        Attendance::create(['meeting_id' => $m2->id, 'user_id' => $memberLazy->id, 'status' => 'alpha']);

        $service = app(GamificationService::class);

        // Track record Panitia Rajin: 2/2 Hadir (100% — Sangat Aktif)
        $trackActive = $service->calculateEventMemberTrackRecord($event, $memberActive->id);
        $this->assertEquals(2, $trackActive['total_meetings']);
        $this->assertEquals(2, $trackActive['attended_count']);
        $this->assertEquals(100.0, $trackActive['attendance_rate']);
        $this->assertEquals('Sangat Aktif', $trackActive['status_label']);

        // Track record Panitia Alpha: 0/2 Hadir (0% — Tidak Aktif)
        $trackLazy = $service->calculateEventMemberTrackRecord($event, $memberLazy->id);
        $this->assertEquals(2, $trackLazy['total_meetings']);
        $this->assertEquals(0, $trackLazy['attended_count']);
        $this->assertEquals(0.0, $trackLazy['attendance_rate']);
        $this->assertEquals('Tidak Aktif', $trackLazy['status_label']);
        $this->assertEquals(1, $trackLazy['izin_count']);
        $this->assertEquals(1, $trackLazy['alpha_count']);

        // Admin lihat detail event
        $this->actingAs($admin);
        $showResponse = $this->get(route('events.show', $event->id));
        $showResponse->assertOk()
            ->assertSee('Sangat Aktif')
            ->assertSee('Tidak Aktif')
            ->assertSee('2/2 Hadir (100%)');

        // Selesaikan event dan verifikasi penyimpanan track record di member_badges
        $this->post(route('events.complete', $event->id));

        $badgeActive = MemberBadge::where('event_id', $event->id)->where('user_id', $memberActive->id)->first();
        $this->assertNotNull($badgeActive);
        $this->assertNotNull($badgeActive->attendance_summary);
        $this->assertEquals(100.0, $badgeActive->track_record['attendance_rate']);
        $this->assertEquals('Sangat Aktif', $badgeActive->track_record['status_label']);

        $badgeLazy = MemberBadge::where('event_id', $event->id)->where('user_id', $memberLazy->id)->first();
        $this->assertNotNull($badgeLazy);
        $this->assertEquals('Tidak Aktif', $badgeLazy->track_record['status_label']);

        // Halaman profil pengurus menampilkan track record lencana
        $this->actingAs($memberActive, 'member');
        $profileResponse = $this->get(route('portal.profile'));
        $profileResponse->assertOk()
            ->assertSee('Expo TI 2026')
            ->assertSee('Sangat Aktif')
            ->assertSee('2/2 Rapat (100%)');
    }
}

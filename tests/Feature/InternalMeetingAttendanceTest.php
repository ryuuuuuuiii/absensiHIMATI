<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Meeting;
use App\Models\Member;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class InternalMeetingAttendanceTest extends TestCase
{
    use DatabaseTransactions;

    public function test_internal_meeting_allows_department_member_and_bph_inti_but_rejects_outside_dept(): void
    {
        $creator = Member::where('departemen', 'PSDM')->first();
        $this->assertNotNull($creator);

        $meeting = Meeting::create([
            'created_by' => $creator->id,
            'jenis_rapat' => 'internal_dept',
            'mode_rapat' => 'offline',
            'departemen' => 'PSDM',
            'fokus_bahasan' => 'Uji Coba Presensi Internal PSDM dan Inti',
            'tanggal' => now()->toDateString(),
            'waktu_mulai' => '09:00:00',
            'status' => 'scanning',
            'unique_link' => Str::lower(Str::random(12)),
            'absent_link' => Str::lower(Str::random(12)),
        ]);

        // Auto-populate roster hanya anggota departemen PSDM
        $psdmMembers = Member::where('departemen', 'PSDM')->get();
        foreach ($psdmMembers as $pm) {
            Attendance::create([
                'meeting_id' => $meeting->id,
                'user_id' => $pm->id,
                'status' => 'alpha',
                'created_at' => now(),
            ]);
        }

        $deptMember = Member::where('departemen', 'PSDM')->where('id', '!=', $creator->id)->first();
        $bphInti = Member::where('departemen', 'Inti')->first();
        $outsideKadep = Member::where('departemen', 'Medinfo')->where('jabatan', 'like', '%Kepala%')->first();
        $outsideMember = Member::where('departemen', 'Medinfo')->where('jabatan', 'not like', '%Kepala%')->first();
        $kadepPsdm = Member::where('departemen', 'PSDM')->where('is_bph', true)->first();

        $this->assertNotNull($deptMember);
        $this->assertNotNull($bphInti);
        $this->assertNotNull($outsideKadep);
        $this->assertNotNull($outsideMember);

        // Koor / Kepala Departemen PSDM (BPH departemen tersebut) wajib hadir (default terdaftar alpha)
        if ($kadepPsdm) {
            $this->assertDatabaseHas('attendances', [
                'meeting_id' => $meeting->id,
                'user_id' => $kadepPsdm->id,
                'status' => 'alpha',
            ]);
        }

        // 1. Scan anggota PSDM -> Sukses
        $resDept = $this->postJson('/meeting/process/process_scan.php', [
            'meeting_id' => $meeting->id,
            'user_id' => $deptMember->id,
            'qr_code' => $deptMember->qr_code,
        ]);
        $resDept->assertStatus(200)->assertJson(['success' => true]);
        $this->assertDatabaseHas('attendances', [
            'meeting_id' => $meeting->id,
            'user_id' => $deptMember->id,
            'status' => 'hadir',
        ]);

        // 2. Scan BPH Inti -> Sukses hadir, record baru tercipta dengan status hadir
        $resInti = $this->postJson('/meeting/process/process_scan.php', [
            'meeting_id' => $meeting->id,
            'user_id' => $bphInti->id,
            'qr_code' => $bphInti->qr_code,
        ]);
        $resInti->assertStatus(200)->assertJson([
            'success' => true,
            'user' => [
                'id' => $bphInti->id,
                'is_bph_inti' => true,
            ],
        ]);
        $this->assertDatabaseHas('attendances', [
            'meeting_id' => $meeting->id,
            'user_id' => $bphInti->id,
            'status' => 'hadir',
        ]);

        // 3. Scan Kadep departemen luar -> Ditolak (403)
        $resKadepLuar = $this->postJson('/meeting/process/process_scan.php', [
            'meeting_id' => $meeting->id,
            'user_id' => $outsideKadep->id,
            'qr_code' => $outsideKadep->qr_code,
        ]);
        $resKadepLuar->assertStatus(403)->assertJson(['success' => false]);

        // 4. Scan Anggota biasa departemen luar -> Ditolak (403)
        $resAnggotaLuar = $this->postJson('/meeting/process/process_scan.php', [
            'meeting_id' => $meeting->id,
            'user_id' => $outsideMember->id,
            'qr_code' => $outsideMember->qr_code,
        ]);
        $resAnggotaLuar->assertStatus(403)->assertJson(['success' => false]);

        // 5. Pastikan BPH Inti lainnya yang tidak hadir TIDAK dihitung alpha
        $otherIntiMembers = Member::where('departemen', 'Inti')
            ->where('id', '!=', $bphInti->id)
            ->get();

        foreach ($otherIntiMembers as $oi) {
            $this->assertDatabaseMissing('attendances', [
                'meeting_id' => $meeting->id,
                'user_id' => $oi->id,
            ]);
        }
    }

    public function test_internal_meeting_with_inti_department_only_includes_inti_members(): void
    {
        $creator = Member::where('departemen', 'Inti')->first();
        $this->assertNotNull($creator);

        $response = $this->actingAs($creator, 'member')->post('/rapat', [
            'created_by' => $creator->id,
            'jenis_rapat' => 'internal_dept',
            'mode_rapat' => 'offline',
            'departemen' => 'Inti',
            'fokus_bahasan_internal' => 'Rapat Khusus Pengurus Inti',
            'tanggal' => now()->toDateString(),
            'waktu_mulai' => '10:00',
        ]);

        $response->assertRedirect();

        $meeting = Meeting::where('departemen', 'Inti')
            ->where('fokus_bahasan', 'Rapat Khusus Pengurus Inti')
            ->first();

        $this->assertNotNull($meeting);

        // Inti itu cuman inti (hanya anggota departemen Inti)
        $intiIds = Member::where('departemen', 'Inti')->pluck('id');
        $this->assertCount(6, $intiIds);

        // Seluruh anggota Inti wajib hadir: pembuat rapat otomatis hadir, lainnya alpha
        foreach ($intiIds as $id) {
            $this->assertDatabaseHas('attendances', [
                'meeting_id' => $meeting->id,
                'user_id' => $id,
                'status' => ($id == $creator->id) ? 'hadir' : 'alpha',
            ]);
        }

        // Kadep di luar Inti TIDAK terdaftar di roster Rapat Inti
        $kadepBph = Member::where('is_bph', true)->where('departemen', '!=', 'Inti')->first();
        $this->assertNotNull($kadepBph);
        $this->assertDatabaseMissing('attendances', [
            'meeting_id' => $meeting->id,
            'user_id' => $kadepBph->id,
        ]);

        // Scan Inti lainnya -> Sukses
        $anotherInti = Member::where('departemen', 'Inti')->where('id', '!=', $creator->id)->first();
        $scanInti = $this->postJson('/meeting/process/process_scan.php', [
            'meeting_id' => $meeting->id,
            'user_id' => $anotherInti->id,
            'qr_code' => $anotherInti->qr_code,
        ]);
        $scanInti->assertStatus(200)->assertJson(['success' => true]);

        // Scan Kadep di Rapat Inti -> Ditolak (karena rapat inti hanya untuk inti)
        $scanKadep = $this->postJson('/meeting/process/process_scan.php', [
            'meeting_id' => $meeting->id,
            'user_id' => $kadepBph->id,
            'qr_code' => $kadepBph->qr_code,
        ]);
        $scanKadep->assertStatus(403)->assertJson(['success' => false]);
    }

    public function test_internal_meeting_with_bph_department_includes_both_inti_and_kadep(): void
    {
        $creator = Member::where('departemen', 'Inti')->first();
        $this->assertNotNull($creator);

        $response = $this->actingAs($creator, 'member')->post('/rapat', [
            'created_by' => $creator->id,
            'jenis_rapat' => 'internal_dept',
            'mode_rapat' => 'offline',
            'departemen' => 'BPH',
            'fokus_bahasan_internal' => 'Rapat Koordinasi BPH dan Kepala Departemen',
            'tanggal' => now()->toDateString(),
            'waktu_mulai' => '11:00',
        ]);

        $response->assertRedirect();

        $meeting = Meeting::where('departemen', 'BPH')
            ->where('fokus_bahasan', 'Rapat Koordinasi BPH dan Kepala Departemen')
            ->first();

        $this->assertNotNull($meeting);

        // BPH terdiri dari pengurus Inti dan para Kepala Departemen
        $expectedBphIds = Member::where('departemen', 'Inti')
            ->orWhere('is_bph', true)
            ->pluck('id');
        $this->assertCount(10, $expectedBphIds);

        // Pengurus Inti dan Kadep wajib hadir: pembuat rapat otomatis hadir, lainnya alpha
        foreach ($expectedBphIds as $id) {
            $this->assertDatabaseHas('attendances', [
                'meeting_id' => $meeting->id,
                'user_id' => $id,
                'status' => ($id == $creator->id) ? 'hadir' : 'alpha',
            ]);
        }

        // Scan Inti lainnya -> Sukses
        $anotherInti = Member::where('departemen', 'Inti')->where('id', '!=', $creator->id)->first();
        $scanInti = $this->postJson('/meeting/process/process_scan.php', [
            'meeting_id' => $meeting->id,
            'user_id' => $anotherInti->id,
            'qr_code' => $anotherInti->qr_code,
        ]);
        $scanInti->assertStatus(200)->assertJson(['success' => true]);

        // Scan Kadep -> Sukses
        $kadepBph = Member::where('is_bph', true)->where('departemen', '!=', 'Inti')->first();
        $scanKadep = $this->postJson('/meeting/process/process_scan.php', [
            'meeting_id' => $meeting->id,
            'user_id' => $kadepBph->id,
            'qr_code' => $kadepBph->qr_code,
        ]);
        $scanKadep->assertStatus(200)->assertJson(['success' => true]);

        // Scan anggota biasa non-BPH -> Ditolak (403)
        $regularMember = Member::where('is_bph', false)->where('departemen', '!=', 'Inti')->first();
        if ($regularMember) {
            $scanFail = $this->postJson('/meeting/process/process_scan.php', [
                'meeting_id' => $meeting->id,
                'user_id' => $regularMember->id,
                'qr_code' => $regularMember->qr_code,
            ]);
            $scanFail->assertStatus(403)->assertJson(['success' => false]);
        }
    }

    public function test_hasil_page_renders_live_attendance_table_and_provides_json_updates(): void
    {
        $creator = Member::first();
        $this->assertNotNull($creator);

        $meeting = Meeting::create([
            'created_by' => $creator->id,
            'jenis_rapat' => 'internal_dept',
            'mode_rapat' => 'online',
            'departemen' => 'PSDM',
            'fokus_bahasan' => 'Rapat Online PSDM Live Data',
            'tanggal' => now()->toDateString(),
            'waktu_mulai' => '10:00:00',
            'status' => 'scanning',
            'unique_link' => Str::lower(Str::random(12)),
            'absent_link' => Str::lower(Str::random(12)),
        ]);

        $attendee = Member::where('departemen', 'PSDM')->first();
        Attendance::create([
            'meeting_id' => $meeting->id,
            'user_id' => $attendee->id,
            'status' => 'hadir',
            'scanned_at' => now(),
            'scan_method' => 'online_link',
            'created_at' => now(),
        ]);

        // 1. GET page HTML
        $response = $this->get('/rapat/'.$meeting->unique_link.'/hasil');
        $response->assertStatus(200);
        $response->assertSee('Data Presensi Masuk');
        $response->assertSee('Live Otomatis');
        $response->assertSee($attendee->nama_lengkap);

        // 2. GET real-time JSON
        $jsonResponse = $this->getJson('/rapat/'.$meeting->unique_link.'/hasil?json=1');
        $jsonResponse->assertStatus(200);
        $jsonResponse->assertJsonStructure([
            'success',
            'stats' => ['total', 'hadir', 'izin', 'sakit', 'alpha'],
            'attendances' => [
                '*' => ['id', 'user_id', 'nama', 'departemen', 'status', 'scanned_at', 'scan_method'],
            ],
        ]);
        $jsonResponse->assertJsonFragment([
            'nama' => $attendee->nama_lengkap,
            'status' => 'hadir',
        ]);
    }

    public function test_public_online_meeting_can_be_finished_and_closes_attendance(): void
    {
        $creator = Member::first();
        $this->assertNotNull($creator);

        $meeting = Meeting::create([
            'created_by' => $creator->id,
            'jenis_rapat' => 'internal_dept',
            'mode_rapat' => 'online',
            'departemen' => 'PSDM',
            'fokus_bahasan' => 'Rapat Online Finishing Test',
            'tanggal' => now()->toDateString(),
            'waktu_mulai' => '11:00:00',
            'status' => 'scanning',
            'unique_link' => Str::lower(Str::random(12)),
            'absent_link' => Str::lower(Str::random(12)),
        ]);

        $attendee = Member::where('departemen', 'PSDM')->first();

        // 1. Tombol Selesaikan Rapat ada di hasil.blade.php
        $resHasil = $this->get('/rapat/'.$meeting->unique_link.'/hasil');
        $resHasil->assertStatus(200);
        $resHasil->assertSee('Selesaikan Rapat');

        // 2. Kirim POST untuk menyelesaikan rapat
        $resFinish = $this->post('/rapat/'.$meeting->unique_link.'/finish');
        $resFinish->assertRedirect('/rapat/'.$meeting->unique_link.'/hasil');

        $meeting->refresh();
        $this->assertEquals('completed', $meeting->status);
        $this->assertNotNull($meeting->waktu_selesai);

        // 3. Halaman hasil menampilkan status Rapat Selesai
        $resHasilAfter = $this->get('/rapat/'.$meeting->unique_link.'/hasil');
        $resHasilAfter->assertStatus(200);
        $resHasilAfter->assertSee('Rapat Selesai');
        $resHasilAfter->assertSee('Absensi Rapat Sudah Ditutup');

        // 4. Halaman online attend menampilkan status expired / ditutup
        $resOnline = $this->get('/rapat/'.$meeting->unique_link.'/online');
        $resOnline->assertStatus(200);
        $resOnline->assertSee('Batas Waktu Absensi Telah Berakhir');

        // 5. Submit absensi setelah rapat selesai ditolak
        $resSubmit = $this->post('/rapat/'.$meeting->unique_link.'/online', [
            'user_id' => $attendee->id,
            'status' => 'hadir',
        ]);
        $resSubmit->assertSessionHas('error');
    }

    public function test_meeting_creator_automatically_marked_as_hadir_when_in_roster(): void
    {
        $creator = Member::where('departemen', 'PSDM')->first();
        $this->assertNotNull($creator);

        // Test via MeetingController::store
        $response = $this->actingAs($creator, 'member')->post(route('meetings.store'), [
            'created_by' => $creator->id,
            'jenis_rapat' => 'internal_dept',
            'mode_rapat' => 'offline',
            'departemen' => 'PSDM',
            'tanggal' => now()->toDateString(),
            'waktu_mulai' => '10:00',
            'fokus_bahasan_internal' => 'Uji Coba Auto Hadir Pembuat Rapat',
        ]);

        $meeting = Meeting::where('fokus_bahasan', 'Uji Coba Auto Hadir Pembuat Rapat')->latest()->first();
        $this->assertNotNull($meeting);
        $response->assertRedirect(route('meetings.scan', $meeting->id));

        $creatorAttendance = Attendance::where('meeting_id', $meeting->id)
            ->where('user_id', $creator->id)
            ->first();

        $this->assertNotNull($creatorAttendance);
        $this->assertEquals('hadir', $creatorAttendance->status);
        $this->assertEquals('manual', $creatorAttendance->scan_method);
        $this->assertNotNull($creatorAttendance->scanned_at);

        // Member lain di departemen harus tetap berstatus alpha
        $otherMember = Member::where('departemen', 'PSDM')->where('id', '!=', $creator->id)->first();
        if ($otherMember) {
            $otherAttendance = Attendance::where('meeting_id', $meeting->id)
                ->where('user_id', $otherMember->id)
                ->first();
            $this->assertNotNull($otherAttendance);
            $this->assertEquals('alpha', $otherAttendance->status);
        }

        // Bypass cooldown untuk pembuatan rapat kedua dalam satu test runner
        DB::table('meetings')->where('id', $meeting->id)->update(['created_at' => now()->subMinutes(5)]);

        // Test via PublicMeetingController::store (/rapat)
        $responsePublic = $this->actingAs($creator, 'member')->post(route('public.meeting.store'), [
            'created_by' => $creator->id,
            'jenis_rapat' => 'internal_dept',
            'mode_rapat' => 'offline',
            'departemen' => 'PSDM',
            'tanggal' => now()->toDateString(),
            'waktu_mulai' => '11:00',
            'fokus_bahasan_internal' => 'Uji Coba Auto Hadir Public Meeting',
        ]);

        $publicMeeting = Meeting::where('fokus_bahasan', 'Uji Coba Auto Hadir Public Meeting')->latest()->first();
        $this->assertNotNull($publicMeeting);
        $responsePublic->assertRedirect(route('public.hasil', $publicMeeting->unique_link));

        $publicCreatorAttendance = Attendance::where('meeting_id', $publicMeeting->id)
            ->where('user_id', $creator->id)
            ->first();

        $this->assertNotNull($publicCreatorAttendance);
        $this->assertEquals('hadir', $publicCreatorAttendance->status);
        $this->assertEquals('manual', $publicCreatorAttendance->scan_method);
        $this->assertNotNull($publicCreatorAttendance->scanned_at);
    }

    public function test_resumed_meeting_with_past_time_remains_open_until_explicitly_completed(): void
    {
        $creator = Member::first();
        $this->assertNotNull($creator);

        // Simulasi rapat yang dibuat kemarin atau beberapa jam lalu dan belum diklik 'selesai'
        $meeting = Meeting::create([
            'created_by' => $creator->id,
            'jenis_rapat' => 'internal_dept',
            'mode_rapat' => 'online',
            'departemen' => 'PSDM',
            'fokus_bahasan' => 'Rapat Lama Dilanjutkan',
            'tanggal' => now()->subDay()->toDateString(), // Kemarin
            'waktu_mulai' => '08:00:00', // Sudah lewat lebih dari batas jam
            'status' => 'scanning', // Masih aktif/scanning (belum diklik selesai)
            'unique_link' => Str::lower(Str::random(12)),
            'absent_link' => Str::lower(Str::random(12)),
        ]);

        $attendee = Member::where('departemen', 'PSDM')->first();
        $this->assertNotNull($attendee);

        // Peserta membuka halaman presensi online
        $resOnline = $this->get('/rapat/'.$meeting->unique_link.'/online');
        $resOnline->assertStatus(200);
        $resOnline->assertSee('Isi Presensi Kehadiran');
        $resOnline->assertDontSee('Batas Waktu Absensi Telah Berakhir');

        // Peserta mengisi presensi online
        $resSubmit = $this->post('/rapat/'.$meeting->unique_link.'/online', [
            'user_id' => $attendee->id,
            'status' => 'hadir',
            'keterangan' => 'Hadir via online',
        ]);
        $resSubmit->assertSessionHas('success');

        $attRecord = Attendance::where('meeting_id', $meeting->id)
            ->where('user_id', $attendee->id)
            ->first();
        $this->assertNotNull($attRecord);
        $this->assertEquals('hadir', $attRecord->status);

        // Buka form izin juga harus tetap bisa diakses karena status rapat masih scanning
        $resAbsent = $this->get('/rapat/'.$meeting->absent_link.'/absent');
        $resAbsent->assertStatus(200);
        $resAbsent->assertSee('Kirim Surat Keterangan / Izin');

        // Sekarang host menyelesaikan rapat
        $this->post('/rapat/'.$meeting->unique_link.'/finish');
        $meeting->refresh();
        $this->assertEquals('completed', $meeting->status);

        // Setelah selesai, barulah ditutup
        $resOnlineClosed = $this->get('/rapat/'.$meeting->unique_link.'/online');
        $resOnlineClosed->assertStatus(200);
        $resOnlineClosed->assertSee('Rapat Telah Selesai');
        $resOnlineClosed->assertDontSee('Isi Presensi Kehadiran');
    }
}

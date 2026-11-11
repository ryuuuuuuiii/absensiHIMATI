<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Meeting;
use App\Models\Member;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
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
        $outsideKadep = Member::where('departemen', 'Medinfo')->where(function ($q) {
            $q->where('jabatan', 'like', '%Kepala%')->orWhere('jabatan', 'like', '%Koordinator%');
        })->first();
        $outsideMember = Member::where('departemen', 'Medinfo')
            ->where('jabatan', 'not like', '%Kepala%')
            ->where('jabatan', 'not like', '%Koordinator%')
            ->first();
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
        $resOnline = $this->actingAs($attendee, 'member')->get('/rapat/'.$meeting->unique_link.'/online');
        $resOnline->assertStatus(200);
        $resOnline->assertSee('Batas Waktu Absensi Telah Berakhir');

        // 5. Submit absensi setelah rapat selesai ditolak
        $resSubmit = $this->actingAs($attendee, 'member')->post('/rapat/'.$meeting->unique_link.'/online', [
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

        // Peserta membuka halaman presensi online (wajib login akun kepengurusan)
        $resOnline = $this->actingAs($attendee, 'member')->get('/rapat/'.$meeting->unique_link.'/online');
        $resOnline->assertStatus(200);
        $resOnline->assertSee('Form Presensi Kehadiran Online');
        $resOnline->assertSee($attendee->nama_lengkap);
        $resOnline->assertDontSee('Batas Waktu Absensi Telah Berakhir');

        // Peserta mengisi presensi online (otomatis dari data akun terautentikasi)
        $resSubmit = $this->actingAs($attendee, 'member')->post('/rapat/'.$meeting->unique_link.'/online', [
            'status' => 'hadir',
            'keterangan' => 'Hadir via online',
            'bukti_foto' => UploadedFile::fake()->image('bukti_kehadiran.jpg'),
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
        $resOnlineClosed = $this->actingAs($attendee, 'member')->get('/rapat/'.$meeting->unique_link.'/online');
        $resOnlineClosed->assertStatus(200);
        $resOnlineClosed->assertSee('Rapat Telah Selesai');
        $resOnlineClosed->assertDontSee('Form Presensi Kehadiran Online');
    }

    public function test_scanning_already_present_member_returns_duplicate_response(): void
    {
        $creator = Member::where('departemen', 'Inti')->first();
        $this->assertNotNull($creator);

        $meeting = Meeting::create([
            'created_by' => $creator->id,
            'jenis_rapat' => 'perdana',
            'mode_rapat' => 'offline',
            'fokus_bahasan' => 'Uji Coba Duplikasi Scan',
            'tanggal' => now()->toDateString(),
            'waktu_mulai' => '10:00:00',
            'status' => 'scanning',
            'unique_link' => Str::lower(Str::random(12)),
            'absent_link' => Str::lower(Str::random(12)),
        ]);

        $member = Member::where('departemen', '!=', 'Inti')->first();
        $this->assertNotNull($member);

        // Scan pertama -> Sukses hadir
        $firstScan = $this->postJson('/meeting/process/process_scan.php', [
            'meeting_id' => $meeting->id,
            'user_id' => $member->id,
            'qr_code' => $member->qr_code,
        ]);
        $firstScan->assertStatus(200)->assertJson([
            'success' => true,
            'user' => [
                'id' => $member->id,
                'qr_code' => $member->qr_code,
            ],
        ]);

        // Scan kedua -> Ditolak sebagai duplicate
        $secondScan = $this->postJson('/meeting/process/process_scan.php', [
            'meeting_id' => $meeting->id,
            'user_id' => $member->id,
            'qr_code' => $member->qr_code,
        ]);
        $secondScan->assertStatus(200)->assertJson([
            'success' => false,
            'duplicate' => true,
        ]);

        // Pastikan record di database tetap 1 dan status hadir
        $this->assertEquals(
            1,
            Attendance::where('meeting_id', $meeting->id)->where('user_id', $member->id)->where('status', 'hadir')->count()
        );
    }

    public function test_unauthenticated_guest_opening_online_attend_is_redirected_to_login(): void
    {
        $creator = Member::where('departemen', 'Inti')->first();
        $meeting = Meeting::create([
            'created_by' => $creator->id,
            'jenis_rapat' => 'internal_dept',
            'mode_rapat' => 'online',
            'departemen' => 'PSDM',
            'fokus_bahasan' => 'Rapat Online Auth Guard Check',
            'tanggal' => now()->toDateString(),
            'waktu_mulai' => now()->format('H:i:s'),
            'status' => 'scanning',
            'unique_link' => Str::lower(Str::random(12)),
            'absent_link' => Str::lower(Str::random(12)),
        ]);

        // Tamu (guest belum login) membuka link online -> dialihkan ke login dengan pesan info
        $response = $this->get('/rapat/'.$meeting->unique_link.'/online');
        $response->assertRedirect(route('login'));
        $response->assertSessionHas('info');

        // Tamu mencoba POST submit langsung -> dialihkan juga ke login
        $resSubmit = $this->post('/rapat/'.$meeting->unique_link.'/online', [
            'status' => 'hadir',
        ]);
        $resSubmit->assertRedirect(route('login'));
    }

    public function test_authenticated_member_submits_online_attend_automatically_using_their_account(): void
    {
        $creator = Member::where('departemen', 'Inti')->first();
        $meeting = Meeting::create([
            'created_by' => $creator->id,
            'jenis_rapat' => 'internal_dept',
            'mode_rapat' => 'online',
            'departemen' => 'PSDM',
            'fokus_bahasan' => 'Rapat Online Auto Identity Record',
            'tanggal' => now()->toDateString(),
            'waktu_mulai' => now()->format('H:i:s'),
            'status' => 'scanning',
            'unique_link' => Str::lower(Str::random(12)),
            'absent_link' => Str::lower(Str::random(12)),
        ]);

        $attendee = Member::where('departemen', 'PSDM')->first();
        $this->assertNotNull($attendee);

        // Buka form saat login -> Tampil data akun terverifikasi tanpa perlu input nama
        $response = $this->actingAs($attendee, 'member')->get('/rapat/'.$meeting->unique_link.'/online');
        $response->assertStatus(200);
        $response->assertSee($attendee->nama_lengkap);
        $response->assertSee('Terverifikasi');
        $response->assertSee($attendee->departemen);
        $response->assertDontSee('Pilih Nama Anda');

        // Submit tanpa menyertakan user_id -> Otomatis tercatat atas nama akun login
        $resSubmit = $this->actingAs($attendee, 'member')->post('/rapat/'.$meeting->unique_link.'/online', [
            'status' => 'hadir',
            'keterangan' => 'Hadir melalui akun sendiri',
            'bukti_foto' => UploadedFile::fake()->image('bukti_mandiri.jpg'),
        ]);
        $resSubmit->assertSessionHas('success');

        $record = Attendance::where('meeting_id', $meeting->id)
            ->where('user_id', $attendee->id)
            ->first();
        $this->assertNotNull($record);
        $this->assertEquals('hadir', $record->status);
        $this->assertEquals('Hadir melalui akun sendiri', $record->keterangan);
        $this->assertEquals('online_link', $record->scan_method);
        $this->assertNotNull($record->bukti_foto);
    }

    public function test_submitting_online_attend_hadir_without_proof_photo_fails_validation(): void
    {
        $creator = Member::where('departemen', 'PSDM')->first();
        $meeting = Meeting::create([
            'created_by' => $creator->id,
            'jenis_rapat' => 'internal_dept',
            'mode_rapat' => 'online',
            'departemen' => 'PSDM',
            'fokus_bahasan' => 'Uji Validasi Foto Bukti Wajib',
            'tanggal' => now()->toDateString(),
            'waktu_mulai' => now()->format('H:i:s'),
            'status' => 'scanning',
            'unique_link' => Str::lower(Str::random(12)),
            'absent_link' => Str::lower(Str::random(12)),
        ]);

        $attendee = Member::where('departemen', 'PSDM')->first();

        // Hadir tanpa file foto bukti -> Gagal validasi
        $res = $this->actingAs($attendee, 'member')->post('/rapat/'.$meeting->unique_link.'/online', [
            'status' => 'hadir',
            'keterangan' => 'Mencoba absen tanpa foto bukti',
        ]);

        $res->assertSessionHasErrors(['bukti_foto']);
        $this->assertNull(Attendance::where('meeting_id', $meeting->id)->where('user_id', $attendee->id)->first());
    }

    public function test_updating_online_attend_when_proof_already_exists_allows_empty_file(): void
    {
        $creator = Member::where('departemen', 'PSDM')->first();
        $meeting = Meeting::create([
            'created_by' => $creator->id,
            'jenis_rapat' => 'internal_dept',
            'mode_rapat' => 'online',
            'departemen' => 'PSDM',
            'fokus_bahasan' => 'Uji Update Keterangan dengan Bukti Tersimpan',
            'tanggal' => now()->toDateString(),
            'waktu_mulai' => now()->format('H:i:s'),
            'status' => 'scanning',
            'unique_link' => Str::lower(Str::random(12)),
            'absent_link' => Str::lower(Str::random(12)),
        ]);

        $attendee = Member::where('departemen', 'PSDM')->first();

        // 1. Submit pertama dengan foto
        $this->actingAs($attendee, 'member')->post('/rapat/'.$meeting->unique_link.'/online', [
            'status' => 'hadir',
            'keterangan' => 'Hadir awal',
            'bukti_foto' => UploadedFile::fake()->image('bukti_pertama.jpg'),
        ])->assertSessionHas('success');

        $attendance = Attendance::where('meeting_id', $meeting->id)->where('user_id', $attendee->id)->first();
        $this->assertNotNull($attendance);
        $initialPhoto = $attendance->bukti_foto;
        $this->assertNotEmpty($initialPhoto);

        // 2. Update keterangan tanpa mengunggah file baru -> Berhasil dan foto tidak hilang
        $this->actingAs($attendee, 'member')->post('/rapat/'.$meeting->unique_link.'/online', [
            'status' => 'hadir',
            'keterangan' => 'Keterangan diperbarui',
        ])->assertSessionHas('success');

        $attendance->refresh();
        $this->assertEquals('Keterangan diperbarui', $attendance->keterangan);
        $this->assertEquals($initialPhoto, $attendance->bukti_foto);
    }

    public function test_submitting_online_attend_izin_allows_empty_file(): void
    {
        $creator = Member::where('departemen', 'PSDM')->first();
        $meeting = Meeting::create([
            'created_by' => $creator->id,
            'jenis_rapat' => 'internal_dept',
            'mode_rapat' => 'online',
            'departemen' => 'PSDM',
            'fokus_bahasan' => 'Uji Izin Pada Rapat Online',
            'tanggal' => now()->toDateString(),
            'waktu_mulai' => now()->format('H:i:s'),
            'status' => 'scanning',
            'unique_link' => Str::lower(Str::random(12)),
            'absent_link' => Str::lower(Str::random(12)),
        ]);

        $attendee = Member::where('departemen', 'PSDM')->first();

        // Izin tanpa foto bukti -> Boleh / nullable
        $this->actingAs($attendee, 'member')->post('/rapat/'.$meeting->unique_link.'/online', [
            'status' => 'izin',
            'keterangan' => 'Izin praktikum kuliah',
        ])->assertSessionHas('success');

        $attendance = Attendance::where('meeting_id', $meeting->id)->where('user_id', $attendee->id)->first();
        $this->assertNotNull($attendance);
        $this->assertEquals('izin', $attendance->status);
        $this->assertEquals('Izin praktikum kuliah', $attendance->keterangan);
    }

    public function test_ineligible_member_submitting_online_attend_is_rejected(): void
    {
        $creator = Member::where('departemen', 'Inti')->first();
        $meeting = Meeting::create([
            'created_by' => $creator->id,
            'jenis_rapat' => 'internal_dept',
            'mode_rapat' => 'online',
            'departemen' => 'PSDM',
            'fokus_bahasan' => 'Rapat Khusus PSDM Saja',
            'tanggal' => now()->toDateString(),
            'waktu_mulai' => now()->format('H:i:s'),
            'status' => 'scanning',
            'unique_link' => Str::lower(Str::random(12)),
            'absent_link' => Str::lower(Str::random(12)),
        ]);

        // Anggota dari departemen lain (bukan PSDM dan bukan Inti)
        $otherMember = Member::whereNotIn('departemen', ['PSDM', 'Inti'])->first();
        if ($otherMember) {
            // Error kelayakan tampil di halaman
            $response = $this->actingAs($otherMember, 'member')->get('/rapat/'.$meeting->unique_link.'/online');
            $response->assertStatus(200);
            $response->assertSee('Pemberitahuan Partisipasi');

            // Submit ditolak
            $resSubmit = $this->actingAs($otherMember, 'member')->post('/rapat/'.$meeting->unique_link.'/online', [
                'status' => 'hadir',
            ]);
            $resSubmit->assertSessionHas('error');
        }
    }

    public function test_meeting_creator_can_set_custom_online_meeting_end_time_and_it_expires_at_that_time(): void
    {
        $creator = Member::where('departemen', 'PSDM')->first();
        $this->assertNotNull($creator);

        // Buat rapat online dengan jam berakhir yang sudah terlewat (misal 1 jam lalu)
        $pastStartTime = now()->subHours(2)->format('H:i:s');
        $pastEndTime = now()->subHour()->format('H:i:s');

        $meeting = Meeting::create([
            'created_by' => $creator->id,
            'jenis_rapat' => 'internal_dept',
            'mode_rapat' => 'online',
            'departemen' => 'PSDM',
            'fokus_bahasan' => 'Uji Custom Jam Berakhir Online',
            'tanggal' => now()->toDateString(),
            'waktu_mulai' => $pastStartTime,
            'waktu_selesai' => $pastEndTime,
            'status' => 'scanning',
            'unique_link' => Str::lower(Str::random(12)),
            'absent_link' => Str::lower(Str::random(12)),
        ]);

        $attendee = Member::where('departemen', 'PSDM')->first();

        // 1. Model mendeteksi sudah expired sesuai waktu_selesai buatan pembuat rapat
        $this->assertTrue($meeting->isOnlineExpired());

        // 2. Akses halaman online attend menampilkan batas waktu telah berakhir
        $response = $this->actingAs($attendee, 'member')->get('/rapat/'.$meeting->unique_link.'/online');
        $response->assertStatus(200);
        $response->assertSee('Batas Waktu Absensi Telah Berakhir');
        $response->assertDontSee('Kirim Presensi Kehadiran');

        // 3. Submit absensi ditolak karena melewati batas waktu
        $resSubmit = $this->actingAs($attendee, 'member')->post('/rapat/'.$meeting->unique_link.'/online', [
            'status' => 'hadir',
            'bukti_foto' => UploadedFile::fake()->image('bukti.jpg'),
        ]);
        $resSubmit->assertSessionHas('error');
    }

    public function test_meeting_creator_can_extend_online_meeting_end_time(): void
    {
        $creator = Member::where('departemen', 'PSDM')->first();
        $this->assertNotNull($creator);

        // Awalnya expired (1 jam lalu)
        $pastStartTime = now()->subHours(2)->format('H:i:s');
        $pastEndTime = now()->subHour()->format('H:i:s');

        $meeting = Meeting::create([
            'created_by' => $creator->id,
            'jenis_rapat' => 'internal_dept',
            'mode_rapat' => 'online',
            'departemen' => 'PSDM',
            'fokus_bahasan' => 'Uji Perpanjang Jam Berakhir Online',
            'tanggal' => now()->toDateString(),
            'waktu_mulai' => $pastStartTime,
            'waktu_selesai' => $pastEndTime,
            'status' => 'scanning',
            'unique_link' => Str::lower(Str::random(12)),
            'absent_link' => Str::lower(Str::random(12)),
        ]);

        $this->assertTrue($meeting->isOnlineExpired());

        // Pembuat rapat memperpanjang waktu hingga 2 jam ke depan
        $newEndTime = now()->addHours(2)->format('H:i');
        $resUpdate = $this->post('/rapat/'.$meeting->unique_link.'/update-expiry', [
            'waktu_selesai' => $newEndTime,
        ]);
        $resUpdate->assertSessionHas('success');

        $meeting->refresh();
        $this->assertEquals(date('H:i:s', strtotime($newEndTime)), $meeting->waktu_selesai);
        $this->assertFalse($meeting->isOnlineExpired());

        // Peserta sekarang bisa membuka form dan submit kehadiran
        $attendee = Member::where('departemen', 'PSDM')->first();
        $response = $this->actingAs($attendee, 'member')->get('/rapat/'.$meeting->unique_link.'/online');
        $response->assertStatus(200);
        $response->assertSee('Form Presensi Kehadiran Online');
    }

    public function test_unauthenticated_user_cannot_access_or_submit_absent_form(): void
    {
        $creator = Member::where('departemen', 'Inti')->first();
        $meeting = Meeting::create([
            'created_by' => $creator->id,
            'jenis_rapat' => 'perdana',
            'mode_rapat' => 'offline',
            'fokus_bahasan' => 'Uji Izin Wajib Login',
            'tanggal' => now()->toDateString(),
            'waktu_mulai' => '14:00:00',
            'status' => 'scanning',
            'unique_link' => Str::lower(Str::random(12)),
            'absent_link' => Str::lower(Str::random(12)),
        ]);

        // Akses tanpa login harus diarahkan ke login
        $response = $this->get('/rapat/'.$meeting->absent_link.'/absent');
        $response->assertRedirect(route('login'));
        $response->assertSessionHas('info');

        // Submit tanpa login harus diarahkan ke login
        $submit = $this->post('/rapat/'.$meeting->absent_link.'/absent', [
            'status' => 'izin',
            'keterangan' => 'Mencoba submit tanpa login',
        ]);
        $submit->assertRedirect(route('login'));
    }

    public function test_authenticated_member_submits_absent_form_automatically_tied_to_own_account(): void
    {
        $creator = Member::where('departemen', 'Inti')->first();
        $members = Member::where('departemen', '!=', 'Inti')->take(2)->get();
        $memberA = $members[0];
        $memberB = $members[1];

        $meeting = Meeting::create([
            'created_by' => $creator->id,
            'jenis_rapat' => 'perdana',
            'mode_rapat' => 'offline',
            'fokus_bahasan' => 'Uji Izin Akun Terkunci',
            'tanggal' => now()->toDateString(),
            'waktu_mulai' => '14:00:00',
            'status' => 'scanning',
            'unique_link' => Str::lower(Str::random(12)),
            'absent_link' => Str::lower(Str::random(12)),
        ]);

        Attendance::create([
            'meeting_id' => $meeting->id,
            'user_id' => $memberA->id,
            'status' => 'alpha',
        ]);

        Attendance::create([
            'meeting_id' => $meeting->id,
            'user_id' => $memberB->id,
            'status' => 'alpha',
        ]);

        // Login sebagai Member A
        $response = $this->actingAs($memberA, 'member')->get('/rapat/'.$meeting->absent_link.'/absent');
        $response->assertStatus(200);
        $response->assertSee($memberA->nama_lengkap);
        $response->assertDontSee('<select name="user_id"', false);

        // Member A mencoba mengirim user_id = Member B (mencoba curang)
        $submit = $this->actingAs($memberA, 'member')->post('/rapat/'.$meeting->absent_link.'/absent', [
            'user_id' => $memberB->id,
            'status' => 'izin',
            'keterangan' => 'Ada acara keluarga mendadak',
        ]);
        $submit->assertSessionHas('success');

        // Yang tercatat izin HARUS Member A (akun yang sedang login), BUKAN Member B
        $attA = Attendance::where('meeting_id', $meeting->id)->where('user_id', $memberA->id)->first();
        $this->assertEquals('izin', $attA->status);
        $this->assertEquals('Ada acara keluarga mendadak', $attA->keterangan);

        $attB = Attendance::where('meeting_id', $meeting->id)->where('user_id', $memberB->id)->first();
        $this->assertEquals('alpha', $attB->status); // Member B tetap alpha, tidak terpengaruh
    }
}

<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Event;
use App\Models\Meeting;
use App\Models\Member;
use App\Services\FileUploadService;
use App\Services\MeetingSpamProtectionService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Tests\TestCase;

class KegiatanAttendanceTest extends TestCase
{
    use DatabaseTransactions;

    /** @var array<int, string> */
    private array $uploadedProofs = [];

    protected function tearDown(): void
    {
        foreach ($this->uploadedProofs as $filename) {
            app(FileUploadService::class)->delete('bukti', $filename);
        }

        parent::tearDown();
    }

    public function test_kegiatan_created_from_form_appears_on_participant_dashboard_with_photo_action(): void
    {
        $creator = Member::where('departemen', 'Inti')->first();
        $this->assertNotNull($creator);

        $this->actingAs($creator, 'member')->post('/rapat', [
            'created_by' => $creator->id,
            'kategori' => 'kegiatan',
            'jenis_rapat' => 'internal_dept',
            'mode_rapat' => 'offline',
            'departemen' => 'Inti',
            'fokus_bahasan_internal' => 'Bakti Sosial Himpunan',
            'tanggal' => now()->toDateString(),
            'waktu_mulai' => '10:00',
        ])->assertRedirect();

        $meeting = Meeting::where('fokus_bahasan', 'Bakti Sosial Himpunan')->firstOrFail();
        $this->assertSame('kegiatan', $meeting->kategori);

        $participant = Member::where('departemen', 'Inti')->where('id', '!=', $creator->id)->firstOrFail();

        $this->actingAs($participant, 'member')->get('/portal')
            ->assertOk()
            ->assertSee('Bakti Sosial Himpunan')
            ->assertSee('Foto Hadir')
            ->assertSee(route('public.online', $meeting->unique_link));
    }

    public function test_kegiatan_creator_is_not_automatically_present_and_must_submit_photo_documentation(): void
    {
        $creator = Member::where('departemen', 'Inti')->firstOrFail();
        $this->resetCreationCooldown($creator->id);
        $initialExp = (int) $creator->fresh()->exp;

        $this->actingAs($creator, 'member')->post('/rapat', [
            'created_by' => $creator->id,
            'kategori' => 'kegiatan',
            'jenis_rapat' => 'internal_dept',
            'mode_rapat' => 'offline',
            'departemen' => 'Inti',
            'fokus_bahasan_internal' => 'Kegiatan Bersih Kampus TI',
            'tanggal' => now()->toDateString(),
            'waktu_mulai' => '08:00',
        ])->assertRedirect();

        $meeting = Meeting::where('fokus_bahasan', 'Kegiatan Bersih Kampus TI')->firstOrFail();

        // Creator attendance MUST be alpha initially (no auto-hadir for kegiatan)
        $creatorAttendance = Attendance::where('meeting_id', $meeting->id)
            ->where('user_id', $creator->id)
            ->firstOrFail();

        $this->assertSame('alpha', $creatorAttendance->status);
        $this->assertNull($creatorAttendance->scanned_at);
        $this->assertNull($creatorAttendance->bukti_foto);
        $this->assertFalse((bool) $creatorAttendance->exp_awarded);
        $this->assertSame($initialExp, (int) $creator->fresh()->exp);

        // Creator submits photo documentation
        $this->actingAs($creator, 'member')
            ->post(route('public.online.submit', $meeting->unique_link), [
                'status' => 'hadir',
                'konfirmasi_wajah' => '1',
                'bukti_foto' => UploadedFile::fake()->image('creator_selfie.jpg'),
            ])->assertSessionHas('success');

        $freshAttendance = $creatorAttendance->fresh();
        $this->assertSame('hadir', $freshAttendance->status);
        $this->assertNotNull($freshAttendance->scanned_at);
        $this->assertNotNull($freshAttendance->bukti_foto);
        $this->assertTrue((bool) $freshAttendance->exp_awarded);
        $this->uploadedProofs[] = $freshAttendance->bukti_foto;
        $this->assertGreaterThan($initialExp, (int) $creator->fresh()->exp);
    }

    public function test_offline_kegiatan_requires_location_photo_documentation_to_be_present(): void
    {
        $meeting = $this->createKegiatan('offline');
        $member = $this->createMember();

        $this->actingAs($member, 'member')
            ->get(route('public.online', $meeting->unique_link))
            ->assertOk()
            ->assertSee('lokasi');

        $this->post(route('public.online.submit', $meeting->unique_link), [
            'status' => 'hadir',
        ])->assertSessionHasErrors('bukti_foto');

        $this->assertDatabaseMissing('attendances', [
            'meeting_id' => $meeting->id,
            'user_id' => $member->id,
            'status' => 'hadir',
        ]);

        $this->post(route('public.online.submit', $meeting->unique_link), [
            'status' => 'hadir',
            'bukti_foto' => UploadedFile::fake()->image('dokumentasi_lokasi.jpg'),
        ])->assertSessionHas('success');

        $record = Attendance::where('meeting_id', $meeting->id)->where('user_id', $member->id)->firstOrFail();
        $this->assertSame('hadir', $record->status);
        $this->assertSame('foto_kegiatan', $record->scan_method);
        $this->assertNotNull($record->bukti_foto);
        $this->uploadedProofs[] = $record->bukti_foto;
    }

    public function test_kegiatan_attendance_can_be_submitted_via_camera_base64(): void
    {
        $meeting = $this->createKegiatan('offline');
        $member = $this->createMember();

        // 1x1 transparent png in base64
        $base64Image = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==';

        $this->actingAs($member, 'member')
            ->post(route('public.online.submit', $meeting->unique_link), [
                'status' => 'hadir',
                'bukti_foto_base64' => $base64Image,
            ])->assertSessionHas('success');

        $record = Attendance::where('meeting_id', $meeting->id)->where('user_id', $member->id)->firstOrFail();
        $this->assertSame('hadir', $record->status);
        $this->assertSame('foto_kegiatan', $record->scan_method);
        $this->assertNotNull($record->bukti_foto);
        $this->uploadedProofs[] = $record->bukti_foto;
    }

    public function test_online_kegiatan_requires_camera_on_screenshot_without_face_checkbox(): void
    {
        $meeting = $this->createKegiatan('online');
        $member = $this->createMember();

        $this->actingAs($member, 'member')
            ->get(route('public.online', $meeting->unique_link))
            ->assertOk()
            ->assertSee('Foto Dokumentasi Kegiatan');

        $this->post(route('public.online.submit', $meeting->unique_link), [
            'status' => 'hadir',
        ])->assertSessionHasErrors('bukti_foto');

        $this->post(route('public.online.submit', $meeting->unique_link), [
            'status' => 'hadir',
            'bukti_foto' => UploadedFile::fake()->image('zoom.jpg'),
        ])->assertSessionHasNoErrors();

        $record = Attendance::where('meeting_id', $meeting->id)->where('user_id', $member->id)->firstOrFail();
        $this->assertSame('hadir', $record->status);
        $this->assertSame('foto_kegiatan', $record->scan_method);
        $this->uploadedProofs[] = $record->bukti_foto;
    }

    public function test_absent_member_can_submit_izin_for_kegiatan_without_photo(): void
    {
        $meeting = $this->createKegiatan('offline');
        $member = $this->createMember();

        $this->actingAs($member, 'member')
            ->post(route('public.absent.submit', $meeting->absent_link), [
                'status' => 'izin',
                'keterangan' => 'Ada ujian di jam yang sama',
            ])->assertSessionHas('success');

        $this->assertDatabaseHas('attendances', [
            'meeting_id' => $meeting->id,
            'user_id' => $member->id,
            'status' => 'izin',
            'keterangan' => 'Ada ujian di jam yang sama',
            'scan_method' => 'absent_form',
        ]);
    }

    public function test_member_cannot_self_mark_kegiatan_presence_from_portal_without_photo(): void
    {
        $meeting = $this->createKegiatan('online');
        $member = $this->createMember();

        $attendance = Attendance::create([
            'meeting_id' => $meeting->id,
            'user_id' => $member->id,
            'status' => 'alpha',
            'created_at' => now(),
        ]);

        $this->actingAs($member, 'member')
            ->post(route('portal.presence.store', $attendance->id))
            ->assertRedirect(route('public.online', $meeting->unique_link));

        $this->assertSame('alpha', $attendance->fresh()->status);
    }

    public function test_creating_kegiatan_as_jenis_rapat_automatically_sets_kategori_kegiatan_and_populates_members(): void
    {
        $creator = Member::where('departemen', 'Inti')->firstOrFail();

        $this->actingAs($creator, 'member')->post('/rapat', [
            'created_by' => $creator->id,
            'jenis_rapat' => 'kegiatan',
            'nama_event_kegiatan' => 'Makrab Akbar HIMATI 2026',
            'target_peserta_kegiatan' => 'semua',
            'fokus_bahasan_kegiatan' => 'Malam Keakraban Mahasiswa TI',
            'mode_rapat' => 'offline',
            'tanggal' => now()->toDateString(),
            'waktu_mulai' => '19:00',
        ])->assertRedirect();

        $meeting = Meeting::where('nama_event', 'Makrab Akbar HIMATI 2026')->firstOrFail();
        $this->assertSame('kegiatan', $meeting->jenis_rapat);
        $this->assertSame('kegiatan', $meeting->kategori);
        $this->assertTrue($meeting->isKegiatan());
        $this->assertGreaterThan(0, $meeting->attendances()->count());

        // Attendances have sp_ignored = 1
        $this->assertTrue((bool) $meeting->attendances()->first()->sp_ignored);
    }

    public function test_kegiatan_can_be_created_from_registered_event(): void
    {
        $creator = Member::where('departemen', 'Inti')->firstOrFail();
        $this->resetCreationCooldown($creator->id);
        $event = Event::create([
            'nama_event' => 'HIMATI E-Sport Championship '.Str::random(4),
            'status' => 'active',
            'created_at' => now(),
        ]);

        $this->actingAs($creator, 'member')->post('/rapat', [
            'created_by' => $creator->id,
            'jenis_rapat' => 'kegiatan',
            'sumber_nama_kegiatan' => 'event',
            'kegiatan_event_id' => $event->id,
            'mode_rapat' => 'offline',
            'tanggal' => now()->toDateString(),
            'waktu_mulai' => '14:00',
        ])->assertRedirect();

        $meeting = Meeting::where('event_id', $event->id)->firstOrFail();
        $this->assertSame($event->nama_event, $meeting->nama_event);
        $this->assertSame('kegiatan', $meeting->jenis_rapat);
        $this->assertSame('kegiatan', $meeting->kategori);
        $this->assertNull($meeting->departemen);

        // Verify all created attendances have sp_ignored = 0 (karena event diharuskan untuk seluruh pengurus)
        $this->assertSame('wajib', $meeting->sifat_kegiatan);
        $attendances = $meeting->attendances()->get();
        $this->assertGreaterThan(0, $attendances->count());
        foreach ($attendances as $att) {
            $this->assertFalse((bool) $att->sp_ignored);
        }
    }

    public function test_kegiatan_attendance_is_free_from_sp_penalties_for_absent_members(): void
    {
        $creator = Member::where('departemen', 'Inti')->firstOrFail();
        $meeting = Meeting::create([
            'created_by' => $creator->id,
            'jenis_rapat' => 'kegiatan',
            'kategori' => 'kegiatan',
            'sifat_kegiatan' => 'dibebaskan',
            'mode_rapat' => 'offline',
            'nama_event' => 'Bakti Sosial Sukarela',
            'tanggal' => now()->toDateString(),
            'waktu_mulai' => '08:00',
            'waktu_selesai' => '12:00',
            'status' => 'completed',
            'unique_link' => Str::lower(Str::random(12)),
            'absent_link' => Str::lower(Str::random(12)),
        ]);

        $spamService = app(MeetingSpamProtectionService::class);
        $this->assertFalse($spamService->isMeetingValidForSp($meeting));

        $member = $this->createMember();
        $this->assertNull($meeting->checkMemberEligibility($member));
    }

    public function test_kegiatan_from_event_is_automatically_wajib_for_all_pengurus(): void
    {
        $creator = Member::where('departemen', 'Inti')->firstOrFail();
        $this->resetCreationCooldown($creator->id);
        $event = Event::create([
            'nama_event' => 'Dies Natalis HIMA TI',
            'deskripsi' => 'Perayaan tahunan',
            'status' => 'active',
            'tanggal_mulai' => now()->toDateString(),
        ]);

        $this->actingAs($creator, 'member')->post('/rapat', [
            'created_by' => $creator->id,
            'jenis_rapat' => 'kegiatan',
            'mode_rapat' => 'offline',
            'sumber_nama_kegiatan' => 'event',
            'kegiatan_event_id' => $event->id,
            'fokus_bahasan_kegiatan' => 'Puncak Acara Dies Natalis',
            'tanggal' => now()->toDateString(),
            'waktu_mulai' => '09:00',
        ])->assertRedirect();

        $meeting = Meeting::where('nama_event', 'Dies Natalis HIMA TI')->firstOrFail();
        $this->assertSame('kegiatan', $meeting->kategori);
        $this->assertSame('wajib', $meeting->sifat_kegiatan);
        $this->assertTrue($meeting->isWajib());
        $this->assertFalse($meeting->isDibebaskan());

        // Attendances harus sp_ignored = 0 karena diharuskan untuk seluruh pengurus
        $sampleAtt = $meeting->attendances()->first();
        $this->assertNotNull($sampleAtt);
        $this->assertEquals(0, $sampleAtt->sp_ignored);
    }

    public function test_kegiatan_manual_with_semua_pengurus_is_wajib_for_all_pengurus(): void
    {
        $creator = Member::where('departemen', 'Inti')->firstOrFail();
        $this->resetCreationCooldown($creator->id);

        $this->actingAs($creator, 'member')->post('/rapat', [
            'created_by' => $creator->id,
            'jenis_rapat' => 'kegiatan',
            'mode_rapat' => 'offline',
            'sumber_nama_kegiatan' => 'manual',
            'nama_event_kegiatan' => 'Pelatihan Kepemimpinan Pengurus',
            'sifat_kegiatan_manual' => 'semua',
            'fokus_bahasan_kegiatan' => 'Materi LKMM-TD',
            'tanggal' => now()->toDateString(),
            'waktu_mulai' => '08:00',
        ])->assertRedirect();

        $meeting = Meeting::where('nama_event', 'Pelatihan Kepemimpinan Pengurus')->firstOrFail();
        $this->assertSame('kegiatan', $meeting->kategori);
        $this->assertSame('wajib', $meeting->sifat_kegiatan);
        $this->assertTrue($meeting->isWajib());
        $this->assertFalse($meeting->isDibebaskan());

        $sampleAtt = $meeting->attendances()->first();
        $this->assertNotNull($sampleAtt);
        $this->assertEquals(0, $sampleAtt->sp_ignored);
    }

    public function test_kegiatan_manual_with_dibebaskan_is_exempt_from_sp(): void
    {
        $creator = Member::where('departemen', 'Inti')->firstOrFail();
        $this->resetCreationCooldown($creator->id);

        $this->actingAs($creator, 'member')->post('/rapat', [
            'created_by' => $creator->id,
            'jenis_rapat' => 'kegiatan',
            'mode_rapat' => 'offline',
            'sumber_nama_kegiatan' => 'manual',
            'nama_event_kegiatan' => 'Buka Puasa Bersama Himpunan',
            'sifat_kegiatan_manual' => 'dibebaskan',
            'fokus_bahasan_kegiatan' => 'Silaturahmi & Buber',
            'tanggal' => now()->toDateString(),
            'waktu_mulai' => '17:00',
        ])->assertRedirect();

        $meeting = Meeting::where('nama_event', 'Buka Puasa Bersama Himpunan')->firstOrFail();
        $this->assertSame('kegiatan', $meeting->kategori);
        $this->assertSame('dibebaskan', $meeting->sifat_kegiatan);
        $this->assertTrue($meeting->isDibebaskan());
        $this->assertFalse($meeting->isWajib());

        // Attendances harus sp_ignored = 1 karena dibebaskan
        $sampleAtt = $meeting->attendances()->first();
        $this->assertNotNull($sampleAtt);
        $this->assertEquals(1, $sampleAtt->sp_ignored);
    }

    private function createKegiatan(string $mode): Meeting
    {
        $creator = Member::first() ?? $this->createMember();

        return Meeting::create([
            'created_by' => $creator->id,
            'jenis_rapat' => 'perdana',
            'kategori' => 'kegiatan',
            'mode_rapat' => $mode,
            'nama_event' => 'Kegiatan Uji',
            'fokus_bahasan' => 'Kegiatan Uji '.Str::random(5),
            'tanggal' => now()->toDateString(),
            'waktu_mulai' => now()->subMinutes(10)->format('H:i:s'),
            'waktu_selesai' => now()->addHours(2)->format('H:i:s'),
            'status' => 'scanning',
            'unique_link' => Str::lower(Str::random(12)),
            'absent_link' => Str::lower(Str::random(12)),
        ]);
    }

    private function createMember(): Member
    {
        return Member::create([
            'nama_lengkap' => 'Peserta Kegiatan '.Str::random(6),
            'departemen' => 'PSDM',
            'jabatan' => 'Anggota',
            'email' => 'kegiatan.'.Str::lower(Str::random(8)).'@himati.test',
            'password' => 'password123',
            'qr_code' => 'HIMATI-TEST-'.Str::upper(Str::random(8)),
            'is_bph' => false,
        ]);
    }

    private function resetCreationCooldown(int $memberId): void
    {
        RateLimiter::clear('create-meeting:member:'.$memberId);
        Meeting::where('created_by', $memberId)->update(['created_at' => now()->subMinutes(5)]);
    }
}

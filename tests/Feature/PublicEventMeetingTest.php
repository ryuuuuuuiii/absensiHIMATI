<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\EventDivision;
use App\Models\EventMember;
use App\Models\Meeting;
use App\Models\Member;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Tests\TestCase;

class PublicEventMeetingTest extends TestCase
{
    use DatabaseTransactions;

    public function test_public_meeting_create_page_renders_event_select(): void
    {
        $event = Event::create([
            'nama_event' => 'Test Event Selection '.uniqid(),
            'status' => 'active',
        ]);

        $this->get('/rapat')->assertRedirect(route('login'));

        $member = Member::first() ?? Member::create([
            'nama_lengkap' => 'Tester Event',
            'nim' => '12345670',
            'email' => 'tester@test.com',
            'departemen' => 'BPH',
            'jabatan' => 'Ketua',
        ]);

        $response = $this->actingAs($member, 'member')->get('/rapat');

        $response->assertStatus(200);
        $response->assertSee('select name="event_id"', false);
        $response->assertSee('select name="divisi_id"', false);
        $response->assertSee($event->nama_event);
    }

    public function test_get_event_data_api_is_accessible_publicly(): void
    {
        $event = Event::create([
            'nama_event' => 'API Test Event '.uniqid(),
            'status' => 'active',
        ]);

        $division = EventDivision::create([
            'event_id' => $event->id,
            'nama_divisi' => 'Divisi Perlengkapan',
        ]);

        $response = $this->getJson("/api/events/{$event->id}/data");

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'data' => [
                'event' => [
                    'id' => $event->id,
                    'nama_event' => $event->nama_event,
                ],
                'divisions' => [
                    [
                        'id' => $division->id,
                        'nama_divisi' => 'Divisi Perlengkapan',
                    ],
                ],
            ],
        ]);
    }

    public function test_create_kepanitiaan_meeting_all_divisions(): void
    {
        $creator = Member::first() ?? Member::create([
            'nama_lengkap' => 'Creator Test',
            'nim' => '12345678',
            'email' => 'creator@test.com',
            'departemen' => 'BPH',
            'jabatan' => 'Ketua',
        ]);

        $event = Event::create([
            'nama_event' => 'Event Rapat Semua '.uniqid(),
            'status' => 'active',
        ]);

        $div1 = EventDivision::create([
            'event_id' => $event->id,
            'nama_divisi' => 'Acara',
        ]);
        $div2 = EventDivision::create([
            'event_id' => $event->id,
            'nama_divisi' => 'Humas',
        ]);

        $member1 = Member::create([
            'nama_lengkap' => 'Member One '.uniqid(),
            'email' => 'm1_'.uniqid().'@test.com',
            'qr_code' => Str::random(32),
            'departemen' => 'KESTARI',
            'jabatan' => 'Anggota',
        ]);
        $member2 = Member::create([
            'nama_lengkap' => 'Member Two '.uniqid(),
            'email' => 'm2_'.uniqid().'@test.com',
            'qr_code' => Str::random(32),
            'departemen' => 'MEDINFO',
            'jabatan' => 'Anggota',
        ]);

        EventMember::create([
            'event_id' => $event->id,
            'division_id' => $div1->id,
            'user_id' => $member1->id,
            'jabatan_panitia' => 'Anggota',
        ]);
        EventMember::create([
            'event_id' => $event->id,
            'division_id' => $div2->id,
            'user_id' => $member2->id,
            'jabatan_panitia' => 'Anggota',
        ]);

        $response = $this->actingAs($creator, 'member')->post('/rapat', [
            'created_by' => $creator->id,
            'jenis_rapat' => 'kepanitiaan',
            'event_id' => $event->id,
            'tipe_divisi' => 'semua',
            'fokus_bahasan_kepanitiaan' => 'Pembahasan teknis acara dan humas',
            'mode_rapat' => 'offline',
            'tanggal' => date('Y-m-d'),
            'waktu_mulai' => '09:00',
        ]);

        $meeting = Meeting::where('event_id', $event->id)->latest('id')->first();
        $this->assertNotNull($meeting);
        $this->assertEquals($event->nama_event, $meeting->nama_event);
        $this->assertEquals('semua', $meeting->tipe_divisi);
        $this->assertNull($meeting->divisi_rapat);

        // Assert attendees only contain the event members
        $attendeeUserIds = $meeting->attendances()->pluck('user_id')->toArray();
        $this->assertContains($member1->id, $attendeeUserIds);
        $this->assertContains($member2->id, $attendeeUserIds);
        $this->assertCount(2, $attendeeUserIds);

        $response->assertRedirect(route('public.hasil', $meeting->unique_link));
    }

    public function test_create_kepanitiaan_meeting_per_division(): void
    {
        $creator = Member::first() ?? Member::create([
            'nama_lengkap' => 'Creator Test 2',
            'nim' => '12345679',
            'email' => 'creator2@test.com',
            'departemen' => 'BPH',
            'jabatan' => 'Ketua',
        ]);

        $event = Event::create([
            'nama_event' => 'Event Rapat Divisi '.uniqid(),
            'status' => 'active',
        ]);

        $divAcara = EventDivision::create([
            'event_id' => $event->id,
            'nama_divisi' => 'Acara',
        ]);
        $divHumas = EventDivision::create([
            'event_id' => $event->id,
            'nama_divisi' => 'Humas',
        ]);

        $memberAcara = Member::create([
            'nama_lengkap' => 'Acara Panitia '.uniqid(),
            'email' => 'acara_'.uniqid().'@test.com',
            'qr_code' => Str::random(32),
            'departemen' => 'KESTARI',
            'jabatan' => 'Anggota',
        ]);
        $memberHumas = Member::create([
            'nama_lengkap' => 'Humas Panitia '.uniqid(),
            'email' => 'humas_'.uniqid().'@test.com',
            'qr_code' => Str::random(32),
            'departemen' => 'MEDINFO',
            'jabatan' => 'Anggota',
        ]);

        EventMember::create([
            'event_id' => $event->id,
            'division_id' => $divAcara->id,
            'user_id' => $memberAcara->id,
            'jabatan_panitia' => 'Anggota',
        ]);
        EventMember::create([
            'event_id' => $event->id,
            'division_id' => $divHumas->id,
            'user_id' => $memberHumas->id,
            'jabatan_panitia' => 'Anggota',
        ]);

        $response = $this->actingAs($creator, 'member')->post('/rapat', [
            'created_by' => $creator->id,
            'jenis_rapat' => 'kepanitiaan',
            'event_id' => $event->id,
            'tipe_divisi' => 'perdivisi',
            'divisi_id' => $divAcara->id,
            'fokus_bahasan_kepanitiaan' => 'Rundown acara kegiatan',
            'mode_rapat' => 'offline',
            'tanggal' => date('Y-m-d'),
            'waktu_mulai' => '14:00',
        ]);

        $meeting = Meeting::where('event_id', $event->id)->latest('id')->first();
        $this->assertNotNull($meeting);
        $this->assertEquals($event->nama_event, $meeting->nama_event);
        $this->assertEquals('perdivisi', $meeting->tipe_divisi);
        $this->assertEquals('Acara', $meeting->divisi_rapat);

        // Assert attendees only contain members from divAcara
        $attendeeUserIds = $meeting->attendances()->pluck('user_id')->toArray();
        $this->assertContains($memberAcara->id, $attendeeUserIds);
        $this->assertNotContains($memberHumas->id, $attendeeUserIds);
        $this->assertCount(1, $attendeeUserIds);

        $response->assertRedirect(route('public.hasil', $meeting->unique_link));
    }

    public function test_save_photo_legacy_stores_photo_and_returns_payload(): void
    {
        $creator = Member::first() ?? Member::create([
            'nama_lengkap' => 'Tester Doc Photo',
            'nim' => '12345679',
            'email' => 'docphoto@test.com',
            'departemen' => 'BPH',
            'jabatan' => 'Sekretaris',
        ]);

        $meeting = Meeting::create([
            'created_by' => $creator->id,
            'jenis_rapat' => 'bph',
            'fokus_bahasan' => 'Evaluasi Dokumentasi',
            'mode_rapat' => 'offline',
            'tanggal' => date('Y-m-d'),
            'waktu_mulai' => '10:00:00',
            'unique_link' => Str::lower(Str::random(12)),
            'absent_link' => Str::lower(Str::random(12)),
            'status' => 'scanning',
        ]);

        $file = UploadedFile::fake()->image('dokumentasi_kegiatan.jpg', 600, 400);

        $response = $this->post('/meeting/process/save_photo.php', [
            'meeting_id' => $meeting->id,
            'photo' => $file,
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Foto dokumentasi berhasil disimpan!',
        ]);

        $json = $response->json();
        $this->assertNotEmpty($json['filename']);
        $this->assertNotEmpty($json['url']);
        $this->assertEquals(1, $json['total_photos']);
        $this->assertCount(1, $json['photos']);

        $meeting->refresh();
        $stored = json_decode($meeting->foto_dokumentasi, true);
        $this->assertIsArray($stored);
        $this->assertContains($json['filename'], $stored);

        // Cleanup created file from public/uploads/dokumentasi
        $filePath = public_path('uploads/dokumentasi/'.$json['filename']);
        if (File::exists($filePath)) {
            File::delete($filePath);
        }
    }

    public function test_public_scanner_view_renders_doc_photo_section_and_modal(): void
    {
        $creator = Member::first() ?? Member::create([
            'nama_lengkap' => 'Scanner View Tester',
            'nim' => '12345688',
            'email' => 'scannerview@test.com',
            'departemen' => 'BPH',
            'jabatan' => 'Anggota',
        ]);

        $meeting = Meeting::create([
            'created_by' => $creator->id,
            'jenis_rapat' => 'bph',
            'fokus_bahasan' => 'Test UI Scanner',
            'mode_rapat' => 'offline',
            'tanggal' => date('Y-m-d'),
            'waktu_mulai' => '10:00:00',
            'unique_link' => Str::lower(Str::random(12)),
            'absent_link' => Str::lower(Str::random(12)),
            'status' => 'scanning',
        ]);

        $response = $this->get(route('public.scanner', $meeting->unique_link));

        $response->assertStatus(200);
        $response->assertSee('id="btnPhoto"', false);
        $response->assertSee('id="docPhotoSection"', false);
        $response->assertSee('id="docPhotoThumbnails"', false);
        $response->assertSee('id="docPhotoModal"', false);
        $response->assertSee('DOC_PHOTOS =', false);
    }
}

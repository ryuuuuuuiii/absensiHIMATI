<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Attendance;
use App\Models\Meeting;
use App\Models\Member;
use App\Models\Setting;
use App\Services\GoogleSyncService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

class ReportTest extends TestCase
{
    use DatabaseTransactions;

    private function createAdmin(): Admin
    {
        return Admin::create([
            'nama' => 'Admin '.Str::random(6),
            'email' => 'admin.'.Str::lower(Str::random(8)).'@himati.test',
            'password' => bcrypt('password123'),
        ]);
    }

    private function createMember(array $attributes = []): Member
    {
        return Member::create(array_merge([
            'nama_lengkap' => 'Pengurus '.Str::random(6),
            'departemen' => 'PSDM',
            'jabatan' => 'Anggota Divisi',
            'email' => 'member.'.Str::lower(Str::random(8)).'@himati.test',
            'password' => 'password123',
            'qr_code' => 'HIMATI-TEST-'.Str::upper(Str::random(8)),
            'is_bph' => false,
        ], $attributes));
    }

    public function test_admin_report_displays_alpha_wajib_and_alpha_dibebaskan_breakdown(): void
    {
        $admin = $this->createAdmin();
        $member = $this->createMember(['nama_lengkap' => 'Zahra Pengurus Uji']);

        $date = now()->startOfMonth();

        // 1. Mandatory meeting with alpha (SP berlaku)
        $meetingWajib = Meeting::create([
            'created_by' => $member->id,
            'jenis_rapat' => 'internal_dept',
            'kategori' => 'rapat',
            'sifat_kegiatan' => 'wajib',
            'mode_rapat' => 'offline',
            'departemen' => 'PSDM',
            'fokus_bahasan' => 'Rapat Proker Bulanan',
            'tanggal' => $date->toDateString(),
            'waktu_mulai' => '10:00:00',
            'status' => 'completed',
            'unique_link' => Str::lower(Str::random(12)),
            'absent_link' => Str::lower(Str::random(12)),
        ]);

        Attendance::create([
            'meeting_id' => $meetingWajib->id,
            'user_id' => $member->id,
            'status' => 'alpha',
            'sp_ignored' => false,
        ]);

        // 2. Voluntary kegiatan with alpha (Bebas SP)
        $meetingBebas = Meeting::create([
            'created_by' => $member->id,
            'jenis_rapat' => 'kegiatan',
            'kategori' => 'kegiatan',
            'sifat_kegiatan' => 'dibebaskan',
            'mode_rapat' => 'offline',
            'nama_event' => 'Buber Akbar HIMA TI',
            'fokus_bahasan' => 'Buber Akbar HIMA TI',
            'tanggal' => $date->toDateString(),
            'waktu_mulai' => '17:00:00',
            'status' => 'completed',
            'unique_link' => Str::lower(Str::random(12)),
            'absent_link' => Str::lower(Str::random(12)),
        ]);

        Attendance::create([
            'meeting_id' => $meetingBebas->id,
            'user_id' => $member->id,
            'status' => 'alpha',
            'sp_ignored' => true,
        ]);

        $response = $this->actingAs($admin)
            ->get(route('reports.index', [
                'month' => $date->month,
                'year' => $date->year,
                'department' => 'PSDM',
            ]))
            ->assertOk();

        // Check that Zahra is displayed with both breakdown tags
        $response->assertSee('Zahra Pengurus Uji');
        $response->assertSee('1 Wajib');
        $response->assertSee('1 Bebas SP');
    }

    public function test_admin_meeting_views_display_sifat_kegiatan_and_sp_status(): void
    {
        $admin = $this->createAdmin();
        $member = $this->createMember(['nama_lengkap' => 'Budi Pengurus']);

        $meetingBebas = Meeting::create([
            'created_by' => $member->id,
            'jenis_rapat' => 'kegiatan',
            'kategori' => 'kegiatan',
            'sifat_kegiatan' => 'dibebaskan',
            'mode_rapat' => 'offline',
            'nama_event' => 'Gathering Santai TI',
            'fokus_bahasan' => 'Gathering Santai TI',
            'tanggal' => now()->toDateString(),
            'waktu_mulai' => '15:00:00',
            'status' => 'completed',
            'unique_link' => Str::lower(Str::random(12)),
            'absent_link' => Str::lower(Str::random(12)),
        ]);

        Attendance::create([
            'meeting_id' => $meetingBebas->id,
            'user_id' => $member->id,
            'status' => 'alpha',
            'sp_ignored' => true,
        ]);

        // Meetings index view
        $indexResponse = $this->actingAs($admin)
            ->get(route('meetings.index'))
            ->assertOk();
        $indexResponse->assertSee('Bebas SP');

        // Meetings show view
        $showResponse = $this->actingAs($admin)
            ->get(route('meetings.show', $meetingBebas->id))
            ->assertOk();
        $showResponse->assertSee('Sifat: Dibebaskan (Bebas SP)');
        $showResponse->assertSee('Bebas SP');
    }

    public function test_public_hasil_shows_wajib_and_dibebaskan_information(): void
    {
        $member = $this->createMember(['nama_lengkap' => 'Doni Anggota']);

        $meeting = Meeting::create([
            'created_by' => $member->id,
            'jenis_rapat' => 'kegiatan',
            'kategori' => 'kegiatan',
            'sifat_kegiatan' => 'dibebaskan',
            'mode_rapat' => 'online',
            'nama_event' => 'Webinar Nasional TI',
            'fokus_bahasan' => 'Webinar Nasional TI',
            'tanggal' => now()->toDateString(),
            'waktu_mulai' => '09:00:00',
            'status' => 'scanning',
            'unique_link' => Str::lower(Str::random(12)),
            'absent_link' => Str::lower(Str::random(12)),
        ]);

        Attendance::create([
            'meeting_id' => $meeting->id,
            'user_id' => $member->id,
            'status' => 'alpha',
            'sp_ignored' => true,
        ]);

        // HTML Response
        $htmlRes = $this->get(route('public.hasil', $meeting->unique_link))
            ->assertOk();
        $htmlRes->assertSee('Dibebaskan (Bebas SP)');
        $htmlRes->assertSee('Bebas SP');

        // JSON Response
        $jsonRes = $this->getJson(route('public.hasil', ['unique_link' => $meeting->unique_link, 'json' => 1]))
            ->assertOk();
        $jsonRes->assertJsonPath('meeting.is_dibebaskan', true);
        $jsonRes->assertJsonPath('meeting.sifat_kegiatan', 'dibebaskan');
        $jsonRes->assertJsonPath('stats.alpha_dibebaskan', 1);
        $jsonRes->assertJsonPath('attendances.0.sp_ignored', true);
    }

    public function test_google_sync_payload_includes_sifat_and_sanksi_sp(): void
    {
        Http::fake([
            '*' => Http::response(['success' => true], 200),
        ]);

        Setting::set('google_script_url', 'https://script.google.com/macros/s/fake/exec');

        $member = $this->createMember();
        $meeting = Meeting::create([
            'created_by' => $member->id,
            'jenis_rapat' => 'kegiatan',
            'kategori' => 'kegiatan',
            'sifat_kegiatan' => 'dibebaskan',
            'mode_rapat' => 'offline',
            'nama_event' => 'Sync Test Kegiatan',
            'tanggal' => now()->toDateString(),
            'waktu_mulai' => '10:00:00',
            'status' => 'completed',
            'unique_link' => Str::lower(Str::random(12)),
            'absent_link' => Str::lower(Str::random(12)),
        ]);

        Attendance::create([
            'meeting_id' => $meeting->id,
            'user_id' => $member->id,
            'status' => 'alpha',
            'sp_ignored' => true,
        ]);

        $syncService = app(GoogleSyncService::class);
        $result = $syncService->syncMeeting($meeting);

        $this->assertTrue($result['success'] ?? false);

        Http::assertSent(function ($request) {
            $data = $request['data'] ?? [];

            return isset($data[0]['sifat'])
                && $data[0]['sifat'] === 'Dibebaskan (Bebas SP)'
                && isset($data[0]['sanksi_sp'])
                && $data[0]['sanksi_sp'] === 'Bebas SP';
        });
    }
}

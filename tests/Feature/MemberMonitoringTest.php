<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Meeting;
use App\Models\Member;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Tests\TestCase;

class MemberMonitoringTest extends TestCase
{
    use DatabaseTransactions;

    public function test_pengurus_inti_can_access_monitoring_index_and_sees_all_members(): void
    {
        $inti = $this->createMember([
            'nama_lengkap' => 'Muhammad Faisal',
            'departemen' => 'Inti',
            'jabatan' => 'Wakil Ketua Umum',
            'is_bph' => true,
        ]);

        $medinfoMember = $this->createMember([
            'nama_lengkap' => 'Anggota Medinfo',
            'departemen' => 'Medinfo',
            'jabatan' => 'Staff',
            'is_bph' => false,
        ]);

        $psdmMember = $this->createMember([
            'nama_lengkap' => 'Anggota PSDM',
            'departemen' => 'PSDM',
            'jabatan' => 'Staff',
            'is_bph' => false,
        ]);

        $response = $this->actingAs($inti, 'member')->get(route('portal.members.index'));

        $response->assertOk();
        $response->assertSee('Data Seluruh Pengurus');
        $response->assertSee('Mode Pantau (Read-Only)');
        $response->assertSee('Anggota Medinfo');
        $response->assertSee('Anggota PSDM');
    }

    public function test_kepala_departemen_can_access_monitoring_index_and_only_sees_own_department(): void
    {
        $kadepMedinfo = $this->createMember([
            'nama_lengkap' => 'Muhammad Wahyu Saputra',
            'departemen' => 'Medinfo',
            'jabatan' => 'Koordinator',
            'is_bph' => true,
        ]);

        $medinfoMember = $this->createMember([
            'nama_lengkap' => 'Staff Khusus Medinfo',
            'departemen' => 'Medinfo',
            'jabatan' => 'Staff',
            'is_bph' => false,
        ]);

        $psdmMember = $this->createMember([
            'nama_lengkap' => 'Staff Khusus PSDM',
            'departemen' => 'PSDM',
            'jabatan' => 'Staff',
            'is_bph' => false,
        ]);

        $response = $this->actingAs($kadepMedinfo, 'member')->get(route('portal.members.index'));

        $response->assertOk();
        $response->assertSee('Anggota Departemen Medinfo');
        $response->assertSee('Staff Khusus Medinfo');
        $response->assertDontSee('Staff Khusus PSDM');
    }

    public function test_regular_staff_cannot_access_monitoring_index(): void
    {
        $staff = $this->createMember([
            'nama_lengkap' => 'Staff Biasa',
            'departemen' => 'Medinfo',
            'jabatan' => 'Staff',
            'is_bph' => false,
        ]);

        $response = $this->actingAs($staff, 'member')->get(route('portal.members.index'));

        $response->assertForbidden();
    }

    public function test_pengurus_inti_can_view_any_member_track_record(): void
    {
        $inti = $this->createMember([
            'nama_lengkap' => 'Iqbal Rupawan',
            'departemen' => 'Inti',
            'jabatan' => 'Ketua Umum',
            'is_bph' => true,
        ]);

        $targetMember = $this->createMember([
            'nama_lengkap' => 'Nadil Ulum',
            'departemen' => 'Medinfo',
            'jabatan' => 'Staff',
            'is_bph' => false,
            'exp' => 150,
        ]);

        $meeting = $this->createMeeting('Rapat Evaluasi Program Kerja', 'completed');
        Attendance::create([
            'meeting_id' => $meeting->id,
            'user_id' => $targetMember->id,
            'status' => 'hadir',
            'scan_method' => 'qr_scan',
            'scanned_at' => now(),
        ]);

        $response = $this->actingAs($inti, 'member')->get(route('portal.members.show', $targetMember->id));

        $response->assertOk();
        $response->assertSee('Nadil Ulum');
        $response->assertSee('Mode Pantau Track Record (Read-Only)');
        $response->assertSee('Rapat Evaluasi Program Kerja');
        $response->assertSee('Hadir');
        $response->assertSee('150 EXP');
    }

    public function test_kepala_departemen_can_view_own_department_member_track_record(): void
    {
        $kadep = $this->createMember([
            'nama_lengkap' => 'Koordinator PSDM',
            'departemen' => 'PSDM',
            'jabatan' => 'Koordinator',
            'is_bph' => true,
        ]);

        $deptMember = $this->createMember([
            'nama_lengkap' => 'Anggota PSDM Satu',
            'departemen' => 'PSDM',
            'jabatan' => 'Staff',
            'is_bph' => false,
        ]);

        $response = $this->actingAs($kadep, 'member')->get(route('portal.members.show', $deptMember->id));

        $response->assertOk();
        $response->assertSee('Anggota PSDM Satu');
        $response->assertSee('Mode Pantau Track Record (Read-Only)');
    }

    public function test_kepala_departemen_forbidden_from_viewing_other_department_member(): void
    {
        $kadepMedinfo = $this->createMember([
            'nama_lengkap' => 'Koor Medinfo',
            'departemen' => 'Medinfo',
            'jabatan' => 'Koordinator',
            'is_bph' => true,
        ]);

        $psdmMember = $this->createMember([
            'nama_lengkap' => 'Anggota PSDM Rahasia',
            'departemen' => 'PSDM',
            'jabatan' => 'Staff',
            'is_bph' => false,
        ]);

        $response = $this->actingAs($kadepMedinfo, 'member')->get(route('portal.members.show', $psdmMember->id));

        $response->assertForbidden();
    }

    public function test_regular_staff_forbidden_from_viewing_member_track_record(): void
    {
        $staff = $this->createMember([
            'nama_lengkap' => 'Staff Biasa',
            'departemen' => 'Medinfo',
            'jabatan' => 'Staff',
            'is_bph' => false,
        ]);

        $targetMember = $this->createMember([
            'nama_lengkap' => 'Target Member',
            'departemen' => 'Medinfo',
            'jabatan' => 'Staff',
            'is_bph' => false,
        ]);

        $response = $this->actingAs($staff, 'member')->get(route('portal.members.show', $targetMember->id));

        $response->assertForbidden();
    }

    public function test_monitoring_routes_have_no_modification_endpoints(): void
    {
        $member = $this->createMember();

        $postResponse = $this->actingAs($member, 'member')->post('/portal/anggota', []);
        $this->assertEquals(405, $postResponse->getStatusCode());

        $putResponse = $this->actingAs($member, 'member')->put('/portal/anggota/'.$member->id, []);
        $this->assertEquals(405, $putResponse->getStatusCode());

        $deleteResponse = $this->actingAs($member, 'member')->delete('/portal/anggota/'.$member->id);
        $this->assertEquals(405, $deleteResponse->getStatusCode());
    }

    private function createMember(array $attributes = []): Member
    {
        return Member::create(array_merge([
            'nama_lengkap' => 'Pengurus Test '.Str::random(6),
            'departemen' => 'PSDM',
            'jabatan' => 'Staff',
            'email' => 'member.'.Str::lower(Str::random(8)).'@himati.test',
            'password' => 'password123',
            'qr_code' => 'HIMATI-TEST-'.Str::upper(Str::random(8)),
            'is_bph' => false,
        ], $attributes));
    }

    private function createMeeting(string $agenda, string $status = 'completed'): Meeting
    {
        $creator = Member::first() ?? $this->createMember();

        return Meeting::create([
            'created_by' => $creator->id,
            'jenis_rapat' => 'internal_dept',
            'mode_rapat' => 'offline',
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

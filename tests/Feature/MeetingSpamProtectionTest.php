<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Meeting;
use App\Models\Member;
use App\Services\SpService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Tests\TestCase;

class MeetingSpamProtectionTest extends TestCase
{
    use DatabaseTransactions;

    private function createMember(): Member
    {
        return Member::create([
            'nama_lengkap' => 'Pengurus AntiSpam '.Str::random(4),
            'jabatan' => 'Anggota',
            'departemen' => 'KOMINFO',
            'email' => 'spam_test_'.Str::lower(Str::random(6)).'@himati.id',
            'password' => Hash::make('password123'),
            'qr_code' => 'HIMATI-SPAM-'.Str::upper(Str::random(8)),
            'sp_level' => 0,
            'sp_base_level' => 0,
            'is_active' => true,
        ]);
    }

    public function test_cooldown_prevents_rapid_meeting_creation(): void
    {
        $member = $this->createMember();
        $this->actingAs($member, 'member');

        RateLimiter::clear('create-meeting:member:'.$member->id);

        // 1st meeting creation -> success
        $res1 = $this->post(route('public.meeting.store'), [
            'jenis_rapat' => 'internal_dept',
            'mode_rapat' => 'offline',
            'departemen' => 'KOMINFO',
            'fokus_bahasan_internal' => 'Pembahasan Proker A',
            'tanggal' => now()->toDateString(),
            'waktu_mulai' => '10:00',
        ]);
        $res1->assertRedirect();

        // 2nd meeting creation within seconds -> blocked by cooldown
        $res2 = $this->from(route('public.meeting.create'))->post(route('public.meeting.store'), [
            'jenis_rapat' => 'internal_dept',
            'mode_rapat' => 'offline',
            'departemen' => 'KOMINFO',
            'fokus_bahasan_internal' => 'Pembahasan Proker B',
            'tanggal' => now()->toDateString(),
            'waktu_mulai' => '11:00',
        ]);

        $res2->assertRedirect(route('public.meeting.create'));
        $res2->assertSessionHas('danger');
        $this->assertStringContainsString('Cooldown Anti-Spam', session('danger'));
    }

    public function test_duplicate_agenda_detection(): void
    {
        $member = $this->createMember();
        $this->actingAs($member, 'member');

        // Create an initial meeting
        $initialMeeting = Meeting::create([
            'created_by' => $member->id,
            'jenis_rapat' => 'internal_dept',
            'mode_rapat' => 'offline',
            'departemen' => 'KOMINFO',
            'fokus_bahasan' => 'Evaluasi Bulanan Kominfo',
            'tanggal' => now()->toDateString(),
            'waktu_mulai' => '09:00:00',
            'waktu_selesai' => '10:00:00',
            'status' => 'completed',
            'unique_link' => Str::random(16),
            'absent_link' => 'abs_'.Str::random(12),
        ]);
        DB::table('meetings')->where('id', $initialMeeting->id)->update([
            'created_at' => now()->subMinutes(10),
        ]);

        RateLimiter::clear('create-meeting:member:'.$member->id);

        // Attempt to create duplicate agenda on same day & department
        $response = $this->from(route('public.meeting.create'))->post(route('public.meeting.store'), [
            'jenis_rapat' => 'internal_dept',
            'mode_rapat' => 'offline',
            'departemen' => 'KOMINFO',
            'fokus_bahasan_internal' => 'Evaluasi Bulanan Kominfo',
            'tanggal' => now()->toDateString(),
            'waktu_mulai' => '09:30',
        ]);

        $response->assertRedirect(route('public.meeting.create'));
        $response->assertSessionHas('danger');
        $this->assertStringContainsString('duplikat', session('danger'));
    }

    public function test_limit_active_scanning_meetings_per_creator(): void
    {
        $member = $this->createMember();
        $this->actingAs($member, 'member');

        RateLimiter::clear('create-meeting:member:'.$member->id);

        // Create 2 open scanning meetings today
        $m1 = Meeting::create([
            'created_by' => $member->id,
            'jenis_rapat' => 'internal_dept',
            'mode_rapat' => 'offline',
            'departemen' => 'KOMINFO',
            'fokus_bahasan' => 'Rapat Aktif 1',
            'tanggal' => now()->toDateString(),
            'waktu_mulai' => '08:00:00',
            'status' => 'scanning',
            'unique_link' => Str::random(16),
            'absent_link' => 'abs_'.Str::random(12),
        ]);

        $m2 = Meeting::create([
            'created_by' => $member->id,
            'jenis_rapat' => 'internal_dept',
            'mode_rapat' => 'offline',
            'departemen' => 'KOMINFO',
            'fokus_bahasan' => 'Rapat Aktif 2',
            'tanggal' => now()->toDateString(),
            'waktu_mulai' => '09:00:00',
            'status' => 'scanning',
            'unique_link' => Str::random(16),
            'absent_link' => 'abs_'.Str::random(12),
        ]);

        DB::table('meetings')->whereIn('id', [$m1->id, $m2->id])->update([
            'created_at' => now()->subMinutes(10),
        ]);

        // Attempt to create a 3rd meeting while 2 are still scanning
        $response = $this->from(route('public.meeting.create'))->post(route('public.meeting.store'), [
            'jenis_rapat' => 'internal_dept',
            'mode_rapat' => 'offline',
            'departemen' => 'KOMINFO',
            'fokus_bahasan_internal' => 'Rapat Aktif 3',
            'tanggal' => now()->toDateString(),
            'waktu_mulai' => '10:00',
        ]);

        $response->assertRedirect(route('public.meeting.create'));
        $response->assertSessionHas('danger');
        $this->assertStringContainsString('masih memiliki', session('danger'));
    }

    public function test_cancelling_meeting_does_not_affect_sp(): void
    {
        $creator = $this->createMember();
        $victim = $this->createMember();

        $meeting = Meeting::create([
            'created_by' => $creator->id,
            'jenis_rapat' => 'perdana',
            'mode_rapat' => 'offline',
            'fokus_bahasan' => 'Rapat Dibatalkan',
            'tanggal' => now()->toDateString(),
            'waktu_mulai' => '14:00:00',
            'status' => 'scanning',
            'unique_link' => Str::random(16),
            'absent_link' => 'abs_'.Str::random(12),
        ]);

        Attendance::create([
            'meeting_id' => $meeting->id,
            'user_id' => $victim->id,
            'status' => 'alpha',
        ]);

        // Cancel the meeting via POST
        $this->post(route('public.meeting.cancel', $meeting->unique_link))->assertRedirect();

        $meeting->refresh();
        $this->assertSame('cancelled', $meeting->status);

        // Victim should NOT have SP or alpha streak counted from this meeting
        $spService = app(SpService::class);
        $result = $spService->calculateStreakAndLevel($victim);
        $this->assertSame(0, $result['sp_level']);
        $this->assertSame(0, $result['consecutive_alphas']);
    }

    public function test_instant_spam_completed_meeting_does_not_affect_sp(): void
    {
        $creator = $this->createMember();
        $victim = $this->createMember();

        // Simulate 3 instant-closed spam meetings (< 2 minutes, 0 attendees except creator)
        for ($i = 1; $i <= 3; $i++) {
            $spamMeeting = Meeting::create([
                'created_by' => $creator->id,
                'jenis_rapat' => 'perdana',
                'mode_rapat' => 'offline',
                'fokus_bahasan' => 'Spam Fast Meeting '.$i,
                'tanggal' => now()->toDateString(),
                'waktu_mulai' => now()->format('H:i:s'),
                'waktu_selesai' => now()->format('H:i:s'), // Instant finish
                'status' => 'completed',
                'unique_link' => Str::random(16),
                'absent_link' => 'abs_'.Str::random(12),
                'created_at' => now(), // Just created right now
            ]);

            Attendance::create([
                'meeting_id' => $spamMeeting->id,
                'user_id' => $victim->id,
                'status' => 'alpha',
            ]);
        }

        // Even though 3 meetings with status=completed exist where victim is alpha,
        // they are recognized as instant spam meetings (< 2 min, 0 attendees),
        // so victim must NOT receive SP 1!
        $spService = app(SpService::class);
        $victim->refresh();
        $result = $spService->calculateStreakAndLevel($victim);

        $this->assertSame(0, $result['sp_level']);
        $this->assertSame(0, $result['consecutive_alphas']);
        $this->assertSame(0, $victim->sp_level);
    }
}

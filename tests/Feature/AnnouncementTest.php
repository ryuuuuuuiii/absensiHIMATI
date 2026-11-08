<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Announcement;
use App\Models\Member;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Tests\TestCase;

class AnnouncementTest extends TestCase
{
    use DatabaseTransactions;

    public function test_admin_can_view_announcements_index(): void
    {
        $admin = $this->createAdmin();

        $this->actingAs($admin)
            ->get(route('announcements.index'))
            ->assertOk()
            ->assertSee('Pengumuman & Notifikasi Pesan', false)
            ->assertSee('Buat Pengumuman & Notifikasi Pesan Baru', false);
    }

    public function test_admin_can_create_broadcast_announcement(): void
    {
        $admin = $this->createAdmin();

        $response = $this->actingAs($admin)->post(route('announcements.store'), [
            'judul' => 'Pengumuman Rapat Pleno',
            'konten' => 'Diharapkan seluruh pengurus hadir tepat waktu.',
            'tipe' => 'penting',
            'target_type' => 'all',
            'is_banner' => '1',
            'pinned' => '1',
        ]);

        $response->assertRedirect(route('announcements.index'));
        $this->assertDatabaseHas('announcements', [
            'judul' => 'Pengumuman Rapat Pleno',
            'tipe' => 'penting',
            'target_type' => 'all',
            'is_banner' => true,
            'pinned' => true,
        ]);
    }

    public function test_admin_can_create_department_announcement(): void
    {
        $admin = $this->createAdmin();

        $response = $this->actingAs($admin)->post(route('announcements.store'), [
            'judul' => 'Briefing Khusus PSDM',
            'konten' => 'Agenda evaluasi mingguan departemen PSDM.',
            'tipe' => 'info',
            'target_type' => 'departemen',
            'target_departemen' => 'PSDM',
            'is_banner' => '1',
        ]);

        $response->assertRedirect(route('announcements.index'));
        $this->assertDatabaseHas('announcements', [
            'judul' => 'Briefing Khusus PSDM',
            'target_type' => 'departemen',
            'target_departemen' => 'PSDM',
        ]);
    }

    public function test_admin_can_create_personal_message_to_member(): void
    {
        $admin = $this->createAdmin();
        $member = $this->createMember(['nama_lengkap' => 'Budi Santoso']);

        $response = $this->actingAs($admin)->post(route('announcements.store'), [
            'judul' => 'Panggilan Koordinasi Ketua',
            'konten' => 'Tolong hubungi ketua umum hari ini.',
            'tipe' => 'mendesak',
            'target_type' => 'personal',
            'target_user_id' => $member->id,
            'is_banner' => '1',
        ]);

        $response->assertRedirect(route('announcements.index'));
        $this->assertDatabaseHas('announcements', [
            'judul' => 'Panggilan Koordinasi Ketua',
            'target_type' => 'personal',
            'target_user_id' => $member->id,
            'tipe' => 'mendesak',
        ]);
    }

    public function test_admin_can_toggle_pin_and_status(): void
    {
        $admin = $this->createAdmin();
        $announcement = Announcement::create([
            'admin_id' => $admin->id,
            'judul' => 'Test Toggle',
            'konten' => 'Isi toggle test',
            'tipe' => 'info',
            'target_type' => 'all',
            'is_active' => true,
            'pinned' => false,
        ]);

        // Toggle Pin
        $this->actingAs($admin)
            ->post(route('announcements.toggle_pin', $announcement))
            ->assertRedirect();
        $this->assertTrue($announcement->fresh()->pinned);

        // Toggle Status
        $this->actingAs($admin)
            ->post(route('announcements.toggle_status', $announcement))
            ->assertRedirect();
        $this->assertFalse($announcement->fresh()->is_active);
    }

    public function test_admin_can_update_and_delete_announcement(): void
    {
        $admin = $this->createAdmin();
        $announcement = Announcement::create([
            'admin_id' => $admin->id,
            'judul' => 'Judul Lama',
            'konten' => 'Isi Lama',
            'tipe' => 'info',
            'target_type' => 'all',
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->put(route('announcements.update', $announcement), [
                'judul' => 'Judul Baru',
                'konten' => 'Isi Baru Diperbarui',
                'tipe' => 'mendesak',
                'target_type' => 'all',
                'is_banner' => '1',
            ])
            ->assertRedirect(route('announcements.index'));

        $this->assertEquals('Judul Baru', $announcement->fresh()->judul);
        $this->assertEquals('mendesak', $announcement->fresh()->tipe);

        $this->actingAs($admin)
            ->delete(route('announcements.destroy', $announcement))
            ->assertRedirect(route('announcements.index'));

        $this->assertDatabaseMissing('announcements', ['id' => $announcement->id]);
    }

    public function test_member_sees_targeted_announcement_banner_on_dashboard(): void
    {
        $admin = $this->createAdmin();
        $memberPsdm = $this->createMember(['departemen' => 'PSDM']);
        $memberMedinfo = $this->createMember(['departemen' => 'Medinfo']);

        // Announcement for PSDM only
        Announcement::create([
            'admin_id' => $admin->id,
            'judul' => 'Pengumuman PSDM Saja',
            'konten' => 'Isi pesan khusus PSDM',
            'tipe' => 'penting',
            'target_type' => 'departemen',
            'target_departemen' => 'PSDM',
            'is_banner' => true,
            'is_active' => true,
        ]);

        // Member PSDM should see it
        $this->actingAs($memberPsdm, 'member')
            ->get(route('portal.dashboard'))
            ->assertOk()
            ->assertSee('Pengumuman PSDM Saja')
            ->assertSee('Isi pesan khusus PSDM');

        // Member Medinfo should NOT see it
        $this->actingAs($memberMedinfo, 'member')
            ->get(route('portal.dashboard'))
            ->assertOk()
            ->assertDontSee('Pengumuman PSDM Saja');
    }

    public function test_member_sees_personal_message_banner(): void
    {
        $admin = $this->createAdmin();
        $targetMember = $this->createMember(['nama_lengkap' => 'Target User']);
        $otherMember = $this->createMember(['nama_lengkap' => 'Other User']);

        Announcement::create([
            'admin_id' => $admin->id,
            'judul' => 'Surat Peringatan 1',
            'konten' => 'Mohon temui koordinator anda segera.',
            'tipe' => 'mendesak',
            'target_type' => 'personal',
            'target_user_id' => $targetMember->id,
            'is_banner' => true,
            'is_active' => true,
        ]);

        $this->actingAs($targetMember, 'member')
            ->get(route('portal.dashboard'))
            ->assertOk()
            ->assertSee('Surat Peringatan 1')
            ->assertSee('Pesan Pribadi');

        $this->actingAs($otherMember, 'member')
            ->get(route('portal.dashboard'))
            ->assertOk()
            ->assertDontSee('Surat Peringatan 1');
    }

    public function test_member_can_dismiss_banner_on_dashboard(): void
    {
        $admin = $this->createAdmin();
        $member = $this->createMember();

        $announcement = Announcement::create([
            'admin_id' => $admin->id,
            'judul' => 'Banner Dismiss Test',
            'konten' => 'Banner ini akan ditutup',
            'tipe' => 'info',
            'target_type' => 'all',
            'is_banner' => true,
            'is_active' => true,
        ]);

        // Before dismiss, dashboard banner card should show
        $this->actingAs($member, 'member')
            ->get(route('portal.dashboard'))
            ->assertOk()
            ->assertSee('banner-card-'.$announcement->id);

        // Dismiss via AJAX
        $response = $this->actingAs($member, 'member')
            ->postJson(route('portal.announcements.dismiss', $announcement));

        $response->assertOk()
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('announcement_reads', [
            'announcement_id' => $announcement->id,
            'user_id' => $member->id,
            'is_dismissed' => true,
        ]);

        // After dismiss, dashboard banner card should no longer show
        $this->actingAs($member, 'member')
            ->get(route('portal.dashboard'))
            ->assertOk()
            ->assertDontSee('banner-card-'.$announcement->id);
    }

    public function test_member_can_mark_notification_as_read_and_read_all(): void
    {
        $admin = $this->createAdmin();
        $member = $this->createMember();

        $a1 = Announcement::create([
            'admin_id' => $admin->id,
            'judul' => 'Notif 1',
            'konten' => 'Konten 1',
            'tipe' => 'info',
            'target_type' => 'all',
            'is_active' => true,
        ]);

        $a2 = Announcement::create([
            'admin_id' => $admin->id,
            'judul' => 'Notif 2',
            'konten' => 'Konten 2',
            'tipe' => 'penting',
            'target_type' => 'all',
            'is_active' => true,
        ]);

        // Mark single as read
        $this->actingAs($member, 'member')
            ->postJson(route('portal.announcements.read', $a1))
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('announcement_reads', [
            'announcement_id' => $a1->id,
            'user_id' => $member->id,
        ]);

        // Mark all as read
        $this->actingAs($member, 'member')
            ->postJson(route('portal.announcements.read_all'))
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('announcement_reads', [
            'announcement_id' => $a2->id,
            'user_id' => $member->id,
        ]);
    }

    public function test_member_can_view_announcements_feed_page(): void
    {
        $admin = $this->createAdmin();
        $member = $this->createMember();

        Announcement::create([
            'admin_id' => $admin->id,
            'judul' => 'Feed Test Title',
            'konten' => 'Feed Test Body Content',
            'tipe' => 'info',
            'target_type' => 'all',
            'is_active' => true,
        ]);

        $this->actingAs($member, 'member')
            ->get(route('portal.announcements.index'))
            ->assertOk()
            ->assertSee('Pengumuman & Pesan Admin', false)
            ->assertSee('Feed Test Title')
            ->assertSee('Feed Test Body Content');
    }

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
            'jabatan' => 'Anggota',
            'email' => 'member.'.Str::lower(Str::random(8)).'@himati.test',
            'password' => 'password123',
            'qr_code' => 'HIMATI-TEST-'.Str::upper(Str::random(8)),
            'is_bph' => false,
        ], $attributes));
    }
}

<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Feedback;
use App\Models\Member;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Tests\TestCase;

class FeedbackTest extends TestCase
{
    use DatabaseTransactions;

    public function test_member_can_submit_feedback_without_screenshot(): void
    {
        $member = $this->createMember();

        $response = $this->actingAs($member, 'member')
            ->post(route('portal.feedback.store'), [
                'kategori' => 'saran',
                'subjek' => 'Usulan fitur export absensi ke excel',
                'pesan' => 'Mohon ditambahkan fitur export data rapat ke format XLSX agar lebih mudah dibagikan.',
            ]);

        $response->assertSessionHas('feedback_success');
        $this->assertDatabaseHas('feedback', [
            'user_id' => $member->id,
            'kategori' => 'saran',
            'subjek' => 'Usulan fitur export absensi ke excel',
            'status' => 'pending',
            'screenshot' => null,
        ]);
    }

    public function test_member_can_submit_feedback_with_screenshot(): void
    {
        $member = $this->createMember();
        $fakeImage = UploadedFile::fake()->image('bukti_bug.png', 640, 480);

        $response = $this->actingAs($member, 'member')
            ->post(route('portal.feedback.store'), [
                'kategori' => 'bug',
                'subjek' => 'Tombol scanner tidak merespons',
                'pesan' => 'Saat membuka scanner kamera tidak aktif dan layar hitam.',
                'screenshot' => $fakeImage,
            ]);

        $response->assertSessionHas('feedback_success');

        $feedback = Feedback::where('user_id', $member->id)->latest()->first();
        $this->assertNotNull($feedback);
        $this->assertEquals('bug', $feedback->kategori);
        $this->assertNotNull($feedback->screenshot);

        $filepath = public_path('uploads/feedbacks/'.$feedback->screenshot);
        $this->assertFileExists($filepath);

        // Cleanup created file
        if (File::exists($filepath)) {
            File::delete($filepath);
        }
    }

    public function test_feedback_validation_requires_kategori_subjek_pesan(): void
    {
        $member = $this->createMember();

        $response = $this->actingAs($member, 'member')
            ->post(route('portal.feedback.store'), [
                'kategori' => '',
                'subjek' => '',
                'pesan' => '',
            ]);

        $response->assertSessionHasErrors(['kategori', 'subjek', 'pesan']);
    }

    public function test_member_sees_submitted_feedbacks_in_profile(): void
    {
        $member = $this->createMember();

        Feedback::create([
            'user_id' => $member->id,
            'kategori' => 'pertanyaan',
            'subjek' => 'Bagaimana cara reset password?',
            'pesan' => 'Apakah bisa reset password secara mandiri?',
            'status' => 'read',
            'admin_notes' => 'Silakan gunakan tombol ganti password di profil Anda.',
        ]);

        $response = $this->actingAs($member, 'member')
            ->get(route('portal.profile'));

        $response->assertOk()
            ->assertSee('Beri Masukan')
            ->assertSee('Bagaimana cara reset password?')
            ->assertSee('Silakan gunakan tombol ganti password di profil Anda.');
    }

    public function test_admin_can_view_feedbacks_list(): void
    {
        $admin = $this->createAdmin();
        $member = $this->createMember(['nama_lengkap' => 'Budi Santoso']);

        Feedback::create([
            'user_id' => $member->id,
            'kategori' => 'bug',
            'subjek' => 'Kamera lag saat scan',
            'pesan' => 'Kamera butuh waktu 5 detik untuk membaca QR.',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($admin)
            ->get(route('feedbacks.index'));

        $response->assertOk()
            ->assertSee('Daftar Feedback')
            ->assertSee('Kamera lag saat scan')
            ->assertSee('Budi Santoso');
    }

    public function test_admin_can_update_feedback_status_and_notes(): void
    {
        $admin = $this->createAdmin();
        $member = $this->createMember();

        $feedback = Feedback::create([
            'user_id' => $member->id,
            'kategori' => 'bug',
            'subjek' => 'Gagal absen online',
            'pesan' => 'Tampilan form tidak bisa disubmit.',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($admin)
            ->put(route('feedbacks.update', $feedback), [
                'status' => 'resolved',
                'admin_notes' => 'Kendala sudah diperbaiki pada pembaruan terbaru.',
            ]);

        $response->assertSessionHas('success');
        $this->assertDatabaseHas('feedback', [
            'id' => $feedback->id,
            'status' => 'resolved',
            'admin_notes' => 'Kendala sudah diperbaiki pada pembaruan terbaru.',
        ]);
    }

    public function test_admin_can_delete_feedback_and_removes_screenshot_file(): void
    {
        $admin = $this->createAdmin();
        $member = $this->createMember();

        $fakeFilename = 'feedback_test_del_'.time().'.png';
        $testPath = public_path('uploads/feedbacks/'.$fakeFilename);
        File::put($testPath, 'fake-image-content');

        $feedback = Feedback::create([
            'user_id' => $member->id,
            'kategori' => 'saran',
            'subjek' => 'Testing hapus feedback',
            'pesan' => 'Testing feedback delete flow.',
            'status' => 'pending',
            'screenshot' => $fakeFilename,
        ]);

        $response = $this->actingAs($admin)
            ->delete(route('feedbacks.destroy', $feedback));

        $response->assertSessionHas('success');
        $this->assertDatabaseMissing('feedback', ['id' => $feedback->id]);
        $this->assertFileDoesNotExist($testPath);
    }

    private function createMember(array $attributes = []): Member
    {
        return Member::create(array_merge([
            'nama_lengkap' => 'Pengurus Test '.Str::random(6),
            'departemen' => 'PSDM',
            'jabatan' => 'Anggota',
            'email' => 'member.'.Str::lower(Str::random(8)).'@himati.test',
            'password' => 'password123',
            'qr_code' => 'HIMATI-TEST-'.Str::upper(Str::random(8)),
            'is_bph' => false,
        ], $attributes));
    }

    private function createAdmin(array $attributes = []): Admin
    {
        return Admin::create(array_merge([
            'nama' => 'Admin Test '.Str::random(4),
            'email' => 'admin.'.Str::lower(Str::random(6)).'@himati.id',
            'password' => bcrypt('admin123'),
        ], $attributes));
    }
}

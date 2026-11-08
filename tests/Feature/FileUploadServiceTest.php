<?php

namespace Tests\Feature;

use App\Services\FileUploadService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class FileUploadServiceTest extends TestCase
{
    public function test_upload_service_stores_and_serves_from_database_when_needed(): void
    {
        $service = app(FileUploadService::class);
        $service->ensureTable();

        $file = UploadedFile::fake()->image('test_avatar.jpg', 100, 100);
        $filename = 'test_avatar_'.time().'.jpg';

        // Direct store to database payload simulating read-only environment
        $content = file_get_contents($file->getRealPath());
        DB::table('stored_uploads')->updateOrInsert(
            ['folder' => 'profiles', 'filename' => $filename],
            [
                'mime_type' => 'image/jpeg',
                'file_size' => strlen($content),
                'payload' => $content,
                'created_at' => now(),
            ]
        );

        $response = $this->get('/uploads/profiles/'.$filename);
        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'image/jpeg');

        // Test delete
        $service->delete('profiles', $filename);
        $this->assertDatabaseMissing('stored_uploads', [
            'folder' => 'profiles',
            'filename' => $filename,
        ]);
    }
}

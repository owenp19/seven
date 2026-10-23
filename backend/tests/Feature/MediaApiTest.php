<?php

namespace Tests\Feature;

use App\Models\Media;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MediaApiTest extends TestCase
{
    use RefreshDatabase;

    private function adminHeaders(): array
    {
        return ['X-Admin-Token' => config('api.admin.token')];
    }

    public function test_uploads_updates_and_deletes_media(): void
    {
        Storage::fake('public');

        $this->withHeaders($this->adminHeaders())
            ->post('/api/v1/admin/media', [
                'file' => UploadedFile::fake()->image('cover.png', 10, 10),
                'type' => 'cover',
                'order' => 2,
            ])
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.type', 'cover')
            ->assertJsonPath('data.order', 2);

        $media = Media::firstOrFail();

        $this->assertStringStartsWith('media/', $media->file_path);
        Storage::disk('public')->assertExists($media->file_path);
        Storage::disk('public')->assertExists($media->file_path);

        $this->withHeaders($this->adminHeaders())
            ->patchJson("/api/v1/admin/media/{$media->id}", [
                'type' => 'gallery',
                'order' => 5,
            ])
            ->assertOk()
            ->assertJsonPath('data.type', 'gallery')
            ->assertJsonPath('data.order', 5);

        $this->withHeaders($this->adminHeaders())
            ->deleteJson("/api/v1/admin/media/{$media->id}")
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseMissing('media', ['id' => $media->id]);
        Storage::disk('public')->assertMissing($media->file_path);
    }

    public function test_media_upload_validates(): void
    {
        Storage::fake('public');

        $this->withHeaders($this->adminHeaders())
            ->post('/api/v1/admin/media', ['file' => UploadedFile::fake()->create('not-image.txt', 5)])
            ->assertStatus(422)
            ->assertJsonStructure(['errors' => ['file']]);

        $this->withHeaders($this->adminHeaders())
            ->post('/api/v1/admin/media', [])
            ->assertStatus(422);
    }

    public function test_media_requires_admin_token(): void
    {
        $this->post('/api/v1/admin/media', ['file' => UploadedFile::fake()->image('x.png')])
            ->assertStatus(401);
    }
}

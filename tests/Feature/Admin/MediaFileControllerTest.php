<?php

namespace Tests\Feature\Admin;

use App\Models\MediaFile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Laravel\Facades\Image;
use Tests\TestCase;

class MediaFileControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $superAdmin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->superAdmin = User::factory()->superAdmin()->create();
    }

    public function test_media_library_index_is_accessible_to_super_admin(): void
    {
        MediaFile::create([
            'filename' => 'test.webp',
            'original_name' => 'test.jpg',
            'path' => 'blog/media/test.webp',
            'mime_type' => 'image/webp',
            'size' => 1024,
            'width' => 800,
            'height' => 600,
        ]);

        $response = $this->actingAs($this->superAdmin)->get(route('admin.media-library.index'));

        $response->assertOk();
    }

    public function test_media_library_index_returns_json_if_requested(): void
    {
        MediaFile::create([
            'filename' => 'test.webp',
            'original_name' => 'test.jpg',
            'path' => 'blog/media/test.webp',
            'mime_type' => 'image/webp',
            'size' => 1024,
            'width' => 800,
            'height' => 600,
        ]);

        $response = $this->actingAs($this->superAdmin)
            ->get(route('admin.media-library.index'), ['Accept' => 'application/json']);

        $response->assertOk();
        $response->assertJsonStructure(['files']);
    }

    public function test_unauthorized_user_cannot_access_media_library_index(): void
    {
        $user = User::factory()->create(['is_super_admin' => false]);

        $this->actingAs($user)->get(route('admin.media-library.index'))->assertForbidden();
    }

    public function test_super_admin_can_upload_media_file(): void
    {
        Storage::fake('public');

        $file = UploadedFile::fake()->image('photo.png', 100, 100);

        $response = $this->actingAs($this->superAdmin)
            ->post(route('admin.media-library.store'), [
                'file' => $file,
                'alt_text' => 'A beautiful photo',
                'description' => 'Photo description',
            ]);

        $response->assertStatus(201);
        $response->assertJsonStructure(['file' => ['id', 'filename', 'original_name', 'path', 'url']]);

        $mediaFile = MediaFile::first();
        $this->assertNotNull($mediaFile);
        $this->assertEquals('photo.png', $mediaFile->original_name);
        $this->assertEquals('A beautiful photo', $mediaFile->alt_text);
        $this->assertEquals('Photo description', $mediaFile->description);

        // Assert file exists in fake storage
        Storage::disk('public')->assertExists($mediaFile->path);
    }

    public function test_unauthorized_user_cannot_upload_media_file(): void
    {
        $user = User::factory()->create(['is_super_admin' => false]);
        $file = UploadedFile::fake()->image('photo.png');

        $this->actingAs($user)
            ->post(route('admin.media-library.store'), ['file' => $file])
            ->assertForbidden();
    }

    public function test_super_admin_can_delete_media_file(): void
    {
        Storage::fake('public');

        // Create a physical file in fake storage first
        $path = 'blog/media/delete-me.webp';
        Storage::disk('public')->put($path, 'fake content');

        $mediaFile = MediaFile::create([
            'filename' => 'delete-me.webp',
            'original_name' => 'delete-me.png',
            'path' => $path,
            'mime_type' => 'image/webp',
            'size' => 12,
            'width' => 100,
            'height' => 100,
        ]);

        $response = $this->actingAs($this->superAdmin)
            ->delete(route('admin.media-library.destroy', $mediaFile));

        $response->assertRedirect(route('admin.media-library.index'));
        $this->assertDatabaseMissing('media_files', ['id' => $mediaFile->id]);
        Storage::disk('public')->assertMissing($path);
    }

    public function test_super_admin_upload_falls_back_to_raw_on_image_processing_failure(): void
    {
        Storage::fake('public');

        // Mock the Image facade to throw an exception when decoding
        Image::shouldReceive('decode')
            ->once()
            ->andThrow(new \Exception('Mocked image processing error'));

        $file = UploadedFile::fake()->image('photo.png', 100, 150);

        $response = $this->actingAs($this->superAdmin)
            ->post(route('admin.media-library.store'), [
                'file' => $file,
                'alt_text' => 'Fallback photo',
                'description' => 'Fallback description',
            ]);

        $response->assertStatus(201);

        $mediaFile = MediaFile::first();
        $this->assertNotNull($mediaFile);
        $this->assertEquals('photo.png', $mediaFile->original_name);
        $this->assertEquals('Fallback photo', $mediaFile->alt_text);

        // Check that the mime type matches the uploaded file's original mime type (image/png)
        $this->assertEquals('image/png', $mediaFile->mime_type);

        // Check that the file extension is .png instead of .webp
        $this->assertStringEndsWith('.png', $mediaFile->filename);

        // Assert dimensions were fetched via getimagesize fallback
        $this->assertEquals(100, $mediaFile->width);
        $this->assertEquals(150, $mediaFile->height);

        // Assert file exists in fake storage
        Storage::disk('public')->assertExists($mediaFile->path);
    }
}

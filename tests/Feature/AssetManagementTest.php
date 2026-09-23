<?php

declare(strict_types=1);

namespace Platno\Tests\Feature;

use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Platno\Models\Asset;
use Platno\Models\Page;
use Platno\Tests\PersistenceTestCase;

final class AssetManagementTest extends PersistenceTestCase
{
    private function image(): UploadedFile
    {
        return UploadedFile::fake()->createWithContent('original.png', base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+jRz8AAAAASUVORK5CYII='));
    }

    /** @return array<string, mixed> */
    private function document(string $identifier): array
    {
        return ['version' => 1, 'blocks' => [['type' => 'group', 'version' => 1, 'data' => ['tone' => 'plain', 'children' => [
            ['type' => 'image', 'version' => 1, 'data' => ['asset' => $identifier, 'alt' => 'Example', 'caption' => 'Original image']],
        ]]]]];
    }

    // Prevent private-media leakage and deletion of originals referenced by drafts or retained history.
    public function test_assets_keep_original_bytes_private_until_published_and_retain_history_references(): void
    {
        $this->install();
        $this->postJson('/platno/media', ['file' => $this->image()])->assertForbidden();
        $this->authorizeEditor();
        $upload = $this->image();
        $original = file_get_contents($upload->getPathname());
        $response = $this->postJson('/platno/media', ['file' => $upload])->assertCreated();
        $identifier = $response->json('id');
        $asset = Asset::query()->findOrFail($identifier);
        self::assertSame($original, Storage::disk('platno')->get($asset->path));
        self::assertStringNotContainsString('original.png', $asset->path);
        $this->get('/pages/media/'.$identifier)->assertNotFound();
        $this->get('/platno/media/'.$identifier)->assertOk()->assertStreamedContent($original);
        $this->postJson('/platno/pages', ['title' => 'Media page', 'slug' => 'media-page', 'document' => $this->document($identifier)])->assertRedirect();
        $this->deleteJson('/platno/media/'.$identifier)->assertUnprocessable();
        self::assertSame('active', $asset->fresh()->state);
        $this->post('/platno/pages/1/publish', ['revision' => 1])->assertRedirect();
        $this->get('/pages/media/'.$identifier)->assertOk()->assertHeader('X-Content-Type-Options', 'nosniff')->assertStreamedContent($original);
        $this->putJson('/platno/pages/1', ['revision' => 2, 'title' => 'Without media', 'document' => ['version' => 1, 'blocks' => []]])->assertRedirect();
        $this->post('/platno/pages/1/publish', ['revision' => 3])->assertRedirect();
        $this->get('/pages/media/'.$identifier)->assertNotFound();
        $this->deleteJson('/platno/media/'.$identifier)->assertUnprocessable();
        self::assertTrue(Storage::disk('platno')->exists($asset->path));
    }

    // Prevent executable uploads, type confusion and missing files from producing broken publication references.
    public function test_unsafe_uploads_and_invalid_asset_references_fail_without_losing_the_draft(): void
    {
        $this->install();
        $this->authorizeEditor();
        foreach ([['script.svg', '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>'], ['fake.png', '<?php echo "unsafe";'], ['file.html', '<html>unsafe</html>']] as [$name, $content]) {
            $this->postJson('/platno/media', ['file' => UploadedFile::fake()->createWithContent($name, $content)])->assertUnprocessable();
        }
        self::assertSame(0, Asset::query()->count());
        $this->post('/platno/pages', ['title' => 'Keep', 'slug' => 'keep', 'text' => 'Original']);
        $this->putJson('/platno/pages/1', ['revision' => 1, 'title' => 'Lost', 'document' => $this->document((string) Str::uuid())])->assertUnprocessable();
        self::assertSame(1, Page::query()->firstOrFail()->revision);
        self::assertSame('Original', Page::query()->firstOrFail()->document['blocks'][0]['data']['text']);
        $response = $this->postJson('/platno/media', ['file' => UploadedFile::fake()->createWithContent('guide.pdf', "%PDF-1.4\n1 0 obj\n<<>>\nendobj\n%%EOF")])->assertCreated();
        $this->putJson('/platno/pages/1', ['revision' => 1, 'title' => 'Lost', 'document' => $this->document($response->json('id'))])->assertUnprocessable();
        $this->get('/platno/media/'.$response->json('id'))->assertOk()->assertHeader('Content-Disposition', 'attachment; filename="'.$response->json('id').'.pdf"');
    }

    // Prevent orphaned storage objects when an unused upload is deleted or a disk deletion fails.
    public function test_unused_asset_deletion_removes_the_original_and_failed_deletion_keeps_its_key_for_retry(): void
    {
        $this->install();
        $this->authorizeEditor();
        $identifier = $this->postJson('/platno/media', ['file' => $this->image()])->assertCreated()->json('id');
        $asset = Asset::query()->findOrFail($identifier);
        $disk = Storage::disk('platno');
        $failingDisk = \Mockery::mock(FilesystemAdapter::class);
        $failingDisk->shouldReceive('exists')->once()->with($asset->path)->andReturn(true);
        $failingDisk->shouldReceive('delete')->once()->with($asset->path)->andReturn(false);
        $this->app['filesystem']->set('platno', $failingDisk);
        $this->deleteJson('/platno/media/'.$identifier)->assertUnprocessable();
        self::assertSame('deleting', $asset->fresh()->state);
        self::assertSame($asset->path, $asset->fresh()->path);
        $this->app['filesystem']->set('platno', $disk);
        $this->delete('/platno/media/'.$identifier)->assertRedirect();
        self::assertNull($asset->fresh());
        self::assertFalse($disk->exists($asset->path));
    }
}

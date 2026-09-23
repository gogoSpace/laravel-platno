<?php

declare(strict_types=1);

namespace Platno\Assets;

use Illuminate\Contracts\Filesystem\Factory;
use Illuminate\Database\DatabaseManager;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Platno\Models\Asset;
use RuntimeException;
use Throwable;

final class AssetLibrary
{
    public function __construct(private readonly Factory $storage, private readonly DatabaseManager $database) {}

    public function upload(UploadedFile $file): Asset
    {
        Validator::make(['file' => $file], ['file' => ['required', 'file', 'max:'.config('platno.assets.max_kilobytes', 10240), 'mimetypes:image/jpeg,image/png,image/webp,application/pdf']])->validate();
        $mimeType = $file->getMimeType();
        $extension = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'application/pdf' => 'pdf'][$mimeType];
        if (str_starts_with($mimeType, 'image/')) {
            $dimensions = @getimagesize($file->getPathname());
            if ($dimensions === false || $dimensions[0] > 12000 || $dimensions[1] > 12000 || $dimensions[0] * $dimensions[1] > 40000000) {
                throw ValidationException::withMessages(['file' => __('platno::editor.invalid_image')]);
            }
        }
        $identifier = (string) Str::uuid();
        $asset = Asset::query()->create([
            'id' => $identifier,
            'disk' => config('platno.assets.disk', 'platno'),
            'path' => 'originals/'.$identifier.'.'.$extension,
            'name' => mb_substr(preg_replace('/[\x00-\x1f\x7f]/', '', basename($file->getClientOriginalName())), 0, 255),
            'mime_type' => $mimeType,
            'size' => $file->getSize(),
            'state' => 'uploading',
        ]);
        try {
            $stored = $this->storage->disk($asset->disk)->putFileAs('originals', $file, $identifier.'.'.$extension, ['visibility' => 'private']);
            if ($stored === false) {
                throw new RuntimeException('The configured disk refused the upload.');
            }
            $asset->update(['state' => 'active']);
        } catch (Throwable $exception) {
            $asset->update(['state' => 'failed']);
            report($exception);
            throw ValidationException::withMessages(['file' => __('platno::editor.upload_failed')]);
        }

        return $asset;
    }

    public function delete(string $identifier): void
    {
        $asset = $this->database->connection()->transaction(function () use ($identifier): Asset {
            // A write before checking references serializes SQLite and row-locking engines alike.
            $affected = Asset::query()->whereKey($identifier)->where('state', '!=', 'uploading')->update(['state' => 'deleting']);
            abort_unless($affected === 1, 404);
            $asset = Asset::query()->findOrFail($identifier);
            if ($this->database->table('platno_asset_references')->where('asset_id', $identifier)->exists()) {
                throw ValidationException::withMessages(['file' => __('platno::editor.asset_referenced')]);
            }

            return $asset;
        });
        try {
            $disk = $this->storage->disk($asset->disk);
            if ($disk->exists($asset->path) && ! $disk->delete($asset->path)) {
                throw new RuntimeException('The configured disk refused deletion.');
            }
        } catch (Throwable $exception) {
            report($exception);
            // Keep the key and tombstone so a failed remote deletion can be retried.
            throw ValidationException::withMessages(['file' => __('platno::editor.delete_failed')]);
        }
        $asset->delete();
    }
}

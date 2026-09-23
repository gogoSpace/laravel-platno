<?php

declare(strict_types=1);

namespace Platno\Http\Controllers;

use Illuminate\Contracts\Filesystem\Factory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Platno\Assets\AssetLibrary;
use Platno\Assets\AssetReferences;
use Platno\Models\Asset;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class AssetController
{
    public function index(Request $request): JsonResponse|Response
    {
        $input = $request->validate(['search' => ['nullable', 'string', 'max:100'], 'kind' => ['nullable', 'in:image,file'], 'page' => ['sometimes', 'integer', 'min:1']]);
        $query = Asset::query()->orderByDesc('created_at')->orderBy('id');
        if ($request->expectsJson()) {
            $query->where('state', 'active');
        }
        if (($input['kind'] ?? null) === 'image') {
            $query->whereIn('mime_type', ['image/jpeg', 'image/png', 'image/webp']);
        }
        if ($input['search'] ?? '') {
            $query->where('name', 'like', '%'.$input['search'].'%');
        }
        $assets = $query->simplePaginate(24)->withQueryString();
        if ($request->expectsJson()) {
            return response()->json(['assets' => array_map(fn (Asset $asset): array => $this->describe($asset), $assets->items()), 'next' => $assets->nextPageUrl(), 'previous' => $assets->previousPageUrl()]);
        }

        return response()->view('platno::editor.assets', ['assets' => $assets]);
    }

    public function store(Request $request, AssetLibrary $library): JsonResponse|RedirectResponse
    {
        $request->validate(['file' => ['required', 'file']]);
        $asset = $library->upload($request->file('file'));

        return $request->expectsJson() ? response()->json($this->describe($asset), 201) : redirect()->route('platno.assets.index')->with('platno.status', __('platno::editor.uploaded'));
    }

    public function destroy(string $asset, AssetLibrary $library): RedirectResponse
    {
        $library->delete($asset);

        return redirect()->route('platno.assets.index')->with('platno.status', __('platno::editor.asset_deleted'));
    }

    public function show(string $asset, Factory $storage): StreamedResponse
    {
        return $this->stream(Asset::query()->where('state', 'active')->findOrFail($asset), $storage);
    }

    public function published(string $asset, Factory $storage, AssetReferences $references): StreamedResponse
    {
        abort_unless($references->isPublic($asset), 404);

        return $this->stream(Asset::query()->where('state', 'active')->findOrFail($asset), $storage);
    }

    /** @return array<string, mixed> */
    private function describe(Asset $asset): array
    {
        return ['id' => $asset->getKey(), 'name' => $asset->name, 'mime_type' => $asset->mime_type, 'size' => $asset->size, 'image' => $asset->isImage(), 'url' => route('platno.assets.show', $asset->getKey())];
    }

    private function stream(Asset $asset, Factory $storage): StreamedResponse
    {
        $stream = $storage->disk($asset->disk)->readStream($asset->path);
        abort_unless(is_resource($stream), 404);

        return response()->stream(function () use ($stream): void {
            try {
                fpassthru($stream);
            } finally {
                fclose($stream);
            }
        }, 200, [
            'Content-Type' => $asset->mime_type,
            'Content-Length' => (string) $asset->size,
            'Content-Disposition' => ($asset->isImage() ? 'inline' : 'attachment').'; filename="'.basename($asset->path).'"',
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
            'Content-Security-Policy' => "default-src 'none'; sandbox",
        ]);
    }
}

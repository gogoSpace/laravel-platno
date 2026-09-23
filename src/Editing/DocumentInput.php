<?php

declare(strict_types=1);

namespace Platno\Editing;

use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use JsonException;
use Platno\Documents\DocumentValidator;

final class DocumentInput
{
    public function __construct(private readonly DocumentValidator $documents) {}

    /** @param array<string, mixed>|null $current
     * @return array<string, mixed>
     */
    public function read(Request $request, ?array $current = null): array
    {
        if (! $request->exists('document')) {
            if (! $request->exists('text')) {
                throw ValidationException::withMessages(['document' => __('platno::editor.invalid_document')]);
            }
            if ($current !== null) {
                $this->documents->editableText($current);
            }

            $input = $request->validate(['text' => ['nullable', 'string', 'max:50000']]);

            return $this->documents->text($input['text'] ?? '');
        }

        $document = $request->input('document');

        // Laravel's global string normalizers must not trim canonical plugin data.
        if ($request->isJson()) {
            if (strlen($request->getContent()) > 2100000) {
                throw ValidationException::withMessages(['document' => __('platno::editor.document_too_large')]);
            }
            try {
                $payload = json_decode($request->getContent(), true, 64, JSON_THROW_ON_ERROR);
                $document = $payload['document'] ?? null;
            } catch (JsonException) {
                throw ValidationException::withMessages(['document' => __('platno::editor.invalid_document')]);
            }
        }

        if (is_string($document)) {
            if (strlen($document) > 2000000) {
                throw ValidationException::withMessages(['document' => __('platno::editor.document_too_large')]);
            }

            try {
                $document = json_decode($document, true, 64, JSON_THROW_ON_ERROR);
            } catch (JsonException) {
                throw ValidationException::withMessages(['document' => __('platno::editor.invalid_document')]);
            }
        }

        if (! is_array($document)) {
            throw ValidationException::withMessages(['document' => __('platno::editor.invalid_document')]);
        }

        return $this->documents->validate($document);
    }
}

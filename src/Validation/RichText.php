<?php

declare(strict_types=1);

namespace Platno\Validation;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Validator;

final class RichText implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $validator = Validator::make(['content' => $value], [
            'content' => ['present', 'array', 'list', 'max:100'],
            'content.*' => ['required', 'array:type,runs'],
            'content.*.type' => ['required', 'string', 'in:paragraph,bullet,numbered'],
            'content.*.runs' => ['present', 'array', 'list', 'max:200'],
            'content.*.runs.*' => ['required', 'array:text,bold,italic,link'],
            'content.*.runs.*.text' => ['present', 'string', 'max:10000'],
            'content.*.runs.*.bold' => ['required', 'boolean'],
            'content.*.runs.*.italic' => ['required', 'boolean'],
            'content.*.runs.*.link' => ['present', 'string', 'max:2048', new SafeLink],
        ]);

        if ($validator->fails() || strlen(json_encode($value, JSON_THROW_ON_ERROR)) > 200000) {
            $fail(__('platno::editor.invalid_rich_text'));
        }
    }
}

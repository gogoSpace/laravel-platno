<?php

declare(strict_types=1);

namespace Platno\Validation;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

final class SafeLink implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || preg_match('/[\x00-\x20\x7f\\\\]/', $value)) {
            $fail('The :attribute must be a web address, a path starting with /, or a #fragment.');

            return;
        }

        if ($value === '' || (str_starts_with($value, '/') && ! str_starts_with($value, '//')) || str_starts_with($value, '#')) {
            return;
        }

        if (filter_var($value, FILTER_VALIDATE_URL) === false || ! in_array(strtolower((string) parse_url($value, PHP_URL_SCHEME)), ['http', 'https'], true)) {
            $fail('The :attribute must be a safe http or https address.');
        }
    }
}

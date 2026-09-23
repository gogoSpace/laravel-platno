<?php

declare(strict_types=1);

namespace Platno\Rendering;

use InvalidArgumentException;

final class Theme
{
    /** @return array<string, string|int> */
    public function tokens(): array
    {
        $defaults = ['background' => '#ffffff', 'text' => '#202821', 'accent' => '#285937', 'surface' => '#eef1e9', 'width' => 1080, 'font' => 'system'];
        $configured = config('platno.theme', []);
        if (! is_array($configured) || array_diff_key($configured, $defaults) !== []) {
            throw new InvalidArgumentException('Platno theme contains unknown tokens.');
        }
        $tokens = array_replace($defaults, $configured);
        foreach (['background', 'text', 'accent', 'surface'] as $name) {
            if (! is_string($tokens[$name]) || preg_match('/\A#[0-9a-fA-F]{6}\z/', $tokens[$name]) !== 1) {
                throw new InvalidArgumentException("Platno theme [{$name}] requires a six-digit hex color.");
            }
        }
        if (! is_int($tokens['width']) || $tokens['width'] < 640 || $tokens['width'] > 1600 || ! in_array($tokens['font'], ['system', 'serif'], true)) {
            throw new InvalidArgumentException('Platno theme requires width 640–1600 and font system or serif.');
        }

        return $tokens;
    }
}

<?php

declare(strict_types=1);

namespace Platno\Tests\Fixtures;

use Platno\Contracts\BlockPlugin;

final class CalloutPlugin implements BlockPlugin
{
    public function type(): string
    {
        return 'example.callout';
    }

    public function schemaVersion(): int
    {
        return 1;
    }

    public function defaults(): array
    {
        return ['message' => ''];
    }

    public function rules(): array
    {
        return ['message' => ['required', 'string', 'max:2000']];
    }

    public function view(): string
    {
        return 'fixture::callout';
    }
}

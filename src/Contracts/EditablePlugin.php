<?php

declare(strict_types=1);

namespace Platno\Contracts;

interface EditablePlugin extends BlockPlugin
{
    public function label(): string;

    /** @return array<string, array<string, mixed>> */
    public function fields(): array;
}

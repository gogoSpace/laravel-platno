<?php

declare(strict_types=1);

namespace Platno\Contracts;

interface BlockPlugin
{
    public function type(): string;

    public function schemaVersion(): int;

    /** @return array<string, mixed> */
    public function defaults(): array;

    /** @return array<string, list<mixed>> */
    public function rules(): array;

    public function view(): string;
}

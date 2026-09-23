<?php

declare(strict_types=1);

namespace Platno\Tests\Fixtures;

use Platno\Plugins\Plugin;

final class NoticePlugin extends Plugin
{
    public function type(): string
    {
        return 'example.notice';
    }

    public function label(): string
    {
        return 'Notice';
    }

    public function fields(): array
    {
        return [
            'message' => ['type' => 'textarea', 'label' => 'Message', 'required' => true, 'default' => 'A new notice'],
            'tone' => ['type' => 'select', 'label' => 'Tone', 'options' => ['note' => 'Note', 'important' => 'Important'], 'default' => 'note'],
        ];
    }

    public function view(): string
    {
        return 'fixture::callout';
    }
}

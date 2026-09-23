<?php

declare(strict_types=1);

namespace App\Content;

use Platno\Plugins\Plugin;

final class Notice extends Plugin
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
            'message' => ['type' => 'textarea', 'label' => 'Message', 'required' => true, 'default' => 'Something worth sharing'],
            'tone' => ['type' => 'select', 'label' => 'Tone', 'options' => ['note' => 'Note', 'important' => 'Important'], 'default' => 'note'],
        ];
    }

    public function view(): string
    {
        return 'content.notice';
    }
}

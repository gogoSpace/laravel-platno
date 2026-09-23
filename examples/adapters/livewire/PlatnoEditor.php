<?php

declare(strict_types=1);

namespace App\Livewire;

use Illuminate\Contracts\View\View;
use Livewire\Attributes\Locked;
use Livewire\Component;

final class PlatnoEditor extends Component
{
    #[Locked]
    public int $pageIdentifier;

    public function render(): View
    {
        return view('livewire.platno-editor', ['editorAddress' => route('platno.pages.edit', $this->pageIdentifier, false)]);
    }
}

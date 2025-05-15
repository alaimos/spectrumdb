<?php

declare(strict_types=1);

namespace App\Livewire\Pages;

use Illuminate\Contracts\View\View;
use Livewire\Component;

final class Playground extends Component
{
    public function render(): View
    {
        return view('livewire.pages.playground');
    }
}

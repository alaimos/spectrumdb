<?php

namespace App\Livewire\Pages;

use Illuminate\Contracts\View\View;
use Livewire\Component;

class Dashboard extends Component
{
    public function render(): View
    {
        return view('livewire.pages.dashboard');
    }
}

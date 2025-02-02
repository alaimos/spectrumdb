<?php

namespace App\Livewire\Components\Layout;

use App\Livewire\Actions\Logout as LogoutAction;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class LogoutButton extends Component
{
    public function logout(LogoutAction $logoutAction): void
    {
        $logoutAction();

        $this->redirect('/', navigate: true);
    }

    public function render(): View
    {
        return view('livewire.components.layout.logout-button');
    }
}

<?php

declare(strict_types=1);

namespace App\Livewire\Components;

use App\Enums\DatasetPermission;
use App\Models\Dataset;
use App\Models\User;
use Flux;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Component;

final class DatasetPermissions extends Component
{
    public Dataset $dataset;

    public ?string $selectedUserId = null;

    public ?string $selectedPermission = null;

    public bool $showAddForm = false;

    public function mount(Dataset $dataset): void
    {
        $this->dataset = $dataset;
        $this->selectedPermission = DatasetPermission::READ->value;
        $this->showAddForm = false;
    }

    public function toggleAddForm(): void
    {
        $this->showAddForm = ! $this->showAddForm;
    }

    #[Computed]
    public function availableUsers(): Collection
    {
        return User::query()
            ->where('id', '!=', $this->dataset->created_by)
            ->whereNotIn('id', $this->dataset->users->pluck('id'))
            ->get();
    }

    #[Computed]
    public function currentUsers(): Collection
    {
        return $this->dataset->users;
    }

    public function grantAccess(): void
    {
        if (! $this->selectedUserId || ! $this->selectedPermission) {
            return;
        }

        $user = User::find($this->selectedUserId);
        $permission = DatasetPermission::from($this->selectedPermission);

        $this->dataset->grantPermission($user, $permission);

        $this->selectedUserId = null;
        Flux::toast(
            text: 'Permission granted successfully',
            variant: 'success'
        );
    }

    public function revokeAccess(int $userId): void
    {
        $user = User::find($userId);
        $this->dataset->revokeAllPermissions($user);
        Flux::toast(
            text: 'Permission revoked successfully',
            variant: 'success'
        );
    }

    public function render(): View
    {
        return view('livewire.components.dataset-permissions');
    }
}

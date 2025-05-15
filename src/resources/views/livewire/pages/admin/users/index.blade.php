<div>
    <x-page-heading-split title="Manage Users" subtitle="View and manage all users in the system">
        <div class="flex flex-col-reverse gap-4 mb-2">
            {{-- Search --}}
            <div class="grid gap-4 sm:grid-cols-2">
                <flux:input wire:model.live.debounce.300ms="search" placeholder="Search by name or email..."
                    icon="magnifying-glass" clearable />
                <flux:select wire:model.live="roleFilter" variant="listbox" placeholder="Filter by role" icon="user-group"
                    clearable>
                    @foreach (\App\Enums\Role::cases() as $role)
                        <flux:select.option value="{{ $role->value }}">
                            {{ $role->label() }}
                        </flux:select.option>
                    @endforeach
                </flux:select>
            </div>
            {{-- Create button --}}
            <flux:button href="{{ route('admin.users.create') }}" wire:navigate variant="primary" icon="plus">
                Add User
            </flux:button>
        </div>
    </x-page-heading-split>
    <flux:card>
        <flux:table :paginate="$this->users">
            <flux:table.columns>
                <flux:table.column sortable :sorted="$sortBy === 'name'" :direction="$sortDirection"
                    wire:click="sort('name')">
                    Name
                </flux:table.column>
                <flux:table.column sortable :sorted="$sortBy === 'email'" :direction="$sortDirection"
                    wire:click="sort('email')">
                    Email
                </flux:table.column>
                <flux:table.column sortable :sorted="$sortBy === 'role'" :direction="$sortDirection"
                    wire:click="sort('role')">
                    Role
                </flux:table.column>
                <flux:table.column sortable :sorted="$sortBy === 'created_at'" :direction="$sortDirection"
                    wire:click="sort('created_at')">
                    Created At
                </flux:table.column>
                <flux:table.column align="right">
                    <div class="sr-only">Actions</div>
                </flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @foreach ($this->users as $user)
                    <flux:table.row :key="$user->id">
                        <flux:table.cell>
                            <div class="font-medium">{{ $user->name }}</div>
                        </flux:table.cell>
                        <flux:table.cell>{{ $user->email }}</flux:table.cell>
                        <flux:table.cell>
                            <flux:badge variant="pill" :color="$user->role->color()">
                                {{ $user->role->label() }}
                            </flux:badge>
                        </flux:table.cell>
                        <flux:table.cell>{{ $user->created_at->format('M d, Y') }}</flux:table.cell>
                        <flux:table.cell class="text-right space-x-2">
                            <flux:button href="{{ route('admin.users.edit', $user) }}" wire:navigate variant="ghost"
                                size="sm" icon="pencil">
                                Edit
                            </flux:button>
                            <x-delete-button size="sm" icon="trash" wire:click="deleteUser({{ $user->id }})"
                                :disabled="$user->id === auth()->id()" :id="$user->id" title="Delete user {{ $user->name }}?">
                                <p>Are you sure you want to delete this user?</p>
                                <p>This action cannot be undone.</p>
                            </x-delete-button>
                        </flux:table.cell>
                    </flux:table.row>
                @endforeach
            </flux:table.rows>
        </flux:table>
    </flux:card>
</div>

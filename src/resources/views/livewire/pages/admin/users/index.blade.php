<flux:card>
    <div class="flex items-center justify-between mb-6">
        <div>
            <flux:heading size="lg">Manage Users</flux:heading>
            <flux:subheading>View and manage all users in the system</flux:subheading>
        </div>

        <flux:button
            href="{{ route('admin.users.create') }}"
            wire:navigate
            variant="primary"
            icon="plus"
        >
            Add User
        </flux:button>
    </div>

    <div class="space-y-4">
        <flux:input
            wire:model.live.debounce.300ms="search"
            placeholder="Search by name or email..."
            icon="magnifying-glass"
            clearable
        />

        <flux:table :paginate="$this->users">
            <flux:columns>
                <flux:column
                    sortable
                    :sorted="$sortBy === 'name'"
                    :direction="$sortDirection"
                    wire:click="sort('name')"
                >
                    Name
                </flux:column>
                <flux:column
                    sortable
                    :sorted="$sortBy === 'email'"
                    :direction="$sortDirection"
                    wire:click="sort('email')"
                >
                    Email
                </flux:column>
                <flux:column
                    sortable
                    :sorted="$sortBy === 'created_at'"
                    :direction="$sortDirection"
                    wire:click="sort('created_at')"
                >
                    Created At
                </flux:column>
                <flux:column align="right">
                    <div class="sr-only">Actions</div>
                </flux:column>
            </flux:columns>

            <flux:rows>
                @foreach($this->users as $user)
                    <flux:row :key="$user->id">
                        <flux:cell>
                            <div class="font-medium">{{ $user->name }}</div>
                        </flux:cell>
                        <flux:cell>{{ $user->email }}</flux:cell>
                        <flux:cell>{{ $user->created_at->format('M d, Y') }}</flux:cell>
                        <flux:cell class="text-right space-x-2">
                            <flux:button
                                href="{{ route('admin.users.edit', $user) }}"
                                wire:navigate
                                variant="ghost"
                                size="sm"
                                icon="pencil"
                            >
                                Edit
                            </flux:button>
                            <flux:button
                                wire:click="deleteUser({{ $user->id }})"
                                wire:confirm="Are you sure you want to delete this user? This action cannot be undone."
                                variant="danger"
                                size="sm"
                                icon="trash"
                            >
                                Delete
                            </flux:button>
                        </flux:cell>
                    </flux:row>
                @endforeach
            </flux:rows>
        </flux:table>
    </div>
</flux:card>

<div class="space-y-6">
    <div>
        <flux:heading size="lg">Manage Access</flux:heading>
        <flux:subheading>Control who can access "{{ $dataset->name }}"</flux:subheading>
    </div>

    {{-- Current Users List --}}
    <div class="space-y-4">
        <div class="flex items-center justify-between">
            <flux:heading size="base">Users with Access</flux:heading>
            <flux:button
                wire:click="toggleAddForm"
                variant="ghost"
                size="sm"
                icon="{{ $showAddForm ? 'minus' : 'plus' }}"
            >
                {{ $showAddForm ? 'Hide Form' : 'Add User' }}
            </flux:button>
        </div>

        <flux:card>
            @if($this->currentUsers->isNotEmpty())
                <div class="divide-y">
                    @foreach($this->currentUsers as $user)
                        <div class="flex items-center justify-between py-3 first:pt-0 last:pb-0">
                            <div>
                                <div class="font-medium">{{ $user->name }}</div>
                                <div class="text-sm text-gray-500">{{ $user->email }}</div>
                            </div>
                            <div class="flex items-center gap-3">
                                <flux:badge>{{ \App\Enums\DatasetPermission::from($user->pivot->permission)->label() }}</flux:badge>
                                <flux:button
                                    wire:click="revokeAccess({{ $user->id }})"
                                    variant="ghost"
                                    size="xs"
                                    icon="x-mark"
                                    negative
                                    label-sr-only="Revoke access"
                                />
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="py-12">
                    <div class="text-center">
                        <div class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-gray-100 mb-4">
                            <flux:icon.users class="w-6 h-6 text-gray-400"/>
                        </div>
                        <div class="text-sm text-gray-500">No users have been granted access yet</div>
                    </div>
                </div>
            @endif
        </flux:card>
    </div>

    {{-- Add User Form --}}
    <div class="{{ $showAddForm ? 'block' : 'hidden' }}">
        <div class="border-t pt-6">
            <flux:heading size="base">Add New User</flux:heading>

            @if($this->availableUsers->isNotEmpty())
                <div class="mt-4 space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <flux:select
                            wire:model.live="selectedUserId"
                            label="Select User"
                            placeholder="Choose a user..."
                            variant="listbox"
                            searchable
                            hint="Search by name or email"
                        >
                            @foreach($this->availableUsers as $user)
                                <flux:select.option value="{{ $user->id }}">
                                    <div>
                                        <div>{{ $user->name }}</div>
                                        <div class="text-sm text-gray-500">{{ $user->email }}</div>
                                    </div>
                                </flux:select.option>
                            @endforeach
                        </flux:select>

                        <flux:select
                            wire:model="selectedPermission"
                            label="Permission Level"
                            hint="Choose what the user can do"
                        >
                            @foreach(App\Enums\DatasetPermission::getAllPermissions() as $permission)
                                <flux:select.option value="{{ $permission->value }}">
                                    {{ $permission->label() }}
                                </flux:select.option>
                            @endforeach
                        </flux:select>
                    </div>

                    <div class="flex justify-end">
                        <flux:button
                            wire:click="grantAccess"
                            variant="primary"
                            :disabled="!$selectedUserId"
                        >
                            Grant Access
                        </flux:button>
                    </div>
                </div>
            @else
                <div class="mt-4">
                    <flux:card>
                        <div class="py-12">
                            <div class="text-center">
                                <div
                                    class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-gray-100 mb-4">
                                    <flux:icon.user-plus class="w-6 h-6 text-gray-400"/>
                                </div>
                                <div class="text-sm text-gray-500">No more users available to add</div>
                            </div>
                        </div>
                    </flux:card>
                </div>
            @endif
        </div>
    </div>
</div>

<div class="space-y-6">
    <div>
        <flux:heading size="lg">Manage Access</flux:heading>
        <flux:subheading>Control who can access "{{ $dataset->name }}"</flux:subheading>
    </div>

    <div class="grid grid-cols-12 gap-6">
        {{-- Current Users List --}}
        <div class="col-span-7 space-y-4">
            <div class="flex items-center justify-between">
                <flux:heading size="base">Users with Access</flux:heading>
                <flux:button
                    wire:click="toggleAddForm"
                    variant="ghost"
                    size="sm"
                    icon="plus"
                    class="lg:hidden"
                >
                    Add User
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
                                    <flux:badge>{{ str($user->permission)->title() }}</flux:badge>
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
                                <flux:icon.users class="w-6 h-6 text-gray-400" />
                            </div>
                            <div class="text-sm text-gray-500">No users have been granted access yet</div>
                        </div>
                    </div>
                @endif
            </flux:card>
        </div>

        {{-- Add User Form --}}
        <div class="col-span-5 space-y-6 {{ $showAddForm ? 'block' : 'hidden lg:block' }}">
            <flux:heading size="base">Add New User</flux:heading>

            <flux:card class="space-y-6">
                @if($this->availableUsers->isNotEmpty())
                    <div class="space-y-4">
                        <flux:select
                            wire:model.live="selectedUserId"
                            label="Select User"
                            placeholder="Choose a user..."
                            searchable
                            hint="Search by name or email"
                        >
                            @foreach($this->availableUsers as $user)
                                <flux:option value="{{ $user->id }}">
                                    <div>
                                        <div>{{ $user->name }}</div>
                                        <div class="text-sm text-gray-500">{{ $user->email }}</div>
                                    </div>
                                </flux:option>
                            @endforeach
                        </flux:select>

                        <flux:select
                            wire:model="selectedPermission"
                            label="Permission Level"
                            hint="Choose what the user can do"
                        >
                            @foreach(App\Enums\DatasetPermission::getAllPermissions() as $permission)
                                <flux:option value="{{ $permission->value }}">
                                    {{ str($permission->value)->title() }}
                                </flux:option>
                            @endforeach
                        </flux:select>

                        <div class="pt-2">
                            <flux:button
                                wire:click="grantAccess"
                                variant="primary"
                                class="w-full"
                                :disabled="!$selectedUserId"
                            >
                                Grant Access
                            </flux:button>
                        </div>
                    </div>
                @else
                    <div class="py-12">
                        <div class="text-center">
                            <div class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-gray-100 mb-4">
                                <flux:icon.user-plus class="w-6 h-6 text-gray-400" />
                            </div>
                            <div class="text-sm text-gray-500">No more users available to add</div>
                        </div>
                    </div>
                @endif
            </flux:card>
        </div>
    </div>
</div>

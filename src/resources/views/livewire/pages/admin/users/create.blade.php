<div>
    <div class="flex items-center mb-6">
        <div>
            <flux:heading size="lg">Create New User</flux:heading>
            <flux:subheading>Add a new user to the system</flux:subheading>
        </div>
        <flux:spacer/>
    </div>
    <flux:card>
        <form wire:submit="save" class="space-y-6">
            <div class="grid gap-6 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <flux:input
                        wire:model="name"
                        label="Full Name"
                        placeholder="Enter user's full name"
                        icon="user"
                        required
                    />
                </div>

                <div class="sm:col-span-2">
                    <flux:input
                        wire:model="email"
                        label="Email Address"
                        type="email"
                        placeholder="user@example.com"
                        icon="envelope"
                        required
                    />
                </div>

                <div class="sm:col-span-2">
                    <flux:select
                        wire:model="role"
                        label="User Role"
                        icon="user-group"
                        required
                    >
                        <flux:select.option value="">Select a role</flux:select.option>
                        @foreach(\App\Enums\Role::cases() as $role)
                            <flux:select.option value="{{ $role->value }}">
                                {{ $role->label() }}
                            </flux:select.option>
                        @endforeach
                    </flux:select>
                </div>

                <div>
                    <flux:input
                        wire:model="password"
                        label="Password"
                        type="password"
                        placeholder="••••••••"
                        icon="key"
                        required
                    />
                </div>

                <div>
                    <flux:input
                        wire:model="password_confirmation"
                        label="Confirm Password"
                        type="password"
                        placeholder="••••••••"
                        icon="key"
                        required
                    />
                </div>
            </div>

            <div class="flex items-center justify-end gap-x-3 pt-4">
                <flux:button
                    href="{{ route('admin.users.index') }}"
                    wire:navigate
                    variant="ghost"
                >
                    Cancel
                </flux:button>
                <flux:button
                    type="submit"
                    variant="primary"
                    icon="user-plus"
                >
                    Create User
                </flux:button>
            </div>
        </form>
    </flux:card>
</div>

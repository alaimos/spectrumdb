<flux:card>
    <div class="flex items-center justify-between mb-6">
        <div>
            <flux:heading size="lg">Edit User</flux:heading>
            <flux:subheading>Update user information</flux:subheading>
        </div>
    </div>

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
                    <option value="">Select a role</option>
                    <option value="{{ \App\Enums\Role::ADMIN->value }}">Administrator</option>
                    <option value="{{ \App\Enums\Role::FARM->value }}">Farm</option>
                    <option value="{{ \App\Enums\Role::RESEARCHER->value }}">Researcher</option>
                </flux:select>
            </div>

            <div>
                <flux:input
                    wire:model="password"
                    label="New Password"
                    type="password"
                    placeholder="Leave blank to keep current"
                    icon="key"
                    help="Leave blank to keep the current password"
                />
            </div>

            <div>
                <flux:input
                    wire:model="password_confirmation"
                    label="Confirm New Password"
                    type="password"
                    placeholder="Leave blank to keep current"
                    icon="key"
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
                icon="user"
            >
                Update User
            </flux:button>
        </div>
    </form>
</flux:card>

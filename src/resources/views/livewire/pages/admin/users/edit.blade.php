<div>
    <div class="flex items-center mb-6">
        <div>
            <flux:heading size="lg">{{ __('Edit User') }}</flux:heading>
            <flux:subheading>{{ __('Update user information') }}</flux:subheading>
        </div>
        <flux:spacer/>
    </div>
    <flux:card>
        <form wire:submit="save" class="space-y-6">
            <div class="grid gap-6 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <flux:input
                        wire:model="name"
                        label="{{ __('Full Name') }}"
                        placeholder="{{ __('Enter user\'s full name') }}"
                        icon="user"
                        required
                    />
                </div>

                <div class="sm:col-span-2">
                    <flux:input
                        wire:model="email"
                        label="{{ __('Email Address') }}"
                        type="email"
                        placeholder="user@example.com"
                        icon="envelope"
                        required
                    />
                </div>

                <div class="sm:col-span-2">
                    <flux:select
                        wire:model="role"
                        label="{{ __('User Role') }}"
                        placeholder="{{ __('Select a role') }}"
                        icon="user-group"
                        required
                    >
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
                        label="{{ __('New Password') }}"
                        type="password"
                        placeholder="{{ __('Leave blank to keep current') }}"
                        icon="key"
                        help="{{ __('Leave blank to keep the current password') }}"
                    />
                </div>

                <div>
                    <flux:input
                        wire:model="password_confirmation"
                        label="{{ __('Confirm New Password') }}"
                        type="password"
                        placeholder="{{ __('Leave blank to keep current') }}"
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
                    {{ __('Cancel') }}
                </flux:button>
                <flux:button
                    type="submit"
                    variant="primary"
                    icon="user"
                >
                    {{ __('Update User') }}
                </flux:button>
            </div>
        </form>
    </flux:card>
</div>

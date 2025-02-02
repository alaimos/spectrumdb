<div class="space-y-6">
    <flux:card>
        <form wire:submit="updateProfileInformation" class="space-y-6">
            <div>
                <flux:heading size="lg">Profile Information</flux:heading>
                <flux:subheading>Update your account's profile information and email address.</flux:subheading>
            </div>

            <div class="space-y-6">
                <flux:input wire:model="name" label="Name" type="text" placeholder="Your name" required
                            autofocus />

                <flux:input wire:model="email" label="Email" type="email" placeholder="Your email address"
                            required />

                @if (auth()->user() instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && !auth()->user()->hasVerifiedEmail())
                    <div>
                        <p class="text-sm text-gray-800">
                            Your email address is unverified.

                            <flux:button wire:click.prevent="sendVerification" variant="link">
                                Click here to re-send the verification email.
                            </flux:button>
                        </p>
                    </div>
                @endif
            </div>

            <div class="flex gap-4 justify-end items-center">
                <flux:button type="submit" variant="primary">Save</flux:button>
            </div>
        </form>
    </flux:card>

    <flux:card>
        <form wire:submit="updatePassword" class="space-y-6">
            <div>
                <flux:heading size="lg">Update Password</flux:heading>
                <flux:subheading>Ensure your account is using a long, random password to stay secure.
                </flux:subheading>
            </div>

            <div class="space-y-6">
                <flux:input wire:model="current_password" label="Current Password" type="password" required />
                <flux:input wire:model="password" label="New Password" type="password" required />
                <flux:input wire:model="password_confirmation" label="Confirm Password" type="password" required />
            </div>

            <div class="flex gap-4 justify-end items-center">
                <flux:button type="submit" variant="primary">Update Password</flux:button>
            </div>
        </form>
    </flux:card>
    <flux:card>
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Delete Account</flux:heading>
                <flux:subheading>Once your account is deleted, all of its resources and data will be permanently
                    deleted.</flux:subheading>
            </div>

            <flux:modal.trigger name="delete-profile">
                <flux:button variant="danger" class="mt-4">Delete Account</flux:button>
            </flux:modal.trigger>

            <flux:modal name="delete-profile" class="min-w-[22rem] space-y-6">
                <form wire:submit="deleteUser">
                    <div>
                        <flux:heading size="lg">Are you sure you want to delete your account?</flux:heading>

                        <flux:subheading>
                            Once your account is deleted, all of its resources and data will be permanently
                            deleted.
                        </flux:subheading>
                    </div>

                    <div class="mt-6">
                        <flux:input wire:model="delete_password" label="Password" type="password"
                                    placeholder="Password" required />
                    </div>

                    <div class="flex gap-2 mt-6">
                        <flux:spacer />

                        <flux:modal.close>
                            <flux:button variant="ghost">Cancel</flux:button>
                        </flux:modal.close>

                        <flux:button type="submit" variant="danger">Delete Account</flux:button>
                    </div>
                </form>
            </flux:modal>
        </div>
    </flux:card>
</div>

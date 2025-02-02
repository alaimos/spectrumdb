<flux:card>
    <form wire:submit='confirm' class="space-y-6">
        <div>
            <flux:heading size="lg">Confirm your password</flux:heading>
            <flux:subheading>This is a secure area of the application. Please confirm your password before continuing.
            </flux:subheading>
        </div>

        <div class="space-y-6">
            <flux:input wire:model='password' label="Password" type="password" placeholder="Your password" required
                        autocomplete="current-password"/>
        </div>

        <div class="space-y-2">
            <flux:button variant="primary" class="w-full" type="submit">Confirm</flux:button>
        </div>
    </form>
</flux:card>

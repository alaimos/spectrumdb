@props(['analysisId', 'batchStatus'])
@use(App\Enums\BatchStatus)
@if (isset($analysisId) && $batchStatus)
    <flux:separator class="my-4"/>
    @if ($batchStatus === BatchStatus::PENDING)
        <flux:callout icon="clock" variant="warning" inline>
            <flux:callout.heading>
                {{ __('Your analysis is still pending. Please wait...') }}
            </flux:callout.heading>
        </flux:callout>
    @elseif ($batchStatus === BatchStatus::FAILED)
        <flux:callout icon="x-circle" variant="danger" inline>
            <flux:callout.heading>
                {{ __('Your analysis has failed. Please contact the administrator providing the URL of this page.') }}
            </flux:callout.heading>
        </flux:callout>
    @elseif ($batchStatus === BatchStatus::CANCELLED)
        <flux:callout icon="information-circle" color="blue" inline>
            <flux:callout.heading>
                {{ __('Your analysis has been cancelled.') }}</flux:callout.heading>
        </flux:callout>
    @elseif ($batchStatus === BatchStatus::FINISHED)
        {{ $slot }}
    @endif
@endif

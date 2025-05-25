@props(['analysisId', 'batchStatus'])
@use(App\Enums\BatchStatus)
@if (isset($analysisId) && $batchStatus)
    <flux:separator class="my-4"/>
    @if ($batchStatus === BatchStatus::PENDING)
        <flux:callout icon="clock" variant="warning" inline>
            <flux:callout.heading>Your analysis is still pending. Please wait...</flux:callout.heading>
        </flux:callout>
    @elseif ($batchStatus === BatchStatus::FAILED)
        <flux:callout icon="x-circle" variant="danger" inline>
            <flux:callout.heading>Your analysis has failed. Please check the logs for more details.
            </flux:callout.heading>
        </flux:callout>
    @elseif ($batchStatus === BatchStatus::CANCELLED)
        <flux:callout icon="information-circle" color="blue" inline>
            <flux:callout.heading>Your analysis has been cancelled.</flux:callout.heading>
        </flux:callout>
    @elseif ($batchStatus === BatchStatus::FINISHED)
        {{ $slot }}
    @endif
@endif

<?php

declare(strict_types=1);

namespace App\Livewire\Pages\Datasets;

use App\Models\Dataset;
use App\Models\DatasetMetadata;
use Exception;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Validate;
use Livewire\Component;

final class Edit extends Component
{
    public Dataset $dataset;

    #[Validate('required|min:3|max:255')]
    public string $name = '';

    #[Validate('required|min:3|max:1000')]
    public string $description = '';

    /** @var array<int, array{key: string, value: string}> */
    #[Validate('array')]
    public array $datasetMetadata = [];

    public bool $showMetadata = false;

    public function mount(Dataset $dataset): void
    {
        $this->dataset = $dataset;
        $this->name = $dataset->name;
        $this->description = $dataset->description;

        // Load existing metadata
        $this->datasetMetadata = $dataset->metadata()
            ->whereNot('key', 'like', 'original_%_filename')
            ->get()
            ->map(fn (DatasetMetadata $meta) => [
                'key' => $meta->key,
                'value' => $meta->value,
            ])
            ->toArray();
    }

    public function addDatasetMetadata(): void
    {
        $this->datasetMetadata[] = ['key' => '', 'value' => ''];
    }

    public function removeDatasetMetadata(int $index): void
    {
        unset($this->datasetMetadata[$index]);
        $this->datasetMetadata = array_values($this->datasetMetadata);
    }

    public function validateMetadataKey(string $key): bool
    {
        if (preg_match('/^original_.+_filename$/', $key)) {
            return false;
        }

        return true;
    }

    public function toggleMetadata(): void
    {
        $this->showMetadata = ! $this->showMetadata;
    }

    public function save(): void
    {
        $this->validate();

        try {
            $this->dataset->update([
                'name' => $this->name,
                'description' => $this->description,
            ]);

            // Delete existing metadata (except file names)
            $this->dataset->metadata()
                ->whereNot('key', 'like', 'original_%_filename')
                ->delete();

            // Create new metadata
            foreach ($this->datasetMetadata as $metadata) {
                if (! empty($metadata['key']) && ! empty($metadata['value'])) {
                    DatasetMetadata::create([
                        'dataset_id' => $this->dataset->id,
                        'key' => $metadata['key'],
                        'value' => $metadata['value'],
                    ]);
                }
            }

            Flux::toast(
                text: 'Dataset updated successfully.',
                heading: 'Success',
                variant: 'success'
            );

            $this->redirect(route('datasets.index'), navigate: true);
        } catch (Exception $e) {
            Flux::toast(
                text: 'An error occurred while updating the dataset.',
                heading: 'Error',
                variant: 'danger'
            );

            throw $e;
        }
    }

    public function render(): View
    {
        return view('livewire.pages.datasets.edit');
    }

    protected function rules(): array
    {
        return [
            'name' => 'required|min:3|max:255',
            'description' => 'required|min:3|max:1000',
            'datasetMetadata.*.key' => [
                'nullable',
                'string',
                'max:255',
                'not_regex:/^original_.+_filename$/',
            ],
            'datasetMetadata.*.value' => 'nullable',
        ];
    }

    protected function messages(): array
    {
        return [
            'datasetMetadata.*.key.not_regex' => 'The metadata key is not valid.',
        ];
    }
}

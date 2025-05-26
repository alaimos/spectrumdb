<?php

declare(strict_types=1);

namespace App\Livewire\Pages\Datasets\Explore;

use App\Enums\PicrustTables;
use App\Models\Dataset;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Validate;
use Livewire\Component;

final class PicrustTable extends Component
{
    #[Locked]
    public Dataset $dataset;

    #[Validate]
    public PicrustTables $selectedTable;

    public function downloadTable()
    {
        if (! isset($this->selectedTable)) {
            return;
        }

        $tableFile = $this->dataset->getPicrustTableFile($this->selectedTable);

        if (is_null($tableFile)) {
            return;
        }

        return Storage::download($tableFile);
    }

    protected function rules(): array
    {
        return [
            'selectedTable' => ['required', Rule::enum(PicrustTables::class)],
        ];
    }
}

<?php

declare(strict_types=1);

namespace App\Livewire\Pages\Datasets\Explore;

use App\Models\Dataset;
use Livewire\Attributes\Locked;
use Livewire\Component;

final class Index extends Component
{
    #[Locked]
    public Dataset $dataset;
}

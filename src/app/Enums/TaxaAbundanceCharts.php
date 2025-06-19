<?php

declare(strict_types=1);

namespace App\Enums;

use App\Traits\WithGetValues;

enum TaxaAbundanceCharts: string
{
    /** @use WithGetValues<string> */
    use WithGetValues;

    case STACKED = 'stacked_abundance_barplot';
    case PIE = 'abundance_pie_plot';

    public function getName(): string
    {
        return match ($this) {
            self::STACKED => __('Stacked Barplot'),
            self::PIE => __('Pie Plot'),
        };
    }

    public function analysisType(): Analysis
    {
        return match ($this) {
            self::STACKED => Analysis::STACKED_ABUNDANCE_BARPLOT,
            self::PIE => Analysis::ABUNDANCE_PIE_PLOT,
        };
    }
}

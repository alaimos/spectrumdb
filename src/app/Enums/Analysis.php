<?php

declare(strict_types=1);

namespace App\Enums;

enum Analysis: string
{
    case ALPHA_DIVERSITY = 'alpha_diversity';
    case BETA_DIVERSITY = 'beta_diversity';
    case DIFFERENTIAL_ABUNDANCE = 'differential_abundance';
    case FUNCTIONAL_ANALYSIS = 'picrust_functional';
    case TOP_FREQ_PLOT = 'top_freq_plot';
    case TOP_SIGN_PLOT = 'top_sign_plot';
    case TOP_FC_PLOT = 'top_fc_plot';
    case STACKED_ABUNDANCE_BARPLOT = 'stacked_abundance_barplot';
    case ABUNDANCE_PIE_PLOT = 'abundance_pie_plot';
    case CORRELATION_NETWORK = 'correlation_network';
}

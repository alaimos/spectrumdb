@use(App\Enums\AlphaDiversityMetrics)
@use(App\Enums\BetaDiversityMetrics)
<section class="w-full">
    <x-explore-heading :dataset="$dataset"/>

    <x-explore.layout
        heading="Download dataset"
        subheading="Here you can download the raw data of the dataset."
        :dataset="$dataset">
        <flux:card>
            <form wire:submit="downloadTable">
                <flux:heading>{{ __('Basic data') }}</flux:heading>
                <div class="space-y-6 ml-4 my-4 grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="flex flex-col gap-1">
                        <label class="block text-sm">{{ __('Metadata') }}</label>
                        <flux:button variant="primary"
                                     :href="route('datasets.download.asset', ['dataset' => $dataset, 'assetName' => 'metadata'])">
                            {{ __('Download') }}
                        </flux:button>
                    </div>
                    <div class="flex flex-col gap-1">
                        <label class="block text-sm">{{ __('Taxonomy') }}</label>
                        <flux:button variant="primary"
                                     :href="route('datasets.download.asset', ['dataset' => $dataset, 'assetName' => 'taxonomy'])">
                            {{ __('Download') }}
                        </flux:button>
                    </div>
                    <div class="flex flex-col gap-1">
                        <label class="block text-sm">{{ __('ASV Table') }}</label>
                        <flux:button variant="primary"
                                     :href="route('datasets.download.asset', ['dataset' => $dataset, 'assetName' => 'asvTable'])">
                            {{ __('Download') }}
                        </flux:button>
                    </div>
                </div>
                @if ($dataset->hasAlphaDiversity())
                    <flux:heading>{{ __('Alpha Diversity') }}</flux:heading>
                    <div class="space-y-6 ml-4 my-4 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                        @if ($dataset->getAlphaDiversityFile(AlphaDiversityMetrics::SHANNON) !== null)
                            <div class="flex flex-col gap-1">
                                <label class="block text-sm">
                                    {{ __('Shannon Diversity Index') }}
                                </label>
                                <flux:button variant="primary"
                                             :href="route('datasets.download.asset', ['dataset' => $dataset, 'assetName' => 'alphaDiversity.shannon'])">
                                    {{ __('Download') }}
                                </flux:button>
                            </div>
                        @endif
                        @if ($dataset->getAlphaDiversityFile(AlphaDiversityMetrics::FAITH) !== null)
                            <div class="flex flex-col gap-1">
                                <label class="block text-sm">
                                    {{ __('Faith\'s Phylogenetic Diversity Index') }}
                                </label>
                                <flux:button variant="primary"
                                             :href="route('datasets.download.asset', ['dataset' => $dataset, 'assetName' => 'alphaDiversity.faith'])">
                                    {{ __('Download') }}
                                </flux:button>
                            </div>
                        @endif
                        @if ($dataset->getAlphaDiversityFile(AlphaDiversityMetrics::CHAO) !== null)
                            <div class="flex flex-col gap-1">
                                <label class="block text-sm">
                                    {{ __('Chao1 Diversity Index') }}
                                </label>
                                <flux:button variant="primary"
                                             :href="route('datasets.download.asset', ['dataset' => $dataset, 'assetName' => 'alphaDiversity.chao'])">
                                    {{ __('Download') }}
                                </flux:button>
                            </div>
                        @endif
                        @if ($dataset->getAlphaDiversityFile(AlphaDiversityMetrics::EVENNESS) !== null)
                            <div class="flex flex-col gap-1">
                                <label class="block text-sm">
                                    {{ __('Evenness Index') }}
                                </label>
                                <flux:button variant="primary"
                                             :href="route('datasets.download.asset', ['dataset' => $dataset, 'assetName' => 'alphaDiversity.evenness'])">
                                    {{ __('Download') }}
                                </flux:button>
                            </div>
                        @endif
                    </div>
                @endif
                @if ($dataset->hasBetaDiversity())
                    <flux:heading>{{ __('Beta Diversity') }}</flux:heading>
                    <div class="space-y-6 ml-4 my-4 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                        @if ($dataset->getBetaDiversityFile(BetaDiversityMetrics::BRAY_CURTIS) !== null)
                            <div class="flex flex-col gap-1">
                                <label class="block text-sm">
                                    {{ __('Bray-Curtis Distance') }}
                                </label>
                                <flux:button variant="primary"
                                             :href="route('datasets.download.asset', ['dataset' => $dataset, 'assetName' => 'betaDiversity.brayCurtis'])">
                                    {{ __('Download') }}
                                </flux:button>
                            </div>
                        @endif
                        @if ($dataset->getBetaDiversityFile(BetaDiversityMetrics::JACCARD) !== null)
                            <div class="flex flex-col gap-1">
                                <label class="block text-sm">
                                    {{ __('Jaccard Distance') }}
                                </label>
                                <flux:button variant="primary"
                                             :href="route('datasets.download.asset', ['dataset' => $dataset, 'assetName' => 'betaDiversity.jaccard'])">
                                    {{ __('Download') }}
                                </flux:button>
                            </div>
                        @endif
                        @if ($dataset->getBetaDiversityFile(BetaDiversityMetrics::WEIGHTED_UNIFRAC) !== null)
                            <div class="flex flex-col gap-1">
                                <label class="block text-sm">
                                    {{ __('Weighted UniFrac Distance') }}
                                </label>
                                <flux:button variant="primary"
                                             :href="route('datasets.download.asset', ['dataset' => $dataset, 'assetName' => 'betaDiversity.weightedUnifrac'])">
                                    {{ __('Download') }}
                                </flux:button>
                            </div>
                        @endif
                        @if ($dataset->getBetaDiversityFile(BetaDiversityMetrics::UNWEIGHTED_UNIFRAC) !== null)
                            <div class="flex flex-col gap-1">
                                <label class="block text-sm">
                                    {{ __('Unweighted UniFrac Distance') }}
                                </label>
                                <flux:button variant="primary"
                                             :href="route('datasets.download.asset', ['dataset' => $dataset, 'assetName' => 'betaDiversity.unweightedUnifrac'])">
                                    {{ __('Download') }}
                                </flux:button>
                            </div>
                        @endif
                    </div>
                @endif
            </form>
        </flux:card>

    </x-explore.layout>
</section>



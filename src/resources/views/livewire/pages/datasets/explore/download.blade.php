@use(App\Enums\AlphaDiversityMetrics)
@use(App\Enums\BetaDiversityMetrics)
<section class="w-full">
    <x-page-heading title="Explore dataset {{ $dataset->name }}"
                    subtitle="Explore the dataset {{ $dataset->name }} in detail."/>

    <x-explore.layout
        heading="Download dataset"
        subheading="Here you can download the raw data of the dataset {{ $dataset->name }}."
        :dataset="$dataset">
        <flux:card>
            <form wire:submit="downloadTable">
                <flux:heading>Basic data</flux:heading>
                <div class="space-y-6 ml-4 my-4 grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="flex flex-col gap-1">
                        <label class="block text-sm">Metadata</label>
                        <flux:button variant="primary"
                                     :href="route('datasets.download.asset', ['dataset' => $dataset, 'assetName' => 'metadata'])">
                            Download
                        </flux:button>
                    </div>
                    <div class="flex flex-col gap-1">
                        <label class="block text-sm">Taxonomy</label>
                        <flux:button variant="primary"
                                     :href="route('datasets.download.asset', ['dataset' => $dataset, 'assetName' => 'taxonomy'])">
                            Download
                        </flux:button>
                    </div>
                    <div class="flex flex-col gap-1">
                        <label class="block text-sm">ASV Table</label>
                        <flux:button variant="primary"
                                     :href="route('datasets.download.asset', ['dataset' => $dataset, 'assetName' => 'asvTable'])">
                            Download
                        </flux:button>
                    </div>
                </div>
                @if ($dataset->hasAlphaDiversity())
                    <flux:heading>Alpha Diversity</flux:heading>
                    <div class="space-y-6 ml-4 my-4 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                        @if ($dataset->getAlphaDiversityFile(AlphaDiversityMetrics::SHANNON) !== null)
                            <div class="flex flex-col gap-1">
                                <label class="block text-sm">
                                    Shannon Diversity Index
                                </label>
                                <flux:button variant="primary"
                                             :href="route('datasets.download.asset', ['dataset' => $dataset, 'assetName' => 'alphaDiversity.shannon'])">
                                    Download
                                </flux:button>
                            </div>
                        @endif
                        @if ($dataset->getAlphaDiversityFile(AlphaDiversityMetrics::FAITH) !== null)
                            <div class="flex flex-col gap-1">
                                <label class="block text-sm">
                                    Faith's Phylogenetic Diversity Index
                                </label>
                                <flux:button variant="primary"
                                             :href="route('datasets.download.asset', ['dataset' => $dataset, 'assetName' => 'alphaDiversity.faith'])">
                                    Download
                                </flux:button>
                            </div>
                        @endif
                        @if ($dataset->getAlphaDiversityFile(AlphaDiversityMetrics::CHAO) !== null)
                            <div class="flex flex-col gap-1">
                                <label class="block text-sm">
                                    Chao1 Diversity Index
                                </label>
                                <flux:button variant="primary"
                                             :href="route('datasets.download.asset', ['dataset' => $dataset, 'assetName' => 'alphaDiversity.chao'])">
                                    Download
                                </flux:button>
                            </div>
                        @endif
                        @if ($dataset->getAlphaDiversityFile(AlphaDiversityMetrics::EVENNESS) !== null)
                            <div class="flex flex-col gap-1">
                                <label class="block text-sm">
                                    Evenness Index
                                </label>
                                <flux:button variant="primary"
                                             :href="route('datasets.download.asset', ['dataset' => $dataset, 'assetName' => 'alphaDiversity.evenness'])">
                                    Download
                                </flux:button>
                            </div>
                        @endif
                    </div>
                @endif
                @if ($dataset->hasBetaDiversity())
                    <flux:heading>Beta Diversity</flux:heading>
                    <div class="space-y-6 ml-4 my-4 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                        @if ($dataset->getBetaDiversityFile(BetaDiversityMetrics::BRAY_CURTIS) !== null)
                            <div class="flex flex-col gap-1">
                                <label class="block text-sm">
                                    Bray-Curtis Distance
                                </label>
                                <flux:button variant="primary"
                                             :href="route('datasets.download.asset', ['dataset' => $dataset, 'assetName' => 'betaDiversity.brayCurtis'])">
                                    Download
                                </flux:button>
                            </div>
                        @endif
                        @if ($dataset->getBetaDiversityFile(BetaDiversityMetrics::JACCARD) !== null)
                            <div class="flex flex-col gap-1">
                                <label class="block text-sm">
                                    Jaccard Distance
                                </label>
                                <flux:button variant="primary"
                                             :href="route('datasets.download.asset', ['dataset' => $dataset, 'assetName' => 'betaDiversity.jaccard'])">
                                    Download
                                </flux:button>
                            </div>
                        @endif
                        @if ($dataset->getBetaDiversityFile(BetaDiversityMetrics::WEIGHTED_UNIFRAC) !== null)
                            <div class="flex flex-col gap-1">
                                <label class="block text-sm">
                                    Weighted UniFrac Distance
                                </label>
                                <flux:button variant="primary"
                                             :href="route('datasets.download.asset', ['dataset' => $dataset, 'assetName' => 'betaDiversity.weightedUnifrac'])">
                                    Download
                                </flux:button>
                            </div>
                        @endif
                        @if ($dataset->getBetaDiversityFile(BetaDiversityMetrics::UNWEIGHTED_UNIFRAC) !== null)
                            <div class="flex flex-col gap-1">
                                <label class="block text-sm">
                                    Unweighted UniFrac Distance
                                </label>
                                <flux:button variant="primary"
                                             :href="route('datasets.download.asset', ['dataset' => $dataset, 'assetName' => 'betaDiversity.unweightedUnifrac'])">
                                    Download
                                </flux:button>
                            </div>
                        @endif
                    </div>
                @endif
            </form>
        </flux:card>

    </x-explore.layout>
</section>



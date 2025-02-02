<?php

namespace Database\Seeders;

use App\Models\Taxon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class TaxonSeeder extends Seeder
{
    public function run(): void
    {
        // Generate 100 complete taxa (species level)
        $speciesLevel = Taxon::factory()
            ->count(100)
            ->create();

        foreach ($speciesLevel as $species) {
            // Create genus level (parent of species)
            $genusLevel = Taxon::create([
                'feature_id' => Str::lower(Str::random(32)),
                'kingdom' => $species->kingdom,
                'phylum' => $species->phylum,
                'class' => $species->class,
                'order' => $species->order,
                'family' => $species->family,
                'genus' => $species->genus,
                'species' => null,
            ]);

            // Update species parent
            $species->update(['parent_taxa_id' => $genusLevel->id]);

            // Create family level (parent of genus)
            $familyLevel = Taxon::create([
                'feature_id' => Str::lower(Str::random(32)),
                'kingdom' => $species->kingdom,
                'phylum' => $species->phylum,
                'class' => $species->class,
                'order' => $species->order,
                'family' => $species->family,
                'genus' => null,
                'species' => null,
            ]);

            // Update genus parent
            $genusLevel->update(['parent_taxa_id' => $familyLevel->id]);

            // Create order level (parent of family)
            $orderLevel = Taxon::create([
                'feature_id' => Str::lower(Str::random(32)),
                'kingdom' => $species->kingdom,
                'phylum' => $species->phylum,
                'class' => $species->class,
                'order' => $species->order,
                'family' => null,
                'genus' => null,
                'species' => null,
            ]);

            // Update family parent
            $familyLevel->update(['parent_taxa_id' => $orderLevel->id]);

            // Create class level (parent of order)
            $classLevel = Taxon::create([
                'feature_id' => Str::lower(Str::random(32)),
                'kingdom' => $species->kingdom,
                'phylum' => $species->phylum,
                'class' => $species->class,
                'order' => null,
                'family' => null,
                'genus' => null,
                'species' => null,
            ]);

            // Update order parent
            $orderLevel->update(['parent_taxa_id' => $classLevel->id]);

            // Create phylum level (parent of class)
            $phylumLevel = Taxon::create([
                'feature_id' => Str::lower(Str::random(32)),
                'kingdom' => $species->kingdom,
                'phylum' => $species->phylum,
                'class' => null,
                'order' => null,
                'family' => null,
                'genus' => null,
                'species' => null,
            ]);

            // Update class parent
            $classLevel->update(['parent_taxa_id' => $phylumLevel->id]);

            // Create kingdom level (parent of phylum)
            $kingdomLevel = Taxon::create([
                'feature_id' => Str::lower(Str::random(32)),
                'kingdom' => $species->kingdom,
                'phylum' => null,
                'class' => null,
                'order' => null,
                'family' => null,
                'genus' => null,
                'species' => null,
            ]);

            // Update phylum parent
            $phylumLevel->update(['parent_taxa_id' => $kingdomLevel->id]);
        }
    }
}

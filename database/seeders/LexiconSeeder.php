<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class LexiconSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        $lexiconId = Str::uuid();
        DB::table('lexicons')->insert([
            'id' => $lexiconId,
            'name' => 'Child Safety Monitoring',
            'remark' => 'Keywords for identifying content related to child protection and safety concerns - FOR LEGITIMATE MODERATION PURPOSES ONLY',
            'status' => 'enabled',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $keywords = [
            'child exploitation',
            'online grooming',
            'predatory behavior',
            'inappropriate contact',
            'age inappropriate',
            'unsafe situation',
            'risk to children',
            'vulnerable minor',
            'protection needed',
            'safety concern',
            'child helpline',
            'report abuse',
            'cyber tipline',
            'safety hotline',
            'emergency contact',
            'child welfare',
            'guardianship issues',
            'custody problems',
            'neglect signs',
            'abuse indicators',
        ];

        $lexiconKeywords = [];

        foreach ($keywords as $keyword) {
            $lexiconKeywords[] = [
                'id' => Str::uuid(),
                'lexicon_id' => $lexiconId,
                'keywords' => $keyword,
                'crawl_hit_count' => 0,
                'case_count' => 0,
                'status' => 'enabled',
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        DB::table('lexicon_keywords')->insert($lexiconKeywords);

        $this->command->info('Created Child Safety lexicon with 20 keywords for moderation purposes.');
    }
}

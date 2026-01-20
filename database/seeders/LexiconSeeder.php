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
            ['child exploitation', 'minor exploitation', 'child abuse'],
            ['online grooming', 'child grooming', 'grooming behavior'],
            ['predatory behavior', 'sexual predator', 'predatory conduct'],
            ['inappropriate contact', 'inappropriate interaction', 'sexual contact'],
            ['age inappropriate', 'underage content', 'minor involved'],
            ['unsafe situation', 'dangerous situation', 'unsafe environment'],
            ['risk to children', 'child endangerment', 'at risk minor'],
            ['vulnerable minor', 'vulnerable child', 'at-risk child'],
            ['protection needed', 'needs protection', 'child protection'],
            ['safety concern', 'child safety issue', 'safeguarding concern'],
            ['child helpline', 'children helpline', 'youth helpline'],
            ['report abuse', 'report child abuse', 'abuse reporting'],
            ['cyber tipline', 'cyber reporting hotline', 'online abuse tipline'],
            ['safety hotline', 'child safety hotline', 'emergency hotline'],
            ['emergency contact', 'emergency number', 'urgent contact'],
            ['child welfare', 'children welfare', 'youth welfare'],
            ['guardianship issues', 'custody dispute', 'legal guardian issue'],
            ['custody problems', 'custody conflict', 'custody dispute'],
            ['neglect signs', 'child neglect', 'neglect indicators'],
            ['abuse indicators', 'signs of abuse', 'abuse warning signs'],
        ];

        $lexiconKeywords = [];

        foreach ($keywords as $group) {
            $lexiconKeywords[] = [
                'id' => Str::uuid(),
                'lexicon_id' => $lexiconId,
                'keywords' => json_encode($group),
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

<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CrawlerConfigSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $lexicons = [
            [
                'id' => Str::uuid(),
                'name' => 'Basic Child Safety Lexicon',
                'remark' => 'Core keywords for child protection surveillance',
                'status' => 'enabled',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => Str::uuid(),
                'name' => 'Social Media Risk Keywords',
                'remark' => 'Keywords commonly found in risky social media content',
                'status' => 'enabled',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        DB::table('lexicons')->insert($lexicons);

        $keywords = [
            [
                'id' => Str::uuid(),
                'lexicon_id' => $lexicons[0]['id'],
                'keywords' => 'underage,nudity,minor abuse',
                'crawl_hit_count' => 1240,
                'case_count' => 412,
                'status' => 'enabled',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => Str::uuid(),
                'lexicon_id' => $lexicons[0]['id'],
                'keywords' => 'child exploitation,explicit image',
                'crawl_hit_count' => 860,
                'case_count' => 290,
                'status' => 'enabled',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => Str::uuid(),
                'lexicon_id' => $lexicons[1]['id'],
                'keywords' => '诱拐,裸露,交易',
                'crawl_hit_count' => 510,
                'case_count' => 170,
                'status' => 'enabled',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => Str::uuid(),
                'lexicon_id' => $lexicons[1]['id'],
                'keywords' => '未成年,成人视频',
                'crawl_hit_count' => 380,
                'case_count' => 130,
                'status' => 'enabled',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        DB::table('lexicon_keywords')->insert($keywords);
        DB::table('crawler_configs')->insert([
            [
                'id' => Str::uuid(),
                'name' => 'Google Search Surveillance',
                'sources' => 'google.com',
                'lexicon_id' => $lexicons[0]['id'],
                'description' => 'Daily Google crawling using child safety lexicon',
                'frequency_code' => 'daily',
                'status' => 'enabled',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => Str::uuid(),
                'name' => 'Twitter / X Image Monitoring',
                'sources' => 'twitter.com,x.com',
                'lexicon_id' => $lexicons[1]['id'],
                'description' => 'Monitoring public images on social platforms',
                'frequency_code' => 'daily',
                'status' => 'enabled',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => Str::uuid(),
                'name' => 'Video Platform Weekly Scan',
                'sources' => 'youtube.com,tiktok.com',
                'lexicon_id' => $lexicons[0]['id'],
                'description' => 'Weekly scan for risky video content',
                'frequency_code' => 'weekly',
                'status' => 'disabled',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}

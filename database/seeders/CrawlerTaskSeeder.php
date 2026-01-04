<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CrawlerTaskSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $configs = DB::table('crawler_configs')->get();

        foreach ($configs as $config) {
            DB::table('crawler_tasks')->insert([
                'id' => Str::uuid(),
                'crawler_config_id' => $config->id,
                'lexicon_id' => $config->lexicon_id,
                'status' => 'completed',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}

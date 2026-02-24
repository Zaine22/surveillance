<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class BotSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
         $bots = [];

        $healthStatuses = ['busy', 'stable', 'slightly_busy', 'normal'];
        $types = ['crawler', 'sync'];

        for ($i = 1; $i <= 20; $i++) {

            $bots[] = [
                'id' => (string) Str::uuid(),
                'name' => 'bot-' . $i,
                'type' => $types[array_rand($types)],
                'version' => '1.0.' . rand(0, 5),
                'description' => 'Automated bot machine #' . $i,
                'health_checked_at' => Carbon::now(),
                'content' => json_encode([
                    'cpu_usage' => rand(20, 95) . '%',
                    'memory_usage' => rand(30, 90) . '%',
                    'region' => 'region-' . rand(1, 3),
                ]),
                'health_status' => $healthStatuses[array_rand($healthStatuses)],
                'status' => 'enabled',
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        DB::table('bot_machines')->insert($bots);
    }
}

<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

class ExternalCaseManagementSeeder extends Seeder
{
    public function run(): void
    {
        $caseStatuses = [
            'pending',
            'created',
            'notified',
            'moved_offline',
            'auto_offline',
        ];

        $itemStatuses = ['valid', 'invalid'];
        $aiResults = ['normal', 'abnormal'];

        $reasons = [
            'Spam content',
            'Fake news',
            'Sensitive content',
            'Violence',
            'Scam detected',
        ];

        $keywordsPool = [
            ['Lion', 'Animal'],
            ['Forest', 'Wildlife'],
            ['Chat', 'Social'],
            ['AI', 'Robot'],
        ];

        // 👉 create 30 cases across 2024-2026
        for ($i = 1; $i <= 30; $i++) {

            $year = [2024, 2025, 2026][array_rand([2024, 2025, 2026])];

            $createdAt = Carbon::create(
                $year,
                rand(1, 12),
                rand(1, 28)
            );

            $caseId = (string) Str::uuid();

            // ✅ CASE MANAGEMENT (EXTERNAL → no ai_predict_result_id)
            DB::table('case_management')->insert([
                'id'                     => $caseId,
                'ai_predict_result_id'  => null, // external case
                'internal_case_no'      => null,
                'external_case_no'      => 'EXT-' . strtoupper(Str::random(6)),
                'keywords'              => json_encode(
                    $keywordsPool[array_rand($keywordsPool)]
                ),
                'status'                => $caseStatuses[array_rand($caseStatuses)],
                'comment'               => 'External manually created case',
                'created_at'            => $createdAt,
                'updated_at'            => $createdAt,
            ]);

            // 👉 create 2–5 items per case
            $itemCount = rand(2, 5);

            for ($j = 1; $j <= $itemCount; $j++) {

                $itemDate = (clone $createdAt)->addDays(rand(0, 10));

                DB::table('case_management_items')->insert([
                    'id'                  => (string) Str::uuid(),
                    'case_management_id'  => $caseId,

                    // ✅ your requirement
                    'media_url'           => "https://picsum.photos/300/200",

                    'crawler_page_url'    => "https://example.com/page/" . rand(1, 100),

                    // external = manual
                    'ai_result'           => $aiResults[array_rand($aiResults)],

                    'status'              => $itemStatuses[array_rand($itemStatuses)],

                    'reason'              => $reasons[array_rand($reasons)],
                    'other_reason'        => rand(0, 1) ? 'Manual review note' : null,

                    'ai_score'            => rand(50, 99) + rand(0, 99)/100,

                    'keywords'            => json_encode(
                        $keywordsPool[array_rand($keywordsPool)]
                    ),

                    'issue_date'          => $itemDate->toDateString(),
                    'due_date'            => (clone $itemDate)->addDays(rand(3, 10))->toDateString(),

                    'created_at'          => $itemDate,
                    'updated_at'          => $itemDate,
                ]);
            }
        }
    }
}

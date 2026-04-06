<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SystemNoticeSeeder extends Seeder
{
    public function run(): void
    {
        $userId = '019d03d2-4aae-70b7-906d-a2fe05cbe4c9';

        $data = [

            ['published', '2026-01-05 09:00:00', '2026-01-05 11:00:00', 'Crawler Maintenance',
                "Scheduled maintenance for crawler services.

Impact:
- Crawling tasks may be delayed
- New tasks may be queued

System Operations Team"],

            ['published', '2026-01-12 10:00:00', '2026-01-12 12:00:00', 'Crawler Performance Upgrade',
                "Performance improvements for crawler engines.

Impact:
- Temporary slowdown in task processing

System Operations Team"],

            ['published', '2026-01-18 08:00:00', null, 'Crawler Source Update',
                "New external sources have been added to the crawler system.

Benefits:
- Broader coverage
- Improved detection accuracy"],

            ['pending', '2026-02-01 14:00:00', '2026-02-01 16:00:00', 'Crawler Network Adjustment',
                "Network optimization for crawler nodes.

Impact:
- Temporary connection instability"],

            ['pending', '2026-02-08 09:00:00', '2026-02-08 10:00:00', 'Crawler Queue Optimization',
                "Queue handling improvements for better task distribution."],

            // 🔹 AI
            ['published', '2026-02-15 10:00:00', '2026-02-15 12:00:00', 'AI Model Upgrade',
                "AI models have been upgraded to improve prediction accuracy.

Impact:
- Temporary processing delay"],

            ['published', '2026-02-20 13:00:00', null, 'AI Accuracy Improvement',
                "AI analysis logic has been enhanced for better abnormal detection."],

            ['pending', '2026-03-01 09:00:00', '2026-03-01 11:00:00', 'AI Service Maintenance',
                "AI service maintenance scheduled.

Impact:
- AI predictions may be temporarily unavailable"],

            ['pending', '2026-03-05 14:00:00', null, 'AI Model Expansion',
                "Additional AI models will be deployed to support new use cases."],

            ['pending', '2026-03-10 08:00:00', '2026-03-10 10:00:00', 'AI Processing Optimization',
                "Optimization of AI processing pipelines for faster response time."],

            // 🔹 Case Management
            ['published', '2026-03-15 10:00:00', '2026-03-15 12:00:00', 'Case Management Update',
                "Improvements to case handling workflows.

Enhancements:
- Faster review process
- Improved status tracking"],

            ['published', '2026-03-18 09:00:00', null, 'Case Review Enhancement',
                "New validation rules added to improve review accuracy."],

            ['pending', '2026-03-25 13:00:00', '2026-03-25 15:00:00', 'Case Workflow Maintenance',
                "Maintenance on case management services.

Impact:
- Case updates may be delayed"],

            ['pending', '2026-04-01 11:00:00', null, 'Case Notification Update',
                "Improved notification system for case updates."],

            ['pending', '2026-04-05 14:00:00', '2026-04-05 16:00:00', 'Case Sync Optimization',
                "Synchronization improvements between AI and case management."],

            // 🔹 External Case / Integration
            ['published', '2026-04-10 09:00:00', '2026-04-10 11:00:00', 'External Case Sync Maintenance',
                "Maintenance on external case synchronization.

Impact:
- Delayed external case updates"],

            ['published', '2026-04-12 08:00:00', null, 'External API Integration Update',
                "Updated integration with external data providers."],

            ['pending', '2026-04-20 10:00:00', '2026-04-20 12:00:00', 'External Data Source Upgrade',
                "Upgrading external data sources for better reliability."],

            ['pending', '2026-04-25 13:00:00', null, 'External Case Processing Enhancement',
                "Enhanced processing for external case ingestion."],

            ['pending', '2026-04-30 08:00:00', null, 'External System Stability Improvement',
                "Improving stability of external system connections."],
        ];

        $records = [];

        foreach ($data as $item) {
            $records[] = [
                'id'           => Str::uuid(),
                'status'       => $item[0],
                'publish_date' => $item[1],
                'expire_at'    => $item[2],
                'title'        => $item[3],
                'content'      => $item[4],
                'created_by'   => $userId,
                'created_at'   => now(),
                'updated_at'   => now(),
            ];
        }

        DB::table('system_notices')->insert($records);
    }
}

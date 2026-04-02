<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SystemNoticeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $userId = '019cd6b7-38f4-71a1-913b-d5a0c8c15ab3';

        $data = [
            ['published', '2024-01-10 09:00:00', '2024-01-10 12:00:00', 'System Maintenance', 'Scheduled maintenance.'],
            ['published', '2024-02-15 10:00:00', '2024-02-15 12:00:00', 'Service Upgrade', 'System upgrade in progress.'],
            ['published', '2024-03-20 08:00:00', '2024-03-20 10:00:00', 'Temporary Downtime', 'Temporary downtime expected.'],
            ['published', '2024-04-05 13:00:00', '2024-04-05 15:00:00', 'Network Maintenance', 'Network updates ongoing.'],
            ['published', '2024-05-01 09:00:00', null, 'Feature Release', 'New features deployed.'],

            ['published', '2024-06-18 11:00:00', '2024-06-18 13:00:00', 'Database Optimization', 'Database performance improvements.'],
            ['published', '2024-07-22 14:00:00', '2024-07-22 16:00:00', 'Security Update', 'Security patches applied.'],
            ['published', '2024-08-30 08:00:00', null, 'System Enhancement', 'Enhanced system stability.'],
            ['published', '2024-09-15 09:00:00', '2024-09-15 11:00:00', 'API Maintenance', 'API services maintenance.'],
            ['published', '2024-10-10 10:00:00', '2024-10-10 12:00:00', 'Performance Tuning', 'Improved response speed.'],

            ['published', '2025-01-05 09:00:00', '2025-01-05 11:00:00', 'New Year Maintenance', 'Routine maintenance.'],
            ['published', '2025-02-10 00:00:00', '2025-02-10 04:00:00', 'System Maintenance', 'Full system maintenance.'],
            ['published', '2025-03-18 10:00:00', '2025-03-18 12:00:00', 'Infrastructure Update', 'Backend updates applied.'],
            ['published', '2025-04-22 13:00:00', '2025-04-22 15:00:00', 'Cache Refresh', 'Cache optimization.'],
            ['published', '2025-05-30 08:00:00', null, 'Feature Upgrade', 'New module added.'],

            ['pending', '2025-06-15 10:00:00', '2025-06-15 12:00:00', 'Scheduled Maintenance', 'Upcoming maintenance.'],
            ['pending', '2025-07-20 14:00:00', '2025-07-20 16:00:00', 'System Update', 'Upcoming system update.'],
            ['pending', '2025-08-25 09:00:00', null, 'Platform Enhancement', 'Enhancements coming soon.'],
            ['pending', '2025-09-10 11:00:00', '2025-09-10 13:00:00', 'API Upgrade', 'API improvements planned.'],
            ['pending', '2025-10-05 08:00:00', '2025-10-05 10:00:00', 'Security Upgrade', 'Security update planned.'],

            ['pending', '2026-01-12 09:00:00', '2026-01-12 11:00:00', 'System Maintenance', 'Routine maintenance scheduled.'],
            ['pending', '2026-02-18 10:00:00', '2026-02-18 12:00:00', 'Infrastructure Upgrade', 'System upgrade scheduled.'],
            ['pending', '2026-03-25 13:00:00', '2026-03-25 15:00:00', 'Database Migration', 'Migration planned.'],
            ['pending', '2026-04-30 08:00:00', null, 'New Features Coming', 'Exciting features ahead.'],
            ['pending', '2026-05-20 14:00:00', '2026-05-20 16:00:00', 'System Optimization', 'Performance improvements.'],
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

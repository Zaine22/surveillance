<?php
namespace Database\Seeders;

use App\Models\CaseManagement;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CaseManagementSeeder extends Seeder
{
    public function run(): void
    {
        $results = DB::table('ai_predict_results as apr')
            ->leftJoin('case_management as cm', 'cm.ai_predict_result_id', '=', 'apr.id')
            ->whereNull('cm.id')
            ->select('apr.id', 'apr.keywords')
            ->get();

        foreach ($results as $result) {
            CaseManagement::create([
                'id'                   => (string) Str::uuid(),
                'ai_predict_result_id' => $result->id,
                'keywords'             => $result->keywords,
                'internal_case_no'     => 'INT-' . strtoupper(uniqid()),
                'status'               => 'pending_notification',
            ]);
        }
    }
}

<?php
namespace App\Http\Controllers;

use App\Http\Requests\DashboardStatsRequest;
use App\Services\DashboardService;
use App\Services\KeywordRankingService;

class DashboardController extends Controller
{
    protected DashboardService $dashboardService;
    protected KeywordRankingService $keywordRankingService;

    public function __construct(
        DashboardService $dashboardService,
    ) {
        $this->dashboardService = $dashboardService;
    }

    public function stats(DashboardStatsRequest $request)
    {
        $validated = $request->validated();

        $limit  = $validated['limit'] ?? 5;
        $offset = $validated['offset'] ?? 0;

        $dashboardData = $this->dashboardService->getStats($validated);

        return response()->json([
            'code'    => 0,
            'message' => 'success',
            'data'    => [
                 ...$dashboardData,

                'system_announcements' => $this->dashboardService
                    ->getSystemAnnouncements($limit, $offset),
            ],
        ]);
    }

}

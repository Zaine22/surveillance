<?php
namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\AiPredictResult\AiPredictResultIndexRequest;
use App\Http\Requests\AiPredictResult\UpdateAiPredictResultRequest;
use App\Http\Resources\AiPredictResultIndexResource;
use App\Http\Resources\AiPredictResultShowResource;
use App\Models\AiPredictResult;
use App\Models\AiPredictResultAudit;
use App\Services\AiPredictResultService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class AiPredictResultController extends Controller
{
    public function __construct(
        protected AiPredictResultService $service
    ) {}

    public function index(
        AiPredictResultIndexRequest $request
    ): AnonymousResourceCollection {
        $results = $this->service->getAll(
            $request->validated()
        );

        return AiPredictResultIndexResource::collection($results);
    }

    public function show(
        string $id
    ): AiPredictResultShowResource {

        $result = $this->service->findById($id);

        return new AiPredictResultShowResource($result);
    }

    public function getResultItems(AiPredictResult $result)
    {

        if (! $result) {
            return response()->json([
                'message' => 'Predict result not found',
            ], 404);
        }

        $items = $this->service->getResultItems($result);

        return response()->json([
            'data' => AiPredictResultIndexResource::collection($items),
        ]);
    }

    public function update(
        UpdateAiPredictResultRequest $request,
        string $id
    ): JsonResponse {

        $this->service->evidenceReview(
            $id,
            $request->validated()['items']
        );

        return response()->json([
            'message' => 'Evidence review completed.',
        ]);
    }

    public function getAudits(Request $request)
    {
        $request->validate([
            'type' => 'nullable|in:prejudgements,audits',
        ]);

        $type = $request->query('type');

        if ($type === 'audits') {

            $results = AiPredictResultAudit::latest()->get();

        } else {

            $results = AiPredictResult::when(
                $type === 'prejudgements',
                fn($q) => $q->whereNotNull('review_status')
            )
                ->latest()
                ->get();
        }

        if ($results->isEmpty()) {
            return response()->json([
                'type'    => $type,
                'message' => 'No data found',
                'results' => [],
            ], 200);
        }

        return response()->json([
            'type'    => $type,
            'message' => 'Data retrieved successfully',
            'results' => $results,
        ], 200);
    }

}

<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\AiPredictResult\AiPredictResultIndexRequest;
use App\Http\Resources\AiPredictResultResource;
use App\Services\AiPredictResultService;
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

        return AiPredictResultResource::collection($results);
    }
}

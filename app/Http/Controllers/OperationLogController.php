<?php
namespace App\Http\Controllers;

use App\Http\Requests\OperationLogIndexRequest;
use App\Services\OperationLogService;

class OperationLogController extends Controller
{
    public function __construct(
        protected OperationLogService $service
    ) {}

    public function index(OperationLogIndexRequest $request)
    {
        return response()->json(
            $this->service->getAllLogs($request->validated())
        );
    }
}

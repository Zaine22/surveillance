<?php
 
namespace App\Http\Docs;
 
use OpenApi\Attributes as OA;
 
class CaseManagementDocs
{
    #[OA\Post(
        path: '/api/caseFeedback',
        summary: 'Submit case feedback',
        tags: ['Case Management'],
        security: [['apikey' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['case_id', 'url', 'is_illegal'],
                properties: [
                    new OA\Property(property: 'case_id', type: 'string', example: 'CASE123'),
                    new OA\Property(property: 'url', type: 'string', format: 'url', example: 'https://example.com'),
                    new OA\Property(property: 'is_illegal', type: 'boolean', example: true),
                    new OA\Property(property: 'legal_basis', type: 'string', example: 'Copyright Act'),
                    new OA\Property(property: 'reason', type: 'string', example: 'Unauthorised distribution')
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Success',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'success'),
                        new OA\Property(property: 'message', type: 'string', example: '已接收')
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthorized'),
            new OA\Response(
                response: 500,
                description: 'Error',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'error'),
                        new OA\Property(property: 'message', type: 'string', example: '反馈保存失败')
                    ]
                )
            )
        ]
    )]
    public function netChineseCaseFeedback() {}
 
    #[OA\Post(
        path: '/api/newcaseCreate',
        summary: 'Create external case',
        tags: ['Case Management'],
        security: [['apikey' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['url', 'leakReason', 'case_id'],
                properties: [
                    new OA\Property(property: 'url', type: 'string', format: 'url', example: 'https://example.com'),
                    new OA\Property(property: 'leakReason', type: 'string', example: 'Found on dark web'),
                    new OA\Property(property: 'case_id', type: 'string', example: 'EXT-001'),
                    new OA\Property(property: 'issue_date', type: 'string', format: 'date', example: '2024-03-26'),
                    new OA\Property(property: 'due_date', type: 'string', format: 'date', example: '2024-04-26')
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Success',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'success'),
                        new OA\Property(property: 'message', type: 'string', example: '已接收')
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthorized'),
            new OA\Response(
                response: 500,
                description: 'Error',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'error'),
                        new OA\Property(property: 'message', type: 'string', example: '外部案件创建失败')
                    ]
                )
            )
        ]
    )]
    public function externalCaseCreate() {}
 
    #[OA\Post(
        path: '/api/caseScreenshot',
        summary: 'Process case screenshot',
        tags: ['Case Management'],
        security: [['apikey' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['issue_date', 'due_date', 'case_id', 'url'],
                properties: [
                    new OA\Property(property: 'issue_date', type: 'string', format: 'date', example: '2024-03-26'),
                    new OA\Property(property: 'due_date', type: 'string', format: 'date', example: '2024-03-27'),
                    new OA\Property(property: 'case_id', type: 'string', example: 'EXT-001'),
                    new OA\Property(property: 'url', type: 'string', format: 'url', example: 'https://example.com')
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Success'),
            new OA\Response(response: 401, description: 'Unauthorized')
        ]
    )]
    public function netChineseCaseScreenshot() {}
}

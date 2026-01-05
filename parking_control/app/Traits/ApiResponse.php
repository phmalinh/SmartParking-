<?php

namespace App\Traits;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;

trait ApiResponse
{
    // 成功回傳
    protected function successResponse($data = null, $message = '執行成功', $code = 200): JsonResponse
    {
        if ($data instanceof JsonResource) {
            $response = $data->toResponse(request());
            $original = $response->getData(true);

            $responseData = [
                'success' => true,
                'message' => $message,
                'data' => $original['data'] ?? null,
            ];

            if (! empty($original['meta'])) {
                $responseData['meta'] = $original['meta'];
            }

            return response()->json($responseData, $code);
        }

        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
        ], $code);
    }

    // 驗證失敗
    protected function errorResponse($message = '執行失敗', ?array $errors = null, $code = 400): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'errors' => $errors,
        ], $code);
    }
}
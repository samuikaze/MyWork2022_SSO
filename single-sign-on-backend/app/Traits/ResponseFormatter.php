<?php

namespace App\Traits;

use Illuminate\Http\JsonResponse;

trait ResponseFormatter
{
    /**
     * 格式化返回的資料
     *
     * @param string|null $error 錯誤訊息
     * @param mixed $data 資料
     * @param int $status [200] 狀態碼
     * @param array<string,string> $headers [] 標頭
     * @return \Illuminate\Http\JsonResponse
     */
    public function response(string $error = null, $data = null, int $status = 200, array $headers = []): JsonResponse
    {
        $response = [
            'status' => $status,
            'message' => $error,
            'data' => $data,
        ];

        return response()->json($response, $status, $headers);
    }
}

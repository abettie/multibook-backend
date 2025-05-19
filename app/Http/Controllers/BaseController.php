<?php

namespace App\Http\Controllers;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use OpenApi\Attributes as OA;

#[OA\Info(
    title: 'マルチ図鑑API',
    version: '0.1.0',
    description: 'マルチ図鑑APIの仕様書です。'
)]
#[OA\Server(
    url: 'http://localhost:8000/api',
    description: '開発環境'
)]
class BaseController extends Controller
{
    protected function customIndexResponse (Collection $body)
    {
        // APIレスポンスの共通形式はここで定義
        return response()->json($body, 200);
    }

    protected function customStoreResponse (Model $body)
    {
        return response()->json($body, 200);
    }

    protected function customShowResponse (Model $body)
    {
        return response()->json($body, 200);
    }

    protected function customUpdateResponse (Model $body)
    {
        return response()->json($body, 200);
    }

    protected function customDestroyResponse (Model $body)
    {
        return response()->json($body, 200);
    }

    protected function customErrorResponse (string $message)
    {
        return response()->json(['message' => $message], 400);
    }
}

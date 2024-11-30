<?php

namespace App\Http\Controllers;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

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
}

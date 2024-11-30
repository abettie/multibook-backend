<?php

namespace App\Http\Controllers;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;

class BaseController extends Controller
{
    protected function customApiResponse (Collection $body)
    {
        // APIレスポンスの共通形式はここで定義
        return response()->json($body, 200);
    }
}

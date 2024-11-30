<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class BaseController extends Controller
{
    protected function customApiResponse (Array $body)
    {
        // APIレスポンスの共通形式はここで定義
        return response()->json($body);
    }
}

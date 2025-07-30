<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class DebugController extends Controller
{
    public function debug(Request $request)
    {
        // CORS解除
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Headers: *');
        header('Access-Control-Allow-Methods: POST, OPTIONS');

        // ログ出力
        Log::info('--- Debug API Request ---');
        Log::info('Headers: ', $request->headers->all());
        Log::info('Body: ', $request->all());
        Log::info('IP: ' . $request->ip());
        Log::info('Method: ' . $request->method());
        Log::info('UserAgent: ' . $request->userAgent());
        Log::info('------------------------');

        return response()->json(['status' => 'ok']);
    }
}

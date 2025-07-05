<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    public function check(Request $request)
    {
        if (Auth::check()) {
            return response()->json(['message' => 'OK'], 200);
        } else {
            return response()->json(['message' => 'Unauthorized'], 401);
        }
    }
}

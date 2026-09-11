<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Models\RefreshToken;
use Illuminate\Http\Request;

class LogoutController extends Controller
{
    public function handle(Request $request)
    {
        $refreshToken = (string) $request->input('refresh_token', '');

        if ($refreshToken !== '') {
            RefreshToken::where('token_hash', hash('sha256', $refreshToken))
                ->whereNull('revoked_at')
                ->update(['revoked_at' => now()]);
        }

        return response()->json(['success' => true, 'redirect' => '/auth/login']);
    }
}

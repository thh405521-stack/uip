<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Models\RefreshToken;
use App\Services\RoleService;
use App\Services\UipJwtService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * The React app's api/client.js calls this automatically whenever a
 * request comes back 401 (access token expired), then retries the
 * original request once with the new access token.
 */
class RefreshTokenController extends Controller
{
    public function __construct(private RoleService $roles)
    {
    }

    public function submit(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'refresh_token' => 'required',
        ]);

        if ($validator->fails()) {
            return $this->apiError($validator->errors()->first(), $validator->errors()->toArray(), 422);
        }

        $hash = hash('sha256', $request->input('refresh_token'));

        $row = RefreshToken::where('token_hash', $hash)
            ->whereNull('revoked_at')
            ->where('expires_at', '>', now())
            ->first();

        if (!$row) {
            return $this->apiError('This refresh token is invalid, expired, or has already been used.', null, 401);
        }

        $role = $this->roles->primaryRoleFor($row->user_id);
        $tokens = UipJwtService::issueTokenPair($row->user_id, $role);

        // Rotation: the old refresh token is single-use.
        $newest = RefreshToken::where('user_id', $row->user_id)->latest('id')->first();
        $row->update(['revoked_at' => now(), 'replaced_by_id' => $newest?->id]);

        return $this->apiSuccess($tokens, 'Token refreshed successfully.');
    }
}

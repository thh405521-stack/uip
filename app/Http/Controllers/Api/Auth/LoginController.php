<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\RoleService;
use App\Services\UipJwtService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class LoginController extends Controller
{
    public function __construct(private RoleService $roles)
    {
    }

    public function submit(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if ($validator->fails()) {
            return $this->apiError($validator->errors()->first(), $validator->errors()->toArray(), 422);
        }

        $data = $validator->validated();
        $user = User::where('email', $data['email'])->first();

        if (!$user || !Hash::check($data['password'], $user->password)) {
            return $this->apiError('Invalid email or password.', null, 422);
        }

        if ($user->status === 'suspended') {
            return $this->apiError('This account has been suspended. Contact support.', null, 422);
        }

        $user->update([
            'last_login_at' => now(),
            'last_login_ip' => $request->ip(),
        ]);

        $role = $this->roles->primaryRoleFor($user->id);
        $tokens = UipJwtService::issueTokenPair($user->id, $role);

        return $this->apiSuccess(
            array_merge(['redirect' => $this->roles->homeRouteFor($role)], $tokens),
            'Login successful.'
        );
    }
}

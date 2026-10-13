<?php

namespace App\Http\Controllers;

use App\Http\Middleware\ExpireOwnerSession;
use App\Modules\Identity\OwnerLogin;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class OwnerLoginController extends Controller
{
    public function __invoke(Request $request, OwnerLogin $login): Response
    {
        $credentials = $request->validate([
            'email' => ['required', 'string'],
            'password' => ['required', 'string'],
            'code' => ['required', 'string'],
        ]);

        $user = $login->attempt($credentials['email'], $credentials['password'], $credentials['code']);

        if ($user === null) {
            throw ValidationException::withMessages([
                'email' => 'Those details do not match an owner account.',
            ]);
        }

        Auth::login($user);
        $request->session()->regenerate();
        $request->session()->put(ExpireOwnerSession::STARTED_AT, now()->getTimestamp());

        return response()->noContent();
    }
}

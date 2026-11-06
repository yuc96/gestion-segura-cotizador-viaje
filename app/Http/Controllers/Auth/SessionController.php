<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

final class SessionController extends Controller
{
    public function store(LoginRequest $request): JsonResponse
    {
        $request->authenticate();
        $request->session()->regenerate();

        return response()->json([
            'data' => $this->userPayload($request),
        ]);
    }

    public function show(Request $request): JsonResponse
    {
        $user = Auth::guard('web')->user();

        return response()->json([
            'data' => $user ? [
                'name' => $user->name,
                'email' => $user->email,
            ] : null,
        ]);
    }

    public function destroy(Request $request): JsonResponse
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json([
            'message' => 'Sesión cerrada.',
        ]);
    }

    /**
     * @return array{name: string, email: string}
     */
    private function userPayload(Request $request): array
    {
        $user = $request->user();

        return [
            'name' => (string) $user?->name,
            'email' => (string) $user?->email,
        ];
    }
}

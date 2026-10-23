<?php

namespace App\Http\Controllers\Api\V1\Provider;

use App\Http\Controllers\BaseController;
use App\Http\Requests\V1\AdminTokenRequest;
use App\Http\Requests\V1\ProviderLoginRequest;
use App\Http\Resources\V1\Admin\AdminUserResource;
use App\Http\Resources\V1\ProviderResource;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\PersonalAccessToken;

/**
 * ProviderAuthController
 *
 * Autenticación individual del proveedor con Laravel Sanctum:
 *
 *  - POST   /api/v1/provider/auth/login    emite un token personal con
 *                                          abilidad `provider`
 *  - POST   /api/v1/provider/auth/logout   revoca el token en uso
 *  - GET    /api/v1/provider/auth/me       usuario + su proveedor
 *
 * Es un flujo independiente del backoffice (/admin/auth/*): el login solo
 * acepta cuentas con rol PROVEEDOR_TURISTICO y una ficha de proveedor
 * vinculada, y emite un token cuya abilidad distingue al panel del
 * proveedor del panel admin (un token `admin` no entra en /provider/*).
 */
class ProviderAuthController extends BaseController
{
    /**
     * POST /api/v1/provider/auth/login — email + contraseña -> token.
     */
    public function login(ProviderLoginRequest $request): JsonResponse
    {
        $credentials = $request->safe()->only(['email', 'password']);

        $user = User::query()->where('email', $credentials['email'])->first();

        if ($user === null || ! Hash::check((string) $credentials['password'], $user->password)) {
            return apiError(
                message: 'Invalid credentials.',
                errors: ['email' => ['The provided credentials are incorrect.']],
                status: 401,
            );
        }

        $provider = $user->provider;

        // Sin el rol de proveedor o sin ficha vinculada no hay panel que
        // abrir: emitir un token solo generaría credenciales inútiles.
        if (! $user->hasRole(Role::PROVEEDOR_TURISTICO) || $provider === null) {
            return apiError(
                message: 'This account is not allowed to access the provider panel.',
                errors: ['role' => ['A provider role and a linked provider account are required to log in.']],
                status: 403,
            );
        }

        $token = $user->createToken(
            (string) $request->input('device_name', 'provider-app'),
            ['provider'],
        );

        return apiSuccess(
            data: [
                'token' => $token->plainTextToken,
                'token_type' => 'Bearer',
                'user' => new AdminUserResource($user),
                'provider' => new ProviderResource($provider),
            ],
            message: 'Authenticated successfully.',
        );
    }

    /**
     * POST /api/v1/provider/auth/logout — revoca el token en uso.
     */
    public function logout(Request $request): JsonResponse
    {
        $token = $request->user()->currentAccessToken();

        // Solo se revoca si el acceso vino por un token personal; una
        // llamada con sesión de cookie trae un TransientToken.
        if ($token instanceof PersonalAccessToken) {
            $token->delete();
        }

        return apiSuccess(message: 'Logged out successfully.');
    }

    /**
     * GET /api/v1/provider/auth/me — usuario autenticado con su proveedor.
     */
    public function me(Request $request): JsonResponse
    {
        $user = $request->user();

        return apiSuccess(
            data: [
                'user' => new AdminUserResource($user),
                'provider' => new ProviderResource($user->provider),
            ],
            message: 'Authenticated provider retrieved successfully.',
        );
    }

    /**
     * GET /api/v1/provider/auth/tokens — tokens personales del proveedor.
     */
    public function tokens(Request $request): JsonResponse
    {
        $tokens = $request->user()
            ->tokens()
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get(['id', 'name', 'last_used_at', 'created_at', 'expires_at']);

        return apiSuccess(
            data: $tokens,
            message: 'Personal tokens retrieved successfully.',
        );
    }

    /**
     * POST /api/v1/provider/auth/tokens — genera un token personal.
     *
     * Siempre con abilidad `provider`: un proveedor jamás puede fabricar un
     * token `admin`.
     */
    public function storeToken(AdminTokenRequest $request): JsonResponse
    {
        $token = $request->user()->createToken($request->validated('name'), [$this->tokenAbility()]);

        return apiSuccess(
            data: [
                'id' => $token->accessToken->id,
                'name' => $token->accessToken->name,
                'token' => $token->plainTextToken,
                'token_type' => 'Bearer',
            ],
            message: 'Token created successfully.',
            status: 201,
        );
    }

    /**
     * DELETE /api/v1/provider/auth/tokens/{tokenId} — revoca un token propio.
     */
    public function revokeToken(Request $request, int $tokenId): JsonResponse
    {
        $request->user()->tokens()->findOrFail($tokenId)->delete();

        return apiSuccess(message: 'Token revoked successfully.');
    }

    /**
     * DELETE /api/v1/provider/auth/tokens/others — revoca las demás sesiones.
     */
    public function revokeOtherTokens(Request $request): JsonResponse
    {
        $current = $request->user()->currentAccessToken();
        $currentId = $current instanceof PersonalAccessToken ? $current->id : null;

        $query = $request->user()->tokens();

        if ($currentId !== null) {
            $query->whereKeyNot($currentId);
        }

        return apiSuccess(
            data: ['revoked' => (int) $query->delete()],
            message: 'Other sessions revoked successfully.',
        );
    }

    private function tokenAbility(): string
    {
        return (string) config('api.admin.abilities.provider', 'provider');
    }
}

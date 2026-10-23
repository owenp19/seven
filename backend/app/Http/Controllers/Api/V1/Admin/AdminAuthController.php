<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\BaseController;
use App\Http\Requests\V1\AdminLoginRequest;
use App\Http\Requests\V1\AdminTokenRequest;
use App\Http\Resources\V1\Admin\AdminUserResource;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\PersonalAccessToken;

/**
 * AdminAuthController
 *
 * Autenticación administrativa individual con Laravel Sanctum:
 *
 *  - POST   /api/v1/admin/auth/login           emite un token personal
 *  - POST   /api/v1/admin/auth/logout          revoca el token en uso
 *  - GET    /api/v1/admin/auth/me              usuario + roles + permisos
 *  - GET    /api/v1/admin/auth/tokens          tokens personales vivos
 *  - POST   /api/v1/admin/auth/tokens          emite un token con nombre
 *  - DELETE /api/v1/admin/auth/tokens/{id}     revoca un token
 *
 * Solo el login es público (con throttle propio). El resto corre bajo
 * `auth:sanctum`, así que exige un usuario real y no el token compartido
 * legacy. El token solo se devuelve en texto claro en la respuesta que lo
 * crea: después solo se almacena su hash en `personal_access_tokens`.
 */
class AdminAuthController extends BaseController
{
    /**
     * POST /api/v1/admin/auth/login — email + contraseña -> token.
     *
     * @OA\Post(
     *     path="/admin/auth/login",
     *     operationId="adminLogin",
     *     tags={"Autenticación"},
     *     summary="Autentica un usuario administrativo",
     *     description="Devuelve un token personal (Bearer) para usar en el resto de endpoints /admin/*.",
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *             required={"email", "password"},
     *
     *             @OA\Property(property="email", type="string", format="email", example="admin@seven.test"),
     *             @OA\Property(property="password", type="string", format="password", example="password"),
     *             @OA\Property(property="device_name", type="string", example="Panel Web"),
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Autenticación exitosa.",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Authenticated successfully."),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="token", type="string"),
     *                 @OA\Property(property="token_type", type="string", example="Bearer"),
     *                 @OA\Property(property="user", type="object"),
     *             ),
     *         )
     *     ),
     *
     *     @OA\Response(response=401, description="Credenciales incorrectas."),
     *     @OA\Response(response=403, description="El usuario no tiene un rol administrativo."),
     *     @OA\Response(response=422, description="Error de validación."),
     *     @OA\Response(response=429, description="Demasiados intentos de login.")
     * )
     */
    public function login(AdminLoginRequest $request): JsonResponse
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

        // Emitir un token a un cliente que no puede entrar al panel solo
        // generaría credenciales inútiles y visibles en la lista de tokens.
        $loginRoles = (array) config('api.admin.login_roles', Role::ADMIN);

        if (! $user->hasAnyRole($loginRoles)) {
            return apiError(
                message: 'This account is not allowed to access the admin panel.',
                errors: ['role' => ['An administrative role is required to log in.']],
                status: 403,
            );
        }

        $token = $user->createToken(
            (string) $request->input('device_name', 'api'),
            [$this->tokenAbility()],
        );

        return apiSuccess(
            data: [
                'token' => $token->plainTextToken,
                'token_type' => 'Bearer',
                'user' => new AdminUserResource($user),
            ],
            message: 'Authenticated successfully.',
        );
    }

    /**
     * POST /api/v1/admin/auth/logout — revoca el token en uso.
     *
     * @OA\Post(
     *     path="/admin/auth/logout",
     *     operationId="adminLogout",
     *     tags={"Autenticación"},
     *     summary="Cierra la sesión revocando el token actual",
     *
     *     @OA\Response(response=200, description="Sesión cerrada."),
     *     @OA\Response(response=401, description="No autenticado.")
     * )
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
     * GET /api/v1/admin/auth/me — usuario autenticado.
     *
     * @OA\Get(
     *     path="/admin/auth/me",
     *     operationId="adminMe",
     *     tags={"Autenticación"},
     *     summary="Devuelve el usuario autenticado con sus roles y permisos",
     *
     *     @OA\Response(response=200, description="Usuario autenticado."),
     *     @OA\Response(response=401, description="No autenticado.")
     * )
     */
    public function me(Request $request): JsonResponse
    {
        return apiSuccess(
            data: new AdminUserResource($request->user()),
            message: 'Authenticated user retrieved successfully.',
        );
    }

    /**
     * GET /api/v1/admin/auth/tokens — tokens personales del usuario.
     *
     * @OA\Get(
     *     path="/admin/auth/tokens",
     *     operationId="adminTokens",
     *     tags={"Autenticación"},
     *     summary="Lista los tokens personales del usuario",
     *
     *     @OA\Response(response=200, description="Lista de tokens."),
     *     @OA\Response(response=401, description="No autenticado.")
     * )
     */
    public function tokens(Request $request): JsonResponse
    {
        $tokens = $request->user()
            ->tokens()
            ->orderByDesc('created_at')
            // Desempate por id: crear dos tokens en el mismo segundo no
            // debe invertir el orden de la lista.
            ->orderByDesc('id')
            ->get(['id', 'name', 'last_used_at', 'created_at', 'expires_at']);

        return apiSuccess(
            data: $tokens,
            message: 'Personal tokens retrieved successfully.',
        );
    }

    /**
     * POST /api/v1/admin/auth/tokens — genera un token personal.
     *
     * @OA\Post(
     *     path="/admin/auth/tokens",
     *     operationId="adminStoreToken",
     *     tags={"Autenticación"},
     *     summary="Genera un token personal adicional",
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *             required={"name"},
     *
     *             @OA\Property(property="name", type="string", example="CI Windows"),
     *         )
     *     ),
     *
     *     @OA\Response(response=201, description="Token generado."),
     *     @OA\Response(response=401, description="No autenticado."),
     *     @OA\Response(response=422, description="Error de validación.")
     * )
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
     * DELETE /api/v1/admin/auth/tokens/{tokenId} — revoca un token.
     *
     * @OA\Delete(
     *     path="/admin/auth/tokens/{tokenId}",
     *     operationId="adminRevokeToken",
     *     tags={"Autenticación"},
     *     summary="Revoca un token personal",
     *
     *     @OA\Parameter(
     *         name="tokenId",
     *         in="path",
     *         required=true,
     *
     *         @OA\Schema(type="integer")
     *     ),
     *
     *     @OA\Response(response=200, description="Token revocado."),
     *     @OA\Response(response=401, description="No autenticado."),
     *     @OA\Response(response=404, description="El token no existe o no pertenece al usuario.")
     * )
     */
    public function revokeToken(Request $request, int $tokenId): JsonResponse
    {
        // findOrFail sobre la relación: un id ajeno responde 404 y no 403
        // para no revelar la existencia de tokens de otros usuarios.
        $request->user()->tokens()->findOrFail($tokenId)->delete();

        return apiSuccess(message: 'Token revoked successfully.');
    }

    /**
     * DELETE /api/v1/admin/auth/tokens/others — revoca todas las demás sesiones.
     *
     * @OA\Delete(
     *     path="/admin/auth/tokens/others",
     *     operationId="adminRevokeOtherTokens",
     *     tags={"Autenticación"},
     *     summary="Revoca todos los tokens personales salvo el actual",
     *
     *     @OA\Response(response=200, description="Sesiones revocadas."),
     *     @OA\Response(response=401, description="No autenticado.")
     * )
     */
    public function revokeOtherTokens(Request $request): JsonResponse
    {
        $revoked = $this->revokeAllExceptCurrent($request);

        return apiSuccess(
            data: ['revoked' => $revoked],
            message: 'Other sessions revoked successfully.',
        );
    }

    /**
     * Borra todos los tokens del usuario excepto el que autenticó la
     * petición (si vino por token personal). Una sesión de cookie no tiene
     * token asociado y se revocan todos.
     */
    private function revokeAllExceptCurrent(Request $request): int
    {
        $current = $request->user()->currentAccessToken();
        $currentId = $current instanceof PersonalAccessToken ? $current->id : null;

        $query = $request->user()->tokens();

        if ($currentId !== null) {
            $query->whereKeyNot($currentId);
        }

        return (int) $query->delete();
    }

    private function tokenAbility(): string
    {
        return (string) config('api.admin.abilities.admin', 'admin');
    }
}

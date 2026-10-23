<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * AuditLogger — registra acciones administrativas sensibles.
 *
 * Regla de oro: a la tabla NUNCA entra material sensible. Las claves que
 * contengan "password", "token", "secret", "authorization" o "hash" se
 * sustituyen por "[redacted]" de forma recursiva antes de persistir.
 *
 * Es tolerante a fallos: si la escritura falla, se registra un warning y la
 * acción de negocio continúa. La auditoría nunca debe tumbar la petición.
 */
class AuditLogger
{
    public const REDACTED = '[redacted]';

    private const SENSITIVE_FRAGMENTS = [
        'password',
        'token',
        'secret',
        'authorization',
        'access_hash',
        'remember',
    ];

    private const MAX_STRING_LENGTH = 1000;

    private const MAX_DEPTH = 5;

    /**
     * Registra una acción resolviendo el actor desde la petición.
     */
    public function record(
        ?User $user,
        string $action,
        ?Model $subject = null,
        array $metadata = [],
    ): ?AuditLog {
        return $this->write($user, $action, $subject, $metadata);
    }

    /**
     * Igual que record() pero tomando el usuario autenticado del request.
     */
    public function recordFor(
        Request $request,
        string $action,
        ?Model $subject = null,
        array $metadata = [],
    ): ?AuditLog {
        $user = $request->user('sanctum') ?? $request->user();

        return $this->write($user instanceof User ? $user : null, $action, $subject, $metadata);
    }

    private function write(?User $user, string $action, ?Model $subject, array $metadata): ?AuditLog
    {
        try {
            return AuditLog::create([
                'user_id' => $user?->id,
                'action' => $action,
                'auditable_type' => $subject ? $subject::class : null,
                'auditable_id' => $subject?->getKey(),
                'metadata' => $metadata === [] ? null : $this->sanitize($metadata),
                'created_at' => now(),
            ]);
        } catch (Throwable $e) {
            Log::warning('Failed to write audit log.', [
                'action' => $action,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Redacta recursivamente claves sensibles y acota el tamaño de los
     * valores. La estructura se conserva para que el metadata siga siendo
     * útil en consultas.
     */
    public function sanitize(array $metadata, int $depth = 0): array
    {
        if ($depth >= self::MAX_DEPTH) {
            return [];
        }

        $clean = [];

        foreach ($metadata as $key => $value) {
            if (is_string($key) && $this->isSensitive($key)) {
                $clean[$key] = self::REDACTED;

                continue;
            }

            if (is_array($value)) {
                $clean[$key] = $this->sanitize($value, $depth + 1);

                continue;
            }

            if (is_string($value) && mb_strlen($value) > self::MAX_STRING_LENGTH) {
                $clean[$key] = mb_substr($value, 0, self::MAX_STRING_LENGTH);

                continue;
            }

            $clean[$key] = $value;
        }

        return $clean;
    }

    private function isSensitive(string $key): bool
    {
        $key = mb_strtolower($key);

        foreach (self::SENSITIVE_FRAGMENTS as $fragment) {
            if (str_contains($key, $fragment)) {
                return true;
            }
        }

        return false;
    }
}

<?php
declare(strict_types=1);

final class Auth
{
    private static ?array $user = null;

    public static function user(): ?array
    {
        if (self::$user === null && !empty($_SESSION['uid'])) {
            self::$user = DB::one('SELECT * FROM users WHERE id = ? AND activo = 1', [$_SESSION['uid']]);
            if (!self::$user) {
                unset($_SESSION['uid']);
            }
        }
        return self::$user;
    }

    public static function id(): int
    {
        return (int)(self::user()['id'] ?? 0);
    }

    public static function isAdmin(): bool
    {
        return (self::user()['rol'] ?? '') === 'admin';
    }

    public static function login(array $user): void
    {
        session_regenerate_id(true);
        $_SESSION['uid'] = (int)$user['id'];
        self::$user = $user;
    }

    public static function logout(): void
    {
        $_SESSION = [];
        session_regenerate_id(true);
    }

    public static function requireLogin(): array
    {
        $u = self::user();
        if (!$u) {
            flash('info', 'Inicia sesión para continuar.');
            redirect('login');
        }
        return $u;
    }

    public static function requireRole(string $rol): array
    {
        $u = self::requireLogin();
        if ($u['rol'] !== $rol) {
            abort(403, 'Esta sección no está disponible para tu cuenta.');
        }
        return $u;
    }

    /** Límite simple: 5 intentos fallidos cada 15 minutos por correo+IP. */
    public static function tooManyAttempts(string $email): bool
    {
        $key = self::attemptKey($email);
        $n = (int) DB::value('SELECT COUNT(*) FROM login_intentos WHERE clave = ? AND created_at > (NOW() - INTERVAL 15 MINUTE)', [$key]);
        return $n >= 5;
    }

    public static function registerFailure(string $email): void
    {
        DB::run('INSERT INTO login_intentos (clave) VALUES (?)', [self::attemptKey($email)]);
        DB::run('DELETE FROM login_intentos WHERE created_at < (NOW() - INTERVAL 1 DAY)');
    }

    public static function clearFailures(string $email): void
    {
        DB::run('DELETE FROM login_intentos WHERE clave = ?', [self::attemptKey($email)]);
    }

    private static function attemptKey(string $email): string
    {
        return mb_strtolower($email) . '|' . ($_SERVER['REMOTE_ADDR'] ?? '');
    }
}

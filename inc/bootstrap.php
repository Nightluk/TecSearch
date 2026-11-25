<?php
declare(strict_types=1);

// ---------- .env ----------
(function () {
    $f = dirname(__DIR__) . '/.env';
    if (!is_file($f)) return;
    foreach (file($f, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $l) {
        $l = trim($l);
        if ($l === '' || $l[0] === '#' || !str_contains($l, '=')) continue;
        [$k, $v] = explode('=', $l, 2);
        $_ENV[trim($k)] = trim(trim($v), "\"'");
    }
})();

function env(string $k, ?string $d = null): ?string
{
    return $_ENV[$k] ?? (getenv($k) ?: $d);
}

// ---------- Sessão ----------
session_set_cookie_params([
    'lifetime' => 0,
    'path'     => '/',
    'httponly' => true,
    'samesite' => 'Lax',
    'secure'   => !empty($_SERVER['HTTPS']),
]);
session_start();

// ---------- Banco (Supabase / Postgres via PDO) ----------
function db(): PDO
{
    static $pdo = null;
    if ($pdo) return $pdo;
    $dsn = sprintf(
        'pgsql:host=%s;port=%s;dbname=%s;sslmode=%s',
        env('DB_HOST', 'localhost'), env('DB_PORT', '5432'), env('DB_NAME', 'postgres'), env('DB_SSLMODE', 'require')
    );
    $pdo = new PDO($dsn, env('DB_USER'), env('DB_PASS'), [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => true, // compatível com o pooler do Supabase
    ]);
    return $pdo;
}

set_exception_handler(function (Throwable $e) {
    error_log('[TecSearch] ' . $e);
    http_response_code(500);
    echo '<!doctype html><meta charset="utf-8"><title>Erro</title><body style="font-family:sans-serif;padding:2rem">'
       . '<h1>Ops, algo deu errado.</h1><p>Tente novamente em instantes.</p>';
    if (env('APP_DEBUG') === '1') echo '<pre>' . htmlspecialchars((string) $e) . '</pre>';
});

// ---------- Helpers ----------
function e(?string $s): string
{
    return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
}

function redirect(string $to): never
{
    header('Location: ' . $to);
    exit;
}

function flash(string $tipo, string $msg): void
{
    $_SESSION['flash'][] = [$tipo, $msg];
}

function csrf_token(): string
{
    return $_SESSION['csrf'] ??= bin2hex(random_bytes(32));
}

function csrf_campo(): string
{
    return '<input type="hidden" name="csrf" value="' . csrf_token() . '">';
}

function csrf_validar(): void
{
    $ok = isset($_POST['csrf']) && hash_equals(csrf_token(), (string) $_POST['csrf']);
    if (!$ok) {
        http_response_code(400);
        exit('Requisição inválida (token expirado). Volte e tente novamente.');
    }
}

function so_digitos(string $s): string
{
    return preg_replace('/\D+/', '', $s) ?? '';
}

// ---------- Autenticação ----------
function usuario(): ?array
{
    static $u = false;
    if ($u !== false) return $u;
    $id = $_SESSION['cliente_id'] ?? null;
    if (!$id) return $u = null;
    $st = db()->prepare('select id, nome, email, telefone from clientes where id = :id');
    $st->execute([':id' => $id]);
    $u = $st->fetch() ?: null;
    if (!$u) unset($_SESSION['cliente_id']);
    return $u;
}

function logar(int $id): void
{
    session_regenerate_id(true);
    $_SESSION['cliente_id'] = $id;
}

function exigir_login(): array
{
    $u = usuario();
    if (!$u) {
        flash('warning', 'Entre na sua conta para continuar.');
        redirect('entrar.php?next=' . urlencode(basename($_SERVER['SCRIPT_NAME']) . ($_SERVER['QUERY_STRING'] ? '?' . $_SERVER['QUERY_STRING'] : '')));
    }
    return $u;
}

/** Só aceita caminhos locais (evita open redirect). */
function destino_seguro(?string $next): string
{
    $next = (string) $next;
    if ($next !== '' && preg_match('#^[a-z0-9_\-]+\.php(\?[a-zA-Z0-9_=&%\-\.]*)?$#i', $next)) return $next;
    return 'index.php';
}

<?php
require __DIR__ . '/inc/bootstrap.php';
require __DIR__ . '/inc/funcoes.php';

if (usuario()) redirect('index.php');

$erro = '';
$ident = '';
$next = $_POST['next'] ?? $_GET['next'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_validar();
    $ident = trim($_POST['identificador'] ?? '');
    $senha = (string) ($_POST['senha'] ?? '');

    // Limite simples de tentativas por sessão
    $_SESSION['tentativas'] = $_SESSION['tentativas'] ?? ['n' => 0, 't' => time()];
    if (time() - $_SESSION['tentativas']['t'] > 600) $_SESSION['tentativas'] = ['n' => 0, 't' => time()];

    if ($_SESSION['tentativas']['n'] >= 8) {
        $erro = 'Muitas tentativas. Aguarde alguns minutos.';
    } else {
        $st = db()->prepare('select id, senha from clientes where lower(email) = lower(:e) or telefone = :t limit 1');
        $st->execute([':e' => $ident, ':t' => so_digitos($ident) ?: '-']);
        $c = $st->fetch();
        // verifica sempre um hash para não vazar se o usuário existe pelo tempo de resposta
        $hash = $c['senha'] ?? '$2y$10$usesomesillystringforsaltuQ0n8E1bYcWnXzV0p9K3q7k5yA1H2m';
        if ($c && password_verify($senha, $hash)) {
            if (password_needs_rehash($hash, PASSWORD_DEFAULT)) {
                db()->prepare('update clientes set senha = :s where id = :id')->execute([':s' => password_hash($senha, PASSWORD_DEFAULT), ':id' => $c['id']]);
            }
            logar((int) $c['id']);
            redirect(destino_seguro($next));
        }
        $_SESSION['tentativas']['n']++;
        $erro = 'E-mail/telefone ou senha incorretos.';
    }
}

$titulo = 'Entrar';
require __DIR__ . '/inc/auth_topo.php';
?>
        <form method="post" novalidate>
            <?= csrf_campo() ?>
            <input type="hidden" name="next" value="<?= e($next) ?>">
            <h2 class="h6 mb-3 text-body-secondary fw-normal">Insira seus dados:</h2>
            <?php if ($erro): ?><div class="alert alert-danger py-2"><?= e($erro) ?></div><?php endif; ?>

            <div class="form-floating mb-2">
                <input type="text" class="form-control" id="identificador" name="identificador" placeholder="E-mail ou telefone" value="<?= e($ident) ?>" required autocomplete="username">
                <label for="identificador">E-mail ou Telefone</label>
            </div>
            <div class="form-floating mb-3">
                <input type="password" class="form-control" id="senha" name="senha" placeholder="Senha" required autocomplete="current-password">
                <label for="senha">Senha</label>
            </div>

            <button class="btn w-100 py-2 mb-3 ts-btn" type="submit">Entrar</button>
            <button class="btn w-100 py-2 mb-3 btn-outline-secondary" type="button" disabled title="Em breve">Entrar com o Google (em breve)</button>

            <div class="text-center">
                <a href="criarconta.php<?= $next ? '?next=' . urlencode($next) : '' ?>" class="text-decoration-none text-body-secondary small">Ainda não tem uma conta? Crie a sua já!</a>
            </div>
        </form>
<?php require __DIR__ . '/inc/auth_rodape.php'; ?>

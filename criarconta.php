<?php
require __DIR__ . '/inc/bootstrap.php';
require __DIR__ . '/inc/funcoes.php';

if (usuario()) redirect('index.php');

$erros = [];
$d = ['nome' => '', 'email' => '', 'telefone' => ''];
$next = $_POST['next'] ?? $_GET['next'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_validar();
    $d['nome'] = trim($_POST['nome'] ?? '');
    $d['email'] = mb_strtolower(trim($_POST['email'] ?? ''));
    $d['telefone'] = so_digitos($_POST['telefone'] ?? '');
    $senha = (string) ($_POST['senha'] ?? '');
    $conf = (string) ($_POST['confirmar'] ?? '');

    if (mb_strlen($d['nome']) < 3 || mb_strlen($d['nome']) > 100) $erros[] = 'Informe seu nome completo.';
    if (!filter_var($d['email'], FILTER_VALIDATE_EMAIL)) $erros[] = 'E-mail inválido.';
    if (!preg_match('/^\d{10,11}$/', $d['telefone'])) $erros[] = 'Telefone inválido (use DDD + número).';
    if (strlen($senha) < 8) $erros[] = 'A senha deve ter pelo menos 8 caracteres.';
    if ($senha !== $conf) $erros[] = 'As senhas não conferem.';
    if (empty($_POST['termos'])) $erros[] = 'É preciso aceitar os Termos e Condições.';

    if (!$erros) {
        $st = db()->prepare('select email = :e as por_email from clientes where email = :e or telefone = :t limit 1');
        $st->execute([':e' => $d['email'], ':t' => $d['telefone']]);
        if ($r = $st->fetch()) $erros[] = $r['por_email'] ? 'Já existe uma conta com este e-mail.' : 'Já existe uma conta com este telefone.';
    }
    if (!$erros) {
        try {
            $st = db()->prepare('insert into clientes (nome, email, senha, telefone) values (:n, :e, :s, :t) returning id');
            $st->execute([':n' => $d['nome'], ':e' => $d['email'], ':s' => password_hash($senha, PASSWORD_DEFAULT), ':t' => $d['telefone']]);
            logar((int) $st->fetchColumn());
            flash('success', 'Conta criada! Bem-vindo(a) ao TecSearch.');
            redirect(destino_seguro($next));
        } catch (PDOException $ex) {
            if ($ex->getCode() !== '23505') throw $ex;
            $erros[] = 'E-mail ou telefone já cadastrado.';
        }
    }
}

$titulo = 'Criar conta';
require __DIR__ . '/inc/auth_topo.php';
?>
        <form method="post" novalidate>
            <?= csrf_campo() ?>
            <input type="hidden" name="next" value="<?= e($next) ?>">
            <h2 class="h6 mb-3 text-body-secondary fw-normal">Insira seus dados:</h2>
            <?php foreach ($erros as $er): ?><div class="alert alert-danger py-2 mb-2"><?= e($er) ?></div><?php endforeach; ?>

            <div class="form-floating mb-2">
                <input type="text" class="form-control" id="nome" name="nome" placeholder="Nome" value="<?= e($d['nome']) ?>" required autocomplete="name">
                <label for="nome">Nome completo</label>
            </div>
            <div class="form-floating mb-2">
                <input type="email" class="form-control" id="email" name="email" placeholder="E-mail" value="<?= e($d['email']) ?>" required autocomplete="email">
                <label for="email">E-mail</label>
            </div>
            <div class="form-floating mb-2">
                <input type="tel" class="form-control" id="telefone" name="telefone" placeholder="Telefone" value="<?= e($d['telefone']) ?>" required autocomplete="tel">
                <label for="telefone">Telefone (com DDD)</label>
            </div>
            <div class="form-floating mb-2">
                <input type="password" class="form-control" id="senha" name="senha" placeholder="Senha" required minlength="8" autocomplete="new-password">
                <label for="senha">Senha (mín. 8 caracteres)</label>
            </div>
            <div class="form-floating mb-3">
                <input type="password" class="form-control" id="confirmar" name="confirmar" placeholder="Confirmar" required autocomplete="new-password">
                <label for="confirmar">Confirmar senha</label>
            </div>

            <div class="form-check text-start mb-3">
                <input class="form-check-input" type="checkbox" name="termos" value="1" id="termos">
                <label class="form-check-label" for="termos">Aceito os <a href="#" target="_blank" rel="noopener">Termos e Condições</a></label>
            </div>

            <button class="btn w-100 py-2 mb-3 ts-btn" type="submit">Fazer cadastro</button>
            <div class="text-center">
                <a href="entrar.php<?= $next ? '?next=' . urlencode($next) : '' ?>" class="text-decoration-none text-body-secondary small">Já tem uma conta? Faça login</a>
            </div>
        </form>
<?php require __DIR__ . '/inc/auth_rodape.php'; ?>

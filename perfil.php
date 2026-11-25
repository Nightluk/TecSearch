<?php
require __DIR__ . '/inc/bootstrap.php';
require __DIR__ . '/inc/funcoes.php';
$u = exigir_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_validar();
    $acao = $_POST['acao'] ?? '';
    $erros = [];

    if ($acao === 'dados') {
        $nome = trim($_POST['nome'] ?? '');
        $email = mb_strtolower(trim($_POST['email'] ?? ''));
        $tel = so_digitos($_POST['telefone'] ?? '');
        if (mb_strlen($nome) < 3) $erros[] = 'Informe seu nome.';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $erros[] = 'E-mail inválido.';
        if (!preg_match('/^\d{10,11}$/', $tel)) $erros[] = 'Telefone inválido.';
        if (!$erros) {
            $st = db()->prepare('select 1 from clientes where (email = :e or telefone = :t) and id <> :id');
            $st->execute([':e' => $email, ':t' => $tel, ':id' => $u['id']]);
            if ($st->fetch()) $erros[] = 'E-mail ou telefone já em uso por outra conta.';
        }
        if (!$erros) {
            db()->prepare('update clientes set nome = :n, email = :e, telefone = :t where id = :id')
                ->execute([':n' => $nome, ':e' => $email, ':t' => $tel, ':id' => $u['id']]);
            flash('success', 'Dados atualizados.');
        }
    } elseif ($acao === 'senha') {
        $atual = (string) ($_POST['atual'] ?? '');
        $nova = (string) ($_POST['nova'] ?? '');
        $hash = db()->prepare('select senha from clientes where id = :id');
        $hash->execute([':id' => $u['id']]);
        if (!password_verify($atual, (string) $hash->fetchColumn())) $erros[] = 'Senha atual incorreta.';
        if (strlen($nova) < 8) $erros[] = 'A nova senha deve ter pelo menos 8 caracteres.';
        if (!$erros) {
            db()->prepare('update clientes set senha = :s where id = :id')->execute([':s' => password_hash($nova, PASSWORD_DEFAULT), ':id' => $u['id']]);
            session_regenerate_id(true);
            flash('success', 'Senha alterada.');
        }
    }
    foreach ($erros as $er) flash('danger', $er);
    redirect('perfil.php');
}

$st = db()->prepare('select id, nome_fantasia from assistencias where cliente_id = :id');
$st->execute([':id' => $u['id']]);
$minha = $st->fetch();

$st = db()->prepare('select v.nota, v.comentario, v.created_at, a.id as aid, a.nome_fantasia from avaliacoes v join assistencias a on a.id = v.assistencia_id where v.cliente_id = :id order by v.updated_at desc limit 10');
$st->execute([':id' => $u['id']]);
$minhas_aval = $st->fetchAll();

$titulo = 'Perfil';
$ativo = 'perfil';
require __DIR__ . '/inc/topo.php';
?>
<main class="container pb-5">
    <div class="row g-4">
        <div class="col-md-6">
            <div class="ts-painel">
                <h1 class="h5 mb-3">Olá, <?= e(explode(' ', $u['nome'])[0]) ?></h1>
                <form method="post">
                    <?= csrf_campo() ?><input type="hidden" name="acao" value="dados">
                    <label class="form-label small mb-1">Nome</label>
                    <input class="form-control mb-2" name="nome" value="<?= e($u['nome']) ?>" required>
                    <label class="form-label small mb-1">E-mail</label>
                    <input class="form-control mb-2" type="email" name="email" value="<?= e($u['email']) ?>" required>
                    <label class="form-label small mb-1">Telefone</label>
                    <input class="form-control mb-3" type="tel" name="telefone" value="<?= e($u['telefone']) ?>" required>
                    <button class="btn ts-btn w-100">Salvar dados</button>
                </form>
            </div>
            <div class="ts-painel mt-4">
                <h2 class="h6 mb-3">Alterar senha</h2>
                <form method="post">
                    <?= csrf_campo() ?><input type="hidden" name="acao" value="senha">
                    <input class="form-control mb-2" type="password" name="atual" placeholder="Senha atual" required autocomplete="current-password">
                    <input class="form-control mb-3" type="password" name="nova" placeholder="Nova senha (mín. 8)" required minlength="8" autocomplete="new-password">
                    <button class="btn btn-outline-primary w-100">Alterar senha</button>
                </form>
            </div>
            <form method="post" action="sair.php" class="mt-4"><?= csrf_campo() ?><button class="btn btn-outline-danger w-100">Sair da conta</button></form>
        </div>
        <div class="col-md-6">
            <div class="ts-painel">
                <h2 class="h6 mb-3">Minha assistência</h2>
                <?php if ($minha): ?>
                    <p class="mb-2"><strong><?= e($minha['nome_fantasia']) ?></strong></p>
                    <a class="btn ts-btn btn-sm" href="assistencia.php?id=<?= (int) $minha['id'] ?>">Ver página</a>
                    <a class="btn btn-outline-primary btn-sm" href="assistencia_form.php">Editar</a>
                <?php else: ?>
                    <p class="small text-body-secondary">Você tem uma assistência técnica? Cadastre-a para aparecer no mapa e receber avaliações.</p>
                    <a class="btn ts-btn" href="assistencia_form.php">Cadastrar minha assistência</a>
                <?php endif; ?>
            </div>
            <div class="ts-painel mt-4">
                <h2 class="h6 mb-3">Minhas avaliações</h2>
                <?php if (!$minhas_aval): ?><p class="small text-body-secondary mb-0">Você ainda não avaliou nenhuma assistência.</p><?php endif; ?>
                <?php foreach ($minhas_aval as $v): ?>
                    <div class="mb-3">
                        <a href="assistencia.php?id=<?= (int) $v['aid'] ?>" class="fw-bold text-decoration-none"><?= e($v['nome_fantasia']) ?></a>
                        <div class="ts-estrelas"><?= estrelas((float) $v['nota']) ?></div>
                        <?php if ($v['comentario']): ?><div class="small"><?= e($v['comentario']) ?></div><?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</main>
<?php require __DIR__ . '/inc/rodape.php'; ?>

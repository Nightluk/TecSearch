<?php
require __DIR__ . '/inc/bootstrap.php';
require __DIR__ . '/inc/funcoes.php';

$id = (int) ($_GET['id'] ?? 0);
$a = buscar_assistencias(['id' => $id, 'limite' => 1])[0] ?? null;
if (!$a) { http_response_code(404); exit('Assistência não encontrada. <a href="index.php">Voltar</a>'); }

$u = usuario();
$dono = $u && (int) $a['cliente_id'] === (int) $u['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_validar();
    $u = exigir_login();
    $acao = $_POST['acao'] ?? '';

    if ($acao === 'avaliar') {
        $nota = (int) ($_POST['nota'] ?? 0);
        $coment = trim((string) ($_POST['comentario'] ?? ''));
        if ($dono) flash('danger', 'Você não pode avaliar a sua própria assistência.');
        elseif ($nota < 1 || $nota > 5) flash('danger', 'Escolha uma nota de 1 a 5.');
        elseif (mb_strlen($coment) > 500) flash('danger', 'O comentário pode ter até 500 caracteres.');
        else {
            $pdo = db();
            $st = $pdo->prepare('select id from avaliacoes where cliente_id = :c and assistencia_id = :a');
            $st->execute([':c' => $u['id'], ':a' => $id]);
            if ($st->fetchColumn()) {
                $pdo->prepare('update avaliacoes set nota = :n, comentario = :t, updated_at = now() where cliente_id = :c and assistencia_id = :a')
                    ->execute([':n' => $nota, ':t' => $coment ?: null, ':c' => $u['id'], ':a' => $id]);
            } else {
                $pdo->prepare('insert into avaliacoes (cliente_id, assistencia_id, nota, comentario) values (:c, :a, :n, :t)')
                    ->execute([':n' => $nota, ':t' => $coment ?: null, ':c' => $u['id'], ':a' => $id]);
            }
            flash('success', 'Avaliação salva. Obrigado!');
        }
    } elseif ($acao === 'excluir_avaliacao') {
        db()->prepare('delete from avaliacoes where cliente_id = :c and assistencia_id = :a')->execute([':c' => $u['id'], ':a' => $id]);
        flash('success', 'Avaliação removida.');
    }
    redirect('assistencia.php?id=' . $id . '#avaliacoes');
}

$cats = json_decode((string) $a['categorias'], true) ?: [];
$st = db()->prepare('select url_foto from assistencia_fotos where assistencia_id = :id order by id');
$st->execute([':id' => $id]);
$fotos = $st->fetchAll(PDO::FETCH_COLUMN);

$st = db()->prepare('select v.nota, v.comentario, v.created_at, v.cliente_id, c.nome from avaliacoes v join clientes c on c.id = v.cliente_id where v.assistencia_id = :id order by v.updated_at desc');
$st->execute([':id' => $id]);
$avals = $st->fetchAll();
$minha = null;
foreach ($avals as $v) if ($u && (int) $v['cliente_id'] === (int) $u['id']) $minha = $v;

$endereco = "{$a['logradouro']}, {$a['numero']} - {$a['bairro']}, {$a['cidade']}/{$a['estado']}";
$zap = so_digitos($a['telefone_whatsapp']);
if (strlen($zap) <= 11) $zap = '55' . $zap;
$ig = $a['instagram'] ? ltrim($a['instagram'], '@') : null;

$titulo = $a['nome_fantasia'];
$ativo = 'assistencias';
$leaflet = true;
require __DIR__ . '/inc/topo.php';
?>
<main class="container pb-5">
    <div class="row g-4">
        <div class="col-md-7">
            <div class="ts-galeria mb-3">
                <?php if ($fotos): ?>
                    <img src="<?= e($fotos[0]) ?>" alt="<?= e($a['nome_fantasia']) ?>" class="ts-foto-principal">
                    <?php if (count($fotos) > 1): ?><div class="d-flex gap-2 mt-2 flex-wrap"><?php foreach (array_slice($fotos, 1) as $f): ?><img src="<?= e($f) ?>" alt="" class="ts-foto-mini"><?php endforeach; ?></div><?php endif; ?>
                <?php else: ?><div class="ts-card-img ts-foto-principal"></div><?php endif; ?>
            </div>

            <div class="d-flex justify-content-between align-items-start gap-2">
                <h1 class="h4 fw-bold"><?= e($a['nome_fantasia']) ?></h1>
                <?php if ($dono): ?><a class="btn btn-sm btn-outline-primary" href="assistencia_form.php">Editar</a><?php endif; ?>
            </div>
            <div class="mb-2"><?php if ($a['qtd'] > 0): ?><span class="ts-estrelas"><?= estrelas((float) $a['nota']) ?></span> <?= fmt_nota((float) $a['nota']) ?> (<?= (int) $a['qtd'] ?> avaliações)<?php else: ?><span class="text-body-secondary">Sem avaliações ainda</span><?php endif; ?></div>
            <div class="ts-tags mb-3"><?php foreach ($cats as $c): ?><span class="ts-tag"><?= e($c) ?></span><?php endforeach; ?></div>
            <?php if ($a['descricao']): ?><p><?= nl2br(e($a['descricao'])) ?></p><?php endif; ?>

            <section id="avaliacoes" class="mt-4">
                <h2 class="h6 fw-bold">Avaliações</h2>
                <?php if ($dono): ?>
                    <p class="small text-body-secondary">Você é o(a) dono(a) desta assistência.</p>
                <?php elseif ($u): ?>
                    <form method="post" class="ts-painel mb-3">
                        <?= csrf_campo() ?><input type="hidden" name="acao" value="avaliar">
                        <div class="ts-nota-input mb-2" role="radiogroup" aria-label="Nota">
                            <?php for ($i = 5; $i >= 1; $i--): ?>
                                <input type="radio" id="n<?= $i ?>" name="nota" value="<?= $i ?>" <?= $minha && (int) $minha['nota'] === $i ? 'checked' : '' ?> required><label for="n<?= $i ?>" title="<?= $i ?> estrelas">★</label>
                            <?php endfor; ?>
                        </div>
                        <textarea class="form-control mb-2" name="comentario" rows="3" maxlength="500" placeholder="Conte como foi sua experiência (opcional)"><?= e($minha['comentario'] ?? '') ?></textarea>
                        <button class="btn ts-btn btn-sm"><?= $minha ? 'Atualizar avaliação' : 'Enviar avaliação' ?></button>
                    </form>
                    <?php if ($minha): ?>
                        <form method="post" class="mb-3" onsubmit="return confirm('Remover sua avaliação?')">
                            <?= csrf_campo() ?><input type="hidden" name="acao" value="excluir_avaliacao">
                            <button class="btn btn-link btn-sm text-danger p-0">Remover minha avaliação</button>
                        </form>
                    <?php endif; ?>
                <?php else: ?>
                    <p class="small"><a href="entrar.php?next=<?= urlencode('assistencia.php?id=' . $id) ?>">Entre</a> ou <a href="criarconta.php?next=<?= urlencode('assistencia.php?id=' . $id) ?>">crie uma conta</a> para avaliar.</p>
                <?php endif; ?>

                <?php foreach ($avals as $v): ?>
                    <div class="ts-avaliacao mb-3 align-items-start"><span class="ts-avatar"></span>
                        <div><strong><?= e($v['nome']) ?></strong> <span class="ts-estrelas"><?= estrelas((float) $v['nota']) ?></span>
                            <div class="text-body-secondary" style="font-size:.7rem"><?= e(date('d/m/Y', strtotime($v['created_at']))) ?></div>
                            <?php if ($v['comentario']): ?><div class="small"><?= nl2br(e($v['comentario'])) ?></div><?php endif; ?>
                        </div></div>
                <?php endforeach; ?>
            </section>
        </div>

        <aside class="col-md-5">
            <div class="ts-painel mb-3">
                <h2 class="h6 fw-bold">Contato</h2>
                <p class="small mb-2">📍 <?= e($endereco) ?><br><span class="text-body-secondary">CEP <?= e($a['cep']) ?></span></p>
                <a class="btn ts-btn w-100 mb-2" target="_blank" rel="noopener" href="https://wa.me/<?= e($zap) ?>">WhatsApp</a>
                <div class="small">
                    <?php if ($ig): ?>Instagram: <a target="_blank" rel="noopener" href="https://instagram.com/<?= e($ig) ?>">@<?= e($ig) ?></a><br><?php endif; ?>
                    <?php if ($a['facebook']): ?>Facebook: <a target="_blank" rel="noopener" href="<?= e(url_http($a['facebook']) ?? '#') ?>"><?= e($a['facebook']) ?></a><br><?php endif; ?>
                    <?php if ($a['site']): ?>Site: <a target="_blank" rel="noopener" href="<?= e(url_http($a['site']) ?? '#') ?>"><?= e($a['site']) ?></a><?php endif; ?>
                </div>
            </div>
            <div class="ts-mapa" style="min-height:260px"><div id="mapa-a" style="position:absolute;inset:0"></div></div>
            <a class="small d-block mt-2" target="_blank" rel="noopener" href="https://www.google.com/maps/dir/?api=1&destination=<?= $a['latitude'] ?>,<?= $a['longitude'] ?>">Como chegar →</a>
        </aside>
    </div>
</main>
<?php
$scripts = '<script src="js/mapa.js"></script><script>(function(){const m=TSMapa.base("mapa-a",[' . $a['latitude'] . ',' . $a['longitude'] . '],16);L.marker([' . $a['latitude'] . ',' . $a['longitude'] . ']).addTo(m);})();</script>';
require __DIR__ . '/inc/rodape.php';

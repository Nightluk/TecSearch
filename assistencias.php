<?php
require __DIR__ . '/inc/bootstrap.php';
require __DIR__ . '/inc/funcoes.php';

$q = trim((string) ($_GET['q'] ?? ''));
$cat = (int) ($_GET['categoria'] ?? 0);
$ordem = in_array($_GET['ordem'] ?? '', ['nota', 'recentes'], true) ? $_GET['ordem'] : 'nota';
$lista = buscar_assistencias(['q' => $q, 'categoria' => $cat, 'ordem' => $ordem, 'limite' => 60]);
$cats = categorias();

$titulo = 'Assistências';
$ativo = 'assistencias';
require __DIR__ . '/inc/topo.php';
?>
<main class="container pb-5 ts-secao">
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <h1 class="h5 fw-bold m-0">Assistências<?= $q ? ' para “' . e($q) . '”' : '' ?></h1>
        <a class="btn btn-sm ts-btn" href="assistencia_form.php">+ Cadastrar assistência</a>
    </div>
    <form class="row g-2 mb-4" method="get">
        <div class="col-12 col-md-5"><input class="form-control" type="search" name="q" value="<?= e($q) ?>" placeholder="Nome, bairro ou cidade"></div>
        <div class="col-6 col-md-3">
            <select name="categoria" class="form-select">
                <option value="0">Todos os aparelhos</option>
                <?php foreach ($cats as $c): ?><option value="<?= (int) $c['id'] ?>" <?= $cat === (int) $c['id'] ? 'selected' : '' ?>><?= e($c['nome']) ?></option><?php endforeach; ?>
            </select>
        </div>
        <div class="col-6 col-md-2">
            <select name="ordem" class="form-select">
                <option value="nota" <?= $ordem === 'nota' ? 'selected' : '' ?>>Melhor avaliadas</option>
                <option value="recentes" <?= $ordem === 'recentes' ? 'selected' : '' ?>>Mais recentes</option>
            </select>
        </div>
        <div class="col-12 col-md-2"><button class="btn ts-btn w-100">Filtrar</button></div>
    </form>
    <div class="row g-4">
        <?php foreach ($lista as $a): ?><div class="col-12 col-md-6 col-lg-4"><?= card_assistencia($a) ?></div><?php endforeach; ?>
        <?php if (!$lista): ?><p class="text-body-secondary">Nenhuma assistência encontrada.</p><?php endif; ?>
    </div>
</main>
<?php require __DIR__ . '/inc/rodape.php'; ?>

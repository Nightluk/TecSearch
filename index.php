<?php
require __DIR__ . '/inc/bootstrap.php';
require __DIR__ . '/inc/funcoes.php';

$destaques = buscar_assistencias(['ordem' => 'nota', 'limite' => 3]);
$avaliacoes = db()->query(
    "select v.nota, v.comentario, v.created_at, c.nome as autor, a.id as aid, a.nome_fantasia, a.bairro
     from avaliacoes v join clientes c on c.id = v.cliente_id join assistencias a on a.id = v.assistencia_id
     where v.comentario is not null and v.comentario <> ''
     order by v.nota desc, v.created_at desc limit 3"
)->fetchAll();

$titulo = 'Início';
$ativo = 'inicio';
$leaflet = true;
require __DIR__ . '/inc/topo.php';
?>
    <header class="ts-hero">
        <div class="container">
            <div class="row align-items-center g-0">
                <div class="col-md-6 py-5 pe-md-4">
                    <h1 class="h2">Encontre a assistência <span>técnica ideal</span> para o seu aparelho!</h1>
                    <p class="small my-3">Mais praticidade, segurança e confiança na hora de consertar o que é importante para você.</p>
                    <form action="assistencias.php" method="get" class="ts-busca">
                        <span aria-hidden="true">🔍</span>
                        <input type="search" name="q" placeholder="Busque por assistência, aparelho ou bairro" aria-label="Buscar">
                    </form>
                </div>
                <div class="col-md-6 d-none d-md-block align-self-stretch"><div class="ts-hero-img"></div></div>
            </div>
        </div>
    </header>

    <main class="container py-4">
        <section class="ts-secao mb-5">
            <div class="d-flex justify-content-between align-items-baseline mb-3">
                <h2>Assistências em destaque:</h2>
                <a class="ver-mais" href="assistencias.php">Ver mais →</a>
            </div>
            <div class="row g-4">
                <?php foreach ($destaques as $a): ?><div class="col-12 col-md-4"><?= card_assistencia($a) ?></div><?php endforeach; ?>
                <?php if (!$destaques): ?>
                    <p class="text-body-secondary">Ainda não há assistências cadastradas. <a href="assistencia_form.php">Cadastre a primeira!</a></p>
                <?php endif; ?>
            </div>
        </section>

        <section class="ts-secao mb-5">
            <div class="d-flex justify-content-between align-items-baseline mb-3">
                <h2>Assistências próximas de você:</h2>
                <button id="btn-perto" class="btn btn-sm ts-btn">Usar minha localização</button>
            </div>
            <div class="row g-4" id="proximas"><p class="small text-body-secondary">Ative sua localização para ver as assistências mais perto de você.</p></div>
        </section>

        <section class="ts-secao mb-4">
            <div class="d-flex justify-content-between align-items-baseline mb-3">
                <h2>Avaliações em destaque:</h2>
            </div>
            <div class="row g-4">
                <div class="col-12 col-md-5">
                    <?php foreach ($avaliacoes as $v): ?>
                        <article class="ts-card mb-3"><div class="ts-card-body">
                            <a href="assistencia.php?id=<?= (int) $v['aid'] ?>" class="ts-card-nome text-decoration-none text-reset"><?= e($v['nome_fantasia']) ?> · <?= e($v['bairro']) ?></a>
                            <div class="ts-estrelas"><?= estrelas((float) $v['nota']) ?></div>
                            <div class="ts-avaliacao"><span class="ts-avatar"></span>
                                <div><div class="text-body-secondary"><?= e($v['autor']) ?></div><div class="ts-aval-texto">“<?= e($v['comentario']) ?>”</div></div>
                            </div>
                        </div></article>
                    <?php endforeach; ?>
                    <?php if (!$avaliacoes): ?><p class="text-body-secondary small">As avaliações aparecerão aqui.</p><?php endif; ?>
                </div>
                <div class="col-12 col-md-7">
                    <div class="ts-mapa"><div id="mapa-mini" style="position:absolute;inset:0"></div>
                        <a href="mapa.php" class="btn ts-btn" style="z-index:500">Ver no mapa</a></div>
                </div>
            </div>
        </section>
    </main>
<?php
$scripts = <<<'JS'
<script src="js/mapa.js"></script>
<script>
TSMapa.iniciar('mapa-mini', { params: { limite: 50 } });
document.getElementById('btn-perto').addEventListener('click', () => {
    if (!navigator.geolocation) return alert('Seu navegador não suporta geolocalização.');
    navigator.geolocation.getCurrentPosition(async p => {
        const qs = new URLSearchParams({ formato: 'html', ordem: 'distancia', limite: 3, lat: p.coords.latitude, lng: p.coords.longitude });
        const html = await (await fetch('api_assistencias.php?' + qs)).text();
        document.getElementById('proximas').innerHTML = html || '<p class="small text-body-secondary">Nenhuma assistência encontrada.</p>';
    }, () => alert('Não foi possível obter sua localização.'));
});
</script>
JS;
require __DIR__ . '/inc/rodape.php';

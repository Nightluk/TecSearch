<?php
require __DIR__ . '/inc/bootstrap.php';
require __DIR__ . '/inc/funcoes.php';
$cats = categorias();
$cat = (int) ($_GET['categoria'] ?? 0);
$titulo = 'Mapa';
$ativo = 'mapa';
$leaflet = true;
require __DIR__ . '/inc/topo.php';
?>
<main class="container-fluid px-0 ts-mapa-pagina">
    <div class="ts-mapa-barra">
        <select id="f-cat" class="form-select form-select-sm">
            <option value="0">Todos os aparelhos</option>
            <?php foreach ($cats as $c): ?><option value="<?= (int) $c['id'] ?>" <?= $cat === (int) $c['id'] ? 'selected' : '' ?>><?= e($c['nome']) ?></option><?php endforeach; ?>
        </select>
        <button id="btn-loc" class="btn btn-sm ts-btn text-nowrap">📍 Perto de mim</button>
    </div>
    <div id="mapa" class="ts-mapa-grande"></div>
</main>
<?php
$scripts = <<<'JS'
<script src="js/mapa.js"></script>
<script>
(async () => {
    const map = TSMapa.base('mapa');
    let grupo = null;
    async function carregar() {
        const qs = new URLSearchParams({ categoria: document.getElementById('f-cat').value, limite: 100 });
        const lista = await (await fetch('api_assistencias.php?' + qs)).json();
        if (grupo) grupo.remove();
        grupo = TSMapa.marcadores(map, lista);
        if (lista.length) map.fitBounds(grupo.getBounds().pad(0.2), { maxZoom: 15 });
    }
    document.getElementById('f-cat').addEventListener('change', carregar);
    document.getElementById('btn-loc').addEventListener('click', () => TSMapa.minhaLocalizacao(map));
    carregar();
})();
</script>
JS;
require __DIR__ . '/inc/rodape.php';

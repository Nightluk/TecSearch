<?php
// Cria ou edita a assistência do usuário logado (1 por conta, conforme o banco: assistencias.cliente_id é UNIQUE).
require __DIR__ . '/inc/bootstrap.php';
require __DIR__ . '/inc/funcoes.php';
$u = exigir_login();

$st = db()->prepare('select * from assistencias where cliente_id = :c');
$st->execute([':c' => $u['id']]);
$atual = $st->fetch() ?: null;
$editando = $atual !== null;

$todas = categorias();
$d = $atual ?: ['nome_fantasia' => '', 'descricao' => '', 'telefone_whatsapp' => '', 'instagram' => '', 'facebook' => '', 'site' => '',
    'cep' => '', 'logradouro' => '', 'numero' => '', 'bairro' => '', 'cidade' => '', 'estado' => '', 'latitude' => '', 'longitude' => ''];
$sel = [];
$fotos = [];
if ($editando) {
    $x = db()->prepare('select categoria_id from assistencia_categorias where assistencia_id = :id');
    $x->execute([':id' => $atual['id']]);
    $sel = array_map('intval', $x->fetchAll(PDO::FETCH_COLUMN));
    $x = db()->prepare('select url_foto from assistencia_fotos where assistencia_id = :id order by id');
    $x->execute([':id' => $atual['id']]);
    $fotos = $x->fetchAll(PDO::FETCH_COLUMN);
}
$erros = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_validar();

    if (($_POST['acao'] ?? '') === 'excluir' && $editando) {
        $pdo = db();
        $pdo->beginTransaction();
        foreach (['avaliacoes', 'assistencia_fotos', 'assistencia_categorias'] as $t) {
            $pdo->prepare("delete from $t where assistencia_id = :id")->execute([':id' => $atual['id']]);
        }
        $pdo->prepare('delete from assistencias where id = :id and cliente_id = :c')->execute([':id' => $atual['id'], ':c' => $u['id']]);
        $pdo->commit();
        flash('success', 'Assistência excluída.');
        redirect('perfil.php');
    }

    $d = [
        'nome_fantasia'     => trim($_POST['nome_fantasia'] ?? ''),
        'descricao'         => trim($_POST['descricao'] ?? ''),
        'telefone_whatsapp' => so_digitos($_POST['telefone_whatsapp'] ?? ''),
        'instagram'         => ltrim(trim($_POST['instagram'] ?? ''), '@'),
        'facebook'          => trim($_POST['facebook'] ?? ''),
        'site'              => trim($_POST['site'] ?? ''),
        'cep'               => so_digitos($_POST['cep'] ?? ''),
        'logradouro'        => trim($_POST['logradouro'] ?? ''),
        'numero'            => trim($_POST['numero'] ?? ''),
        'bairro'            => trim($_POST['bairro'] ?? ''),
        'cidade'            => trim($_POST['cidade'] ?? ''),
        'estado'            => strtoupper(trim($_POST['estado'] ?? '')),
        'latitude'          => trim($_POST['latitude'] ?? ''),
        'longitude'         => trim($_POST['longitude'] ?? ''),
    ];
    $sel = array_values(array_intersect(array_map('intval', (array) ($_POST['categorias'] ?? [])), array_column($todas, 'id')));
    $fotos = [];
    foreach (preg_split('/\R/', (string) ($_POST['fotos'] ?? '')) as $l) {
        if (trim($l) === '') continue;
        $url = url_http($l);
        if ($url) $fotos[] = $url; else $erros[] = 'Link de foto inválido: ' . trim($l);
    }
    $fotos = array_slice(array_unique($fotos), 0, 6);

    if (mb_strlen($d['nome_fantasia']) < 2 || mb_strlen($d['nome_fantasia']) > 120) $erros[] = 'Informe o nome da assistência.';
    if (mb_strlen($d['descricao']) > 1000) $erros[] = 'Descrição muito longa (máx. 1000).';
    if (!preg_match('/^\d{10,13}$/', $d['telefone_whatsapp'])) $erros[] = 'WhatsApp inválido (DDD + número).';
    if (!preg_match('/^\d{8}$/', $d['cep'])) $erros[] = 'CEP inválido.';
    foreach (['logradouro' => 'Rua', 'numero' => 'Número', 'bairro' => 'Bairro', 'cidade' => 'Cidade'] as $k => $r) if ($d[$k] === '') $erros[] = "$r é obrigatório.";
    if (!in_array($d['estado'], UFS, true)) $erros[] = 'Estado (UF) inválido.';
    if ($d['site'] !== '' && !($d['site'] = url_http($d['site']) ?? '')) $erros[] = 'Site inválido.';
    if ($d['facebook'] !== '' && !($d['facebook'] = url_http($d['facebook']) ?? '')) $erros[] = 'Link do Facebook inválido.';
    $lat = filter_var($d['latitude'], FILTER_VALIDATE_FLOAT);
    $lng = filter_var($d['longitude'], FILTER_VALIDATE_FLOAT);
    if ($lat === false || $lng === false || abs($lat) > 90 || abs($lng) > 180) $erros[] = 'Marque a localização no mapa.';
    if (!$sel) $erros[] = 'Escolha ao menos um tipo de aparelho.';

    if (!$erros) {
        $pdo = db();
        $pdo->beginTransaction();
        $p = [':n' => $d['nome_fantasia'], ':d' => $d['descricao'] ?: null, ':w' => $d['telefone_whatsapp'], ':i' => $d['instagram'] ?: null,
              ':f' => $d['facebook'] ?: null, ':s' => $d['site'] ?: null, ':cep' => $d['cep'], ':lo' => $d['logradouro'], ':nu' => $d['numero'],
              ':b' => $d['bairro'], ':ci' => $d['cidade'], ':uf' => $d['estado'], ':la' => $lat, ':ln' => $lng];
        if ($editando) {
            $pdo->prepare('update assistencias set nome_fantasia=:n, descricao=:d, telefone_whatsapp=:w, instagram=:i, facebook=:f, site=:s, cep=:cep,
                logradouro=:lo, numero=:nu, bairro=:b, cidade=:ci, estado=:uf, latitude=:la, longitude=:ln where id=:id and cliente_id=:c')
                ->execute($p + [':id' => $atual['id'], ':c' => $u['id']]);
            $aid = (int) $atual['id'];
            $pdo->prepare('delete from assistencia_categorias where assistencia_id = :id')->execute([':id' => $aid]);
            $pdo->prepare('delete from assistencia_fotos where assistencia_id = :id')->execute([':id' => $aid]);
        } else {
            $ins = $pdo->prepare('insert into assistencias (cliente_id, nome_fantasia, descricao, telefone_whatsapp, instagram, facebook, site, cep,
                logradouro, numero, bairro, cidade, estado, latitude, longitude) values (:c, :n, :d, :w, :i, :f, :s, :cep, :lo, :nu, :b, :ci, :uf, :la, :ln) returning id');
            $ins->execute($p + [':c' => $u['id']]);
            $aid = (int) $ins->fetchColumn();
        }
        $ic = $pdo->prepare('insert into assistencia_categorias (assistencia_id, categoria_id) values (:a, :c)');
        foreach ($sel as $cid) $ic->execute([':a' => $aid, ':c' => $cid]);
        $if = $pdo->prepare('insert into assistencia_fotos (assistencia_id, url_foto) values (:a, :u)');
        foreach ($fotos as $url) $if->execute([':a' => $aid, ':u' => $url]);
        $pdo->commit();
        flash('success', $editando ? 'Assistência atualizada.' : 'Assistência cadastrada!');
        redirect('assistencia.php?id=' . $aid);
    }
}

$titulo = $editando ? 'Editar assistência' : 'Cadastrar assistência';
$ativo = 'perfil';
$leaflet = true;
require __DIR__ . '/inc/topo.php';
$v = fn($k) => e((string) ($d[$k] ?? ''));
?>
<main class="container pb-5" style="max-width: 860px;">
    <h1 class="h4 fw-bold mb-3"><?= e($titulo) ?></h1>
    <?php foreach ($erros as $er): ?><div class="alert alert-danger py-2 mb-2"><?= e($er) ?></div><?php endforeach; ?>

    <form method="post" novalidate class="ts-painel">
        <?= csrf_campo() ?>
        <div class="row g-3">
            <div class="col-12"><label class="form-label small">Nome da assistência *</label><input class="form-control" name="nome_fantasia" value="<?= $v('nome_fantasia') ?>" required maxlength="120"></div>
            <div class="col-12"><label class="form-label small">Descrição</label><textarea class="form-control" name="descricao" rows="3" maxlength="1000"><?= $v('descricao') ?></textarea></div>

            <div class="col-12"><label class="form-label small d-block">Aparelhos atendidos *</label>
                <?php foreach ($todas as $c): ?>
                    <div class="form-check form-check-inline"><input class="form-check-input" type="checkbox" name="categorias[]" id="c<?= (int) $c['id'] ?>" value="<?= (int) $c['id'] ?>" <?= in_array((int) $c['id'], $sel, true) ? 'checked' : '' ?>><label class="form-check-label" for="c<?= (int) $c['id'] ?>"><?= e($c['nome']) ?></label></div>
                <?php endforeach; ?>
            </div>

            <div class="col-md-6"><label class="form-label small">WhatsApp (com DDD) *</label><input class="form-control" type="tel" name="telefone_whatsapp" value="<?= $v('telefone_whatsapp') ?>" required></div>
            <div class="col-md-6"><label class="form-label small">Instagram</label><input class="form-control" name="instagram" value="<?= $v('instagram') ?>" placeholder="@suaassistencia"></div>
            <div class="col-md-6"><label class="form-label small">Facebook (link)</label><input class="form-control" name="facebook" value="<?= $v('facebook') ?>"></div>
            <div class="col-md-6"><label class="form-label small">Site</label><input class="form-control" name="site" value="<?= $v('site') ?>"></div>

            <div class="col-md-4"><label class="form-label small">CEP *</label>
                <div class="input-group"><input class="form-control" id="cep" name="cep" value="<?= $v('cep') ?>" maxlength="9" required><button type="button" class="btn btn-outline-secondary" id="btn-cep">Buscar</button></div></div>
            <div class="col-md-8"><label class="form-label small">Rua *</label><input class="form-control" id="logradouro" name="logradouro" value="<?= $v('logradouro') ?>" required></div>
            <div class="col-md-3"><label class="form-label small">Número *</label><input class="form-control" id="numero" name="numero" value="<?= $v('numero') ?>" required></div>
            <div class="col-md-4"><label class="form-label small">Bairro *</label><input class="form-control" id="bairro" name="bairro" value="<?= $v('bairro') ?>" required></div>
            <div class="col-md-3"><label class="form-label small">Cidade *</label><input class="form-control" id="cidade" name="cidade" value="<?= $v('cidade') ?>" required></div>
            <div class="col-md-2"><label class="form-label small">UF *</label><input class="form-control" id="estado" name="estado" value="<?= $v('estado') ?>" maxlength="2" required></div>

            <div class="col-12">
                <label class="form-label small">Localização no mapa * <span class="text-body-secondary">(clique no mapa ou arraste o marcador)</span></label>
                <div class="mb-2"><button type="button" class="btn btn-sm btn-outline-secondary" id="btn-localizar">Localizar pelo endereço</button></div>
                <div id="mapa-form" class="ts-mapa" style="min-height:320px"></div>
                <input type="hidden" name="latitude" id="latitude" value="<?= $v('latitude') ?>">
                <input type="hidden" name="longitude" id="longitude" value="<?= $v('longitude') ?>">
            </div>

            <div class="col-12"><label class="form-label small">Fotos (um link por linha, até 6)</label>
                <textarea class="form-control" name="fotos" rows="3" placeholder="https://..."><?= e(implode("\n", $fotos)) ?></textarea></div>
        </div>
        <div class="d-flex gap-2 mt-4">
            <button class="btn ts-btn px-4"><?= $editando ? 'Salvar alterações' : 'Cadastrar' ?></button>
            <a class="btn btn-outline-secondary" href="perfil.php">Cancelar</a>
        </div>
    </form>

    <?php if ($editando): ?>
        <form method="post" class="mt-3" onsubmit="return confirm('Excluir a assistência e todas as avaliações dela? Isso não pode ser desfeito.')">
            <?= csrf_campo() ?><input type="hidden" name="acao" value="excluir">
            <button class="btn btn-outline-danger btn-sm">Excluir assistência</button>
        </form>
    <?php endif; ?>
</main>
<?php
$scripts = <<<'JS'
<script src="js/mapa.js"></script>
<script>
(function () {
    const $ = id => document.getElementById(id);
    const lat0 = parseFloat($('latitude').value), lng0 = parseFloat($('longitude').value);
    const tem = !isNaN(lat0) && !isNaN(lng0);
    const map = TSMapa.base('mapa-form', tem ? [lat0, lng0] : [-23.55, -46.63], tem ? 17 : 11);
    let marker = null;
    function por(ll) {
        $('latitude').value = ll.lat.toFixed(6); $('longitude').value = ll.lng.toFixed(6);
        if (!marker) { marker = L.marker(ll, { draggable: true }).addTo(map); marker.on('dragend', () => por(marker.getLatLng())); }
        else marker.setLatLng(ll);
    }
    if (tem) por({ lat: lat0, lng: lng0 });
    map.on('click', e => por(e.latlng));

    $('btn-cep').addEventListener('click', async () => {
        const cep = $('cep').value.replace(/\D/g, '');
        if (cep.length !== 8) return alert('CEP inválido.');
        try {
            const r = await (await fetch('https://viacep.com.br/ws/' + cep + '/json/')).json();
            if (r.erro) return alert('CEP não encontrado.');
            $('logradouro').value = r.logradouro || ''; $('bairro').value = r.bairro || ''; $('cidade').value = r.localidade || ''; $('estado').value = r.uf || '';
            $('btn-localizar').click();
        } catch (e) { alert('Não foi possível consultar o CEP.'); }
    });

    $('btn-localizar').addEventListener('click', async () => {
        const q = [$('logradouro').value, $('numero').value, $('bairro').value, $('cidade').value, $('estado').value, 'Brasil'].filter(Boolean).join(', ');
        try {
            let r = await (await fetch('https://nominatim.openstreetmap.org/search?format=json&limit=1&q=' + encodeURIComponent(q))).json();
            if (!r.length) r = await (await fetch('https://nominatim.openstreetmap.org/search?format=json&limit=1&q=' + encodeURIComponent([$('cidade').value, $('estado').value, 'Brasil'].join(', ')))).json();
            if (!r.length) return alert('Endereço não localizado. Clique no mapa para marcar.');
            const ll = { lat: parseFloat(r[0].lat), lng: parseFloat(r[0].lon) };
            por(ll); map.setView(ll, 17);
        } catch (e) { alert('Não foi possível localizar. Clique no mapa para marcar.'); }
    });
})();
</script>
JS;
require __DIR__ . '/inc/rodape.php';

<?php
// JSON (mapa) ou HTML de cards (seção "Próximas de você").
// GET: q, categoria, lat, lng, ordem, limite, formato=html|json
require __DIR__ . '/inc/bootstrap.php';
require __DIR__ . '/inc/funcoes.php';

$f = [
    'q'         => trim((string) ($_GET['q'] ?? '')),
    'categoria' => (int) ($_GET['categoria'] ?? 0),
    'limite'    => (int) ($_GET['limite'] ?? 100),
    'ordem'     => in_array($_GET['ordem'] ?? '', ['nota', 'distancia', 'recentes'], true) ? $_GET['ordem'] : 'nota',
];
$lat = filter_var($_GET['lat'] ?? null, FILTER_VALIDATE_FLOAT);
$lng = filter_var($_GET['lng'] ?? null, FILTER_VALIDATE_FLOAT);
if ($lat !== false && $lng !== false && abs($lat) <= 90 && abs($lng) <= 180) {
    $f['lat'] = $lat;
    $f['lng'] = $lng;
}
$lista = buscar_assistencias($f);

if (($_GET['formato'] ?? 'json') === 'html') {
    header('Content-Type: text/html; charset=utf-8');
    foreach ($lista as $a) echo '<div class="col-12 col-md-4">' . card_assistencia($a) . '</div>';
    exit;
}

header('Content-Type: application/json; charset=utf-8');
echo json_encode(array_map(fn($a) => [
    'id' => (int) $a['id'], 'nome' => $a['nome_fantasia'], 'lat' => $a['latitude'], 'lng' => $a['longitude'],
    'nota' => $a['nota'], 'qtd' => $a['qtd'], 'bairro' => $a['bairro'], 'cidade' => $a['cidade'],
    'distancia_km' => $a['distancia_km'], 'categorias' => json_decode((string) $a['categorias'], true),
], $lista), JSON_UNESCAPED_UNICODE);

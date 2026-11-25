<?php
declare(strict_types=1);

function estrelas(float $nota): string
{
    $c = (int) round($nota);
    return str_repeat('★', $c) . str_repeat('☆', 5 - $c);
}

function fmt_nota(float $n): string
{
    return number_format($n, 1, ',', '');
}

function fmt_km(?float $km): string
{
    if ($km === null) return '';
    return $km < 1 ? number_format($km * 1000, 0, ',', '.') . ' m' : number_format($km, 1, ',', '.') . ' km';
}

function url_http(?string $u): ?string
{
    $u = trim((string) $u);
    if ($u === '') return null;
    if (!preg_match('#^https?://#i', $u)) $u = 'https://' . $u;
    return filter_var($u, FILTER_VALIDATE_URL) && preg_match('#^https?://#i', $u) ? $u : null;
}

/**
 * Busca assistências com nota média, fotos, categorias e (opcional) distância.
 * Filtros: q, categoria (id), lat, lng, ordem (nota|distancia|recentes), limite, id
 */
function buscar_assistencias(array $f = []): array
{
    $params = [];
    $dist = 'null::float8';
    if (isset($f['lat'], $f['lng'])) {
        $dist = '6371 * acos(least(1, cos(radians(CAST(:lat AS float8))) * cos(radians(a.latitude)) * cos(radians(a.longitude) - radians(CAST(:lng AS float8))) + sin(radians(CAST(:lat AS float8))) * sin(radians(a.latitude))))';
        $params[':lat'] = (float) $f['lat'];
        $params[':lng'] = (float) $f['lng'];
    }
    $where = ['1=1'];
    if (!empty($f['id'])) { $where[] = 'a.id = :id'; $params[':id'] = (int) $f['id']; }
    if (!empty($f['q'])) {
        $where[] = '(a.nome_fantasia ilike :q or a.bairro ilike :q or a.cidade ilike :q or a.descricao ilike :q)';
        $params[':q'] = '%' . addcslashes((string) $f['q'], '%_\\') . '%';
    }
    if (!empty($f['categoria'])) {
        $where[] = 'exists (select 1 from assistencia_categorias x where x.assistencia_id = a.id and x.categoria_id = :cat)';
        $params[':cat'] = (int) $f['categoria'];
    }
    $ordem = match ($f['ordem'] ?? 'nota') {
        'distancia' => isset($f['lat']) ? 'distancia_km asc, nota desc' : 'nota desc',
        'recentes'  => 'a.created_at desc',
        default     => 'nota desc, qtd desc, a.id',
    };
    $limite = max(1, min(100, (int) ($f['limite'] ?? 24)));
    $sql = "select a.id, a.cliente_id, a.nome_fantasia, a.descricao, a.telefone_whatsapp, a.instagram, a.facebook, a.site,
                   a.cep, a.logradouro, a.numero, a.bairro, a.cidade, a.estado, a.latitude::float8 as latitude, a.longitude::float8 as longitude,
                   coalesce(round(avg(v.nota), 1), 0)::float8 as nota, count(v.id)::int as qtd,
                   (select url_foto from assistencia_fotos p where p.assistencia_id = a.id order by p.id limit 1) as foto,
                   (select coalesce(json_agg(c.nome order by c.nome), '[]') from assistencia_categorias ac
                      join categorias_aparelhos c on c.id = ac.categoria_id where ac.assistencia_id = a.id) as categorias,
                   $dist as distancia_km
            from assistencias a left join avaliacoes v on v.assistencia_id = a.id
            where " . implode(' and ', $where) . "
            group by a.id order by $ordem limit $limite";
    $st = db()->prepare($sql);
    $st->execute($params);
    $rows = $st->fetchAll();
    foreach ($rows as &$r) { // o PDO devolve numéricos como string; normaliza
        foreach (['latitude', 'longitude', 'nota'] as $k) $r[$k] = (float) $r[$k];
        $r['qtd'] = (int) $r['qtd'];
        $r['distancia_km'] = $r['distancia_km'] === null ? null : (float) $r['distancia_km'];
    }
    return $rows;
}

function categorias(): array
{
    return db()->query('select id, nome from categorias_aparelhos order by nome')->fetchAll();
}

/** Card de assistência (usado na home, lista e API html). */
function card_assistencia(array $a): string
{
    $cats = is_array($a['categorias']) ? $a['categorias'] : (json_decode((string) $a['categorias'], true) ?: []);
    $img = $a['foto'] ? ' style="background-image:url(\'' . e($a['foto']) . '\')"' : '';
    ob_start(); ?>
    <article class="ts-card">
        <a href="assistencia.php?id=<?= (int) $a['id'] ?>" class="ts-card-img d-block"<?= $img ?> aria-label="<?= e($a['nome_fantasia']) ?>"></a>
        <div class="ts-card-body">
            <h3 class="ts-card-nome"><?= e($a['nome_fantasia']) ?></h3>
            <div class="ts-meta">
                <span><?php if ($a['qtd'] > 0): ?><span class="ts-estrelas"><?= estrelas((float) $a['nota']) ?></span> <?= fmt_nota((float) $a['nota']) ?> (<?= (int) $a['qtd'] ?>)<?php else: ?><span class="text-body-secondary">Sem avaliações</span><?php endif; ?></span>
                <span>📍 <?= $a['distancia_km'] !== null ? e(fmt_km((float) $a['distancia_km'])) : e($a['bairro']) ?></span>
            </div>
            <div class="ts-tags"><?php foreach (array_slice($cats, 0, 6) as $c): ?><span class="ts-tag"><?= e($c) ?></span><?php endforeach; ?></div>
            <a href="assistencia.php?id=<?= (int) $a['id'] ?>" class="btn ts-btn">Conhecer assistência</a>
        </div>
    </article>
    <?php
    return ob_get_clean();
}

const UFS = ['AC','AL','AP','AM','BA','CE','DF','ES','GO','MA','MT','MS','MG','PA','PB','PR','PE','PI','RJ','RN','RS','RO','RR','SC','SP','SE','TO'];

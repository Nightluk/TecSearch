<?php
/** Variáveis esperadas: $titulo, $ativo ('inicio'|'assistencias'|'mapa'|'perfil'), $leaflet (bool) */
require_once __DIR__ . '/funcoes.php';
$u = usuario();
$titulo = $titulo ?? 'TecSearch';
$ativo = $ativo ?? '';
$flashes = $_SESSION['flash'] ?? [];
unset($_SESSION['flash']);
?>
<!doctype html>
<html lang="pt-BR">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($titulo) ?> - TecSearch</title>
    <link rel="icon" type="image/svg+xml" href="Assets/icons/favicon.svg">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <?php if (!empty($leaflet)): ?><link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/leaflet@1.9.4/dist/leaflet.css"><?php endif; ?>
    <link rel="stylesheet" href="estilo.css">
</head>

<body class="ts-body">
    <nav class="navbar navbar-expand-md ts-nav">
        <div class="container">
            <a class="navbar-brand" href="index.php"><img src="Assets/icons/tecsearch-logo-branca.svg" alt="TecSearch" height="34"></a>
            <form action="assistencias.php" method="get" class="ts-busca d-md-none flex-grow-1 ms-2">
                <input type="search" name="q" placeholder="Pesquisa" aria-label="Pesquisar">
            </form>
            <ul class="navbar-nav ms-auto flex-row align-items-center gap-3 d-none d-md-flex">
                <li class="nav-item"><a class="nav-link <?= $ativo === 'inicio' ? 'active' : '' ?>" href="index.php">Início</a></li>
                <li class="nav-item"><a class="nav-link <?= $ativo === 'assistencias' ? 'active' : '' ?>" href="assistencias.php">Assistências</a></li>
                <li class="nav-item"><a class="nav-link <?= $ativo === 'mapa' ? 'active' : '' ?>" href="mapa.php">Mapa</a></li>
                <?php if ($u): ?>
                    <li class="nav-item"><a class="nav-link <?= $ativo === 'perfil' ? 'active' : '' ?>" href="perfil.php">Perfil</a></li>
                <?php else: ?>
                    <li class="nav-item"><a class="btn btn-sm ts-btn px-3" href="entrar.php">Fazer login</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </nav>

    <?php if ($flashes): ?>
    <div class="container mt-3">
        <?php foreach ($flashes as [$tipo, $msg]): ?>
            <div class="alert alert-<?= e($tipo) ?> py-2" role="alert"><?= e($msg) ?></div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

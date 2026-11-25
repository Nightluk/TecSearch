<?php
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
    <link rel="stylesheet" href="estilo.css">
</head>

<body class="d-flex align-items-center py-4 bg-body-tertiary min-vh-100">
    <main class="form-signin w-100 m-auto px-3" style="max-width: 380px;">
        <a href="index.php"><img class="mb-3" src="Assets/icons/tecsearch-logo.svg" alt="TecSearch" width="260"></a>
        <?php foreach ($flashes as [$tipo, $msg]): ?>
            <div class="alert alert-<?= e($tipo) ?> py-2" role="alert"><?= e($msg) ?></div>
        <?php endforeach; ?>

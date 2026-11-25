    <footer class="ts-footer py-3 text-center d-none d-md-block">© <?= date('Y') ?> TecSearch</footer>

    <nav class="ts-bottom d-md-none" aria-label="Navegação">
        <a href="index.php" class="<?= ($ativo ?? '') === 'inicio' ? 'on' : '' ?>"><span>🏠</span>Início</a>
        <a href="assistencias.php" class="<?= ($ativo ?? '') === 'assistencias' ? 'on' : '' ?>"><span>📍</span>Assistências</a>
        <a href="mapa.php" class="<?= ($ativo ?? '') === 'mapa' ? 'on' : '' ?>"><span>🗺️</span>Mapa</a>
        <a href="<?= usuario() ? 'perfil.php' : 'entrar.php' ?>" class="<?= ($ativo ?? '') === 'perfil' ? 'on' : '' ?>"><span>👤</span>Perfil</a>
    </nav>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <?php if (!empty($leaflet)): ?><script src="https://cdn.jsdelivr.net/npm/leaflet@1.9.4/dist/leaflet.js"></script><?php endif; ?>
    <?= $scripts ?? '' ?>
</body>

</html>

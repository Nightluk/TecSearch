// Mapa interativo (Leaflet + OpenStreetMap). Uso: TSMapa.iniciar('id-do-div', { ... })
window.TSMapa = (function () {
    const esc = s => String(s).replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));

    function base(id, centro, zoom) {
        const map = L.map(id).setView(centro || [-14.2, -51.9], zoom || 4);
        L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19, attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>'
        }).addTo(map);
        return map;
    }

    function marcadores(map, lista) {
        const grupo = L.featureGroup().addTo(map);
        lista.forEach(a => {
            const nota = a.qtd > 0 ? '★ ' + String(a.nota).replace('.', ',') + ' (' + a.qtd + ')' : 'Sem avaliações';
            const dist = a.distancia_km != null ? '<br>📍 ' + a.distancia_km.toFixed(1).replace('.', ',') + ' km' : '';
            L.marker([a.lat, a.lng]).addTo(grupo).bindPopup(
                '<strong>' + esc(a.nome) + '</strong><br>' + nota + dist + '<br>' + esc(a.bairro) + ' - ' + esc(a.cidade) +
                '<br><a href="assistencia.php?id=' + a.id + '">Ver assistência</a>');
        });
        return grupo;
    }

    function ajustar(map, grupo) {
        if (grupo.getLayers().length) map.fitBounds(grupo.getBounds().pad(0.2), { maxZoom: 15 });
    }

    function minhaLocalizacao(map, aoAchar) {
        if (!navigator.geolocation) return alert('Seu navegador não suporta geolocalização.');
        navigator.geolocation.getCurrentPosition(p => {
            const ll = [p.coords.latitude, p.coords.longitude];
            L.circleMarker(ll, { radius: 8, color: '#087cf0', fillOpacity: .9 }).addTo(map).bindPopup('Você está aqui');
            map.setView(ll, 14);
            if (aoAchar) aoAchar(ll);
        }, () => alert('Não foi possível obter sua localização.'), { enableHighAccuracy: true, timeout: 10000 });
    }

    async function iniciar(id, opts) {
        opts = opts || {};
        const map = base(id);
        const qs = new URLSearchParams(opts.params || {});
        const lista = await (await fetch('api_assistencias.php?' + qs)).json();
        const grupo = marcadores(map, lista);
        ajustar(map, grupo);
        return map;
    }

    return { iniciar, base, marcadores, minhaLocalizacao };
})();

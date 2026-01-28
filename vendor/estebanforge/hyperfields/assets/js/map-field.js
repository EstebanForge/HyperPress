(function () {
    function updateInputs(field, latLng) {
        var latInput = document.getElementById(field + '_lat');
        var lngInput = document.getElementById(field + '_lng');
        if (latInput) {
            latInput.value = latLng.lat;
        }
        if (lngInput) {
            lngInput.value = latLng.lng;
        }
    }

    function initMapField(canvas) {
        if (!window.L) {
            return;
        }

        var field = canvas.getAttribute('data-field') || '';
        var lat = parseFloat(canvas.getAttribute('data-lat')) || 0;
        var lng = parseFloat(canvas.getAttribute('data-lng')) || 0;
        var zoom = parseInt(canvas.getAttribute('data-zoom'), 10) || 15;

        var map = window.L.map(canvas).setView([lat, lng], zoom);
        window.L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; OpenStreetMap contributors',
        }).addTo(map);

        var marker = window.L.marker([lat, lng], { draggable: true }).addTo(map);
        marker.on('dragend', function (event) {
            updateInputs(field, event.target.getLatLng());
        });

        map.on('click', function (event) {
            marker.setLatLng(event.latlng);
            updateInputs(field, event.latlng);
        });
    }

    function initMapFields() {
        var canvases = document.querySelectorAll('.hyperpress-map-canvas');
        for (var i = 0; i < canvases.length; i += 1) {
            initMapField(canvases[i]);
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initMapFields);
    } else {
        initMapFields();
    }
})();

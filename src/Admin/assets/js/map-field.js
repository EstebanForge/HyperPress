document.addEventListener('DOMContentLoaded', function () {
    const mapCanvases = document.querySelectorAll('.hmapi-map-canvas');

    if (mapCanvases.length === 0) {
        return;
    }

    mapCanvases.forEach(function (canvas) {
        const fieldName = canvas.dataset.field;
        const lat = parseFloat(canvas.dataset.lat) || 51.505;
        const lng = parseFloat(canvas.dataset.lng) || -0.09;
        const zoom = parseInt(canvas.dataset.zoom, 10) || 13;

        const latInput = document.getElementById(fieldName + '_lat');
        const lngInput = document.getElementById(fieldName + '_lng');
        const addressInput = document.getElementById(fieldName + '_address');

        const map = L.map(canvas).setView([lat, lng], zoom);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
        }).addTo(map);

        let marker = L.marker([lat, lng], { draggable: true }).addTo(map);

        marker.on('dragend', function (e) {
            const position = marker.getLatLng();
            latInput.value = position.lat;
            lngInput.value = position.lng;
        });

        map.on('click', function(e) {
            const position = e.latlng;
            marker.setLatLng(position);
            latInput.value = position.lat;
            lngInput.value = position.lng;
        });

        const geocodeButton = document.querySelector(`.hmapi-geocode-button[data-field="${fieldName}"]`);
        if (geocodeButton) {
            geocodeButton.addEventListener('click', function() {
                const query = addressInput.value;
                if (!query) return;

                fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(query)}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data && data.length > 0) {
                            const newLat = parseFloat(data[0].lat);
                            const newLng = parseFloat(data[0].lon);
                            const newPos = new L.LatLng(newLat, newLng);
                            map.setView(newPos, zoom);
                            marker.setLatLng(newPos);
                            latInput.value = newLat;
                            lngInput.value = newLng;
                        } else {
                            alert('Address not found.');
                        }
                    })
                    .catch(error => console.error('Error during geocoding:', error));
            });
        }
    });
});

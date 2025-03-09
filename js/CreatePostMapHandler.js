class CreatePostMapHandler {
    constructor(latitude, longitude) {
        this.latitude = latitude;
        this.longitude = longitude;
        this.map = null;
        this.marker = null;
        this.userMarker = null;
        this.cachedPosition = null;
        this.greenIcon = new L.Icon({
            iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-green.png',
            shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png',
            iconSize: [25, 41],
            iconAnchor: [12, 41],
            popupAnchor: [1, -34],
            shadowSize: [41, 41]
        });

        document.addEventListener("DOMContentLoaded", () => {
            this.initializeMap();
            this.addLocateControl();
        });
    }

    initializeMap() {
        this.map = L.map('map').setView([this.latitude, this.longitude], 5);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; OpenStreetMap contributors'
        }).addTo(this.map);

        this.map.on('click', (e) => {
            if (this.marker) {
                this.map.removeLayer(this.marker);
            }
            this.marker = L.marker(e.latlng, {
                icon: this.greenIcon
            }).addTo(this.map);
            document.getElementById("latitude").value = e.latlng.lat;
            document.getElementById("longitude").value = e.latlng.lng;
        });
    }

    addLocateControl() {
        let locateControl = L.control({
            position: 'topright'
        });
        locateControl.onAdd = (map) => {
            let div = L.DomUtil.create('div', 'leaflet-control-locate');
            div.title = 'Locate Me';
            L.DomEvent.on(div, 'click', (e) => {
                L.DomEvent.stopPropagation(e); // Stop the click event from propagating to the map
                let loadingIndicator = document.getElementById('loading-indicator');
                loadingIndicator.style.display = 'block';
                if (this.cachedPosition) {
                    let lat = this.cachedPosition.coords.latitude;
                    let lng = this.cachedPosition.coords.longitude;
                    if (this.userMarker) {
                        this.userMarker.setLatLng([lat, lng]);
                    } else {
                        this.userMarker = L.marker([lat, lng], {
                            icon: this.greenIcon
                        }).addTo(this.map);
                        this.userMarker.bindPopup('You are here').openPopup();
                    }
                    this.map.setView([lat, lng], 13);
                    loadingIndicator.style.display = 'none';
                } else if (navigator.geolocation) {
                    navigator.geolocation.getCurrentPosition((position) => {
                        this.cachedPosition = position;
                        let lat = position.coords.latitude;
                        let lng = position.coords.longitude;
                        if (this.userMarker) {
                            this.userMarker.setLatLng([lat, lng]);
                        } else {
                            this.userMarker = L.marker([lat, lng], {
                                icon: this.greenIcon
                            }).addTo(this.map);
                            this.userMarker.bindPopup('You are here').openPopup();
                        }
                        this.map.setView([lat, lng], 13);
                        loadingIndicator.style.display = 'none';
                    }, (error) => {
                        alert('Error getting location: ' + error.message);
                        loadingIndicator.style.display = 'none';
                    });
                } else {
                    alert('Geolocation is not supported by this browser.');
                    loadingIndicator.style.display = 'none';
                }
            });
            return div;
        };
        locateControl.addTo(this.map);
    }
}

// Initialize the CreatePostMapHandler class
let createPostMapHandler = new CreatePostMapHandler(latitude, longitude);
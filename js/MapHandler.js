class MapHandler {
    constructor(latitude, longitude, posts, flarePosts) {
        this.latitude = latitude;
        this.longitude = longitude;
        this.posts = posts;
        this.flarePosts = flarePosts;
        this.map = null;
        this.markers = [];
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
            this.addPostMarkers();
        });
    }

    initializeMap() {
        this.map = L.map('map', { dragging: false, zoomControl: false }).setView([this.latitude, this.longitude], 5);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; OpenStreetMap contributors'
        }).addTo(this.map);
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

    addPostMarkers() {
        this.posts.forEach(post => {
            if (post.latitude && post.longitude) {
                let marker = L.marker([post.latitude, post.longitude], {
                    icon: this.greenIcon
                }).addTo(this.map);
                marker.bindPopup(`
                    <b>${post.title}</b>
                    <br>
                    ${post.location_name}
                    <br>
                    By: ${post.username}
                    <br>
                    <p>${post.content.slice(0, 100)}...</p>
                    <a href="post.php?id=${post.id}" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded" style="text-decoration: none">View Post</a>
                    <button onclick="openDirections(${post.latitude}, ${post.longitude})" class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded mt-2">Directions</button>
                `);
                this.markers.push(marker);
            }
        });
    }

}

// Initialize the MapHandler class
let mapHandler = new MapHandler(latitude, longitude, posts, flare_posts);

function openDirections(lat, lng) {
    window.open(`https://www.google.com/maps/dir/?api=1&destination=${lat},${lng}`, '_blank');
}
class EditPostHandler {
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
            this.initializeTagify();
            this.initializeFileValidation();
            this.initializeContentLengthDisplay();
        });
    }

    initializeMap() {
        this.map = L.map('map').setView([this.latitude, this.longitude], 5);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; OpenStreetMap contributors'
        }).addTo(this.map);

        if (this.latitude !== null && this.longitude !== null) {
            this.marker = L.marker([this.latitude, this.longitude], {
                icon: this.greenIcon
            }).addTo(this.map);
            this.map.setView([this.latitude, this.longitude], 10);
            this.marker.bindPopup('<button onclick="editPostHandler.removeMarker(event)">Remove Location</button>').openPopup();
        }

        this.map.on('click', (e) => {
            if (this.marker) {
                this.map.removeLayer(this.marker);
            }
            this.marker = L.marker(e.latlng, {
                icon: this.greenIcon
            }).addTo(this.map);
            document.getElementById("latitude").value = e.latlng.lat;
            document.getElementById("longitude").value = e.latlng.lng;
            this.marker.bindPopup('<button onclick="editPostHandler.removeMarker(event)">Remove Location</button>').openPopup();
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

    initializeTagify() {
        let input = document.querySelector('input[name=tags]');
        let removedTagsInput = document.createElement('input');
        removedTagsInput.type = 'hidden';
        removedTagsInput.name = 'removed_tags';
        document.querySelector('form').appendChild(removedTagsInput);

        let tagify = new Tagify(input);

        tagify.on('remove', (e) => {
            let removedTag = e.detail.data.value;
            let removedTags = removedTagsInput.value ? removedTagsInput.value.split(',') : [];
            removedTags.push(removedTag);
            removedTagsInput.value = removedTags.join(',');
        });

        // Convert Tagify output to a simple comma-separated string before submitting the form
        document.querySelector('form').addEventListener('submit', () => {
            let tagsArray = tagify.value.map(tag => tag.value);
            input.value = tagsArray.join(',');
        });
    }

    initializeFileValidation() {
        document.getElementById("file_input").addEventListener("change", (event) => {
            let fileErrorsDiv = document.getElementById("fileErrors");
            let filePreviewDiv = document.getElementById("filePreview");
            fileErrorsDiv.innerHTML = ""; // Clear previous errors
            filePreviewDiv.innerHTML = ""; // Clear previous previews

            let files = event.target.files;
            let allowedTypes = [
                "image/jpeg", "image/png", "image/gif",
                "video/mp4", "video/webm", "video/quicktime",
                "audio/mpeg", "audio/wav", "audio/ogg", "audio/mp4", "audio/x-m4a", "audio/x-flac"
            ];
            let maxFileSize = 100 * 1024 * 1024; // 100MB per file
            let maxFiles = 10;

            if (files.length > maxFiles) {
                fileErrorsDiv.innerHTML = `<p>Error: You can upload a maximum of ${maxFiles} files.</p>`;
                event.target.value = ""; // Reset file input
                return;
            }

            for (let i = 0; i < files.length; i++) {
                let file = files[i];

                // Check file type
                if (!allowedTypes.includes(file.type)) {
                    fileErrorsDiv.innerHTML += `<p>Error: ${file.name} is not an allowed file type.</p>`;
                    event.target.value = ""; // Reset file input
                    return;
                }

                // Check file size
                if (file.size > maxFileSize) {
                    fileErrorsDiv.innerHTML += `<p>Error: ${file.name} exceeds the 100MB limit.</p>`;
                    event.target.value = ""; // Reset file input
                    return;
                }

                // OPTIONAL: Show image/video previews
                if (file.type.startsWith("image/")) {
                    let img = document.createElement("img");
                    img.src = URL.createObjectURL(file);
                    img.style.maxWidth = "100px";
                    img.style.margin = "5px";
                    filePreviewDiv.appendChild(img);
                } else if (file.type.startsWith("video/")) {
                    let vid = document.createElement("video");
                    vid.src = URL.createObjectURL(file);
                    vid.controls = true;
                    vid.style.maxWidth = "150px";
                    vid.style.margin = "5px";
                    filePreviewDiv.appendChild(vid);
                }
            }
        });
    }

    initializeContentLengthDisplay() {
        document.getElementById("content").addEventListener("input", function() {
            let charCount = this.value.length;
            document.getElementById("content-char-count").textContent = charCount + "/1000 characters used";
        });
    }

    validatePost() {
        let title = document.getElementById("title").value;
        let content = document.getElementById("content").value;
        let fileErrorsDiv = document.getElementById("fileErrors");

        if (title.trim() === "") {
            alert("Title must be filled in.");
            return false;
        }

        if (content.length > 1000) {
            alert("Content exceeds the 1000-character limit.");
            return false;
        }

        if (fileErrorsDiv.innerHTML !== "") {
            alert("Please fix file upload errors before submitting.");
            return false;
        }

        return true;
    }

    removeMedia(button) {
        // Get the parent container
        let container = button.parentElement;
        // Find the hidden checkbox
        let checkbox = container.querySelector('.remove-checkbox');
        // Check the checkbox to mark for removal
        checkbox.checked = true;
        // Hide the container visually
        container.style.display = 'none';
    }

    removeMarker(event) {
        event.preventDefault(); // Prevent form submission
        if (this.marker) {
            this.map.removeLayer(this.marker);
            this.marker = null;
            document.getElementById("latitude").value = '';
            document.getElementById("longitude").value = '';
        }
    }
}

// Initialize the EditPostHandler class
document.addEventListener("DOMContentLoaded", () => {
    let latitude = document.getElementById("latitude").value;
    let longitude = document.getElementById("longitude").value;
    let editPostHandler = new EditPostHandler(latitude, longitude);
});
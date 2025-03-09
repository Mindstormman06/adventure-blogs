class PostHandler {
    constructor() {
        document.addEventListener("DOMContentLoaded", () => {
            this.initializeTimeFormatting();
            this.initializeFileValidation();
            this.initializeContentLengthDisplay();
            this.initializeTagify();
        });
    }

    initializeTimeFormatting() {
        document.querySelectorAll(".post-time").forEach((element) => {
            let pstTime = element.getAttribute("data-time");

            if (!pstTime) return; // Skip if no timestamp found

            let dateObjPST = new Date(pstTime); // Stored PST timestamp
            if (isNaN(dateObjPST.getTime())) { // Check for invalid date
                console.error("Invalid date format for:", pstTime);
                element.innerText = "Error loading time";
                return;
            }

            // Step 1: Convert PST → UTC (Add 8 hours)
            let dateObjUTC = new Date(dateObjPST.getTime() + (8 * 60 * 60 * 1000));

            // Step 2: Convert UTC → Local Time (Based on viewer's timezone)
            let localTime = new Date(dateObjUTC.getTime() - dateObjUTC.getTimezoneOffset() * 60000);

            // Format the local date as YYYY/MM/DD
            let formattedDate = localTime.getFullYear() + '/' +
                ('0' + (localTime.getMonth() + 1)).slice(-2) + '/' +
                ('0' + localTime.getDate()).slice(-2);

            // Get the time difference (e.g., "2 hours ago", "3 days ago", etc.)
            let timeAgo = this.getTimeAgo(localTime);

            // Display the formatted date and time difference
            element.innerText = `${formattedDate} (${timeAgo})`;
        });
    }

    getTimeAgo(localTime) {
        let now = new Date(); // Local time
        let diff = now - localTime;

        // Calculate time difference in milliseconds
        let minutes = Math.floor(diff / (1000 * 60));
        let hours = Math.floor(diff / (1000 * 60 * 60));
        let days = Math.floor(diff / (1000 * 60 * 60 * 24));
        let weeks = Math.floor(days / 7);
        let months = Math.floor(days / 30);
        let years = Math.floor(days / 365);

        if (years > 0) return `${years} year${years > 1 ? 's' : ''} ago`;
        if (months > 0) return `${months} month${months > 1 ? 's' : ''} ago`;
        if (weeks > 0) return `${weeks} week${weeks > 1 ? 's' : ''} ago`;
        if (days > 0) return `${days} day${days > 1 ? 's' : ''} ago`;
        if (hours > 0) return `${hours} hour${hours > 1 ? 's' : ''} ago`;
        if (minutes > 0) return `${minutes} minute${minutes > 1 ? 's' : ''} ago`;
        return "Just now";
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

    initializeTagify() {
        let input = document.querySelector('input[name=tags]');
        let tagify = new Tagify(input);

        // Convert Tagify output to a simple comma-separated string before submitting the form
        document.querySelector('form').addEventListener('submit', function() {
            let tagsArray = tagify.value.map(tag => tag.value);
            input.value = tagsArray.join(',');
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
}

// Initialize the PostHandler class
let postHandler = new PostHandler();
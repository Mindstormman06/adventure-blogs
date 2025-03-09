class SearchHandler {
    constructor() {
        document.addEventListener("DOMContentLoaded", () => {
            this.initializeSearch();
        });
    }

    initializeSearch() {
        document.getElementById("search").addEventListener("input", (event) => {
            let query = event.target.value.toLowerCase().trim();
            console.log("Search Query:", query); // Debugging

            let posts = document.querySelectorAll(".post-tile");

            posts.forEach(post => {
                let username = post.getAttribute("data-username")?.toLowerCase() || "";
                let tags = post.getAttribute("data-tags")?.toLowerCase() || "";
                let location = post.getAttribute("data-location")?.toLowerCase() || "";
                let title = post.getAttribute("data-title")?.toLowerCase() || ""; // Ensure title exists

                console.log("Post Data:", {
                    username,
                    tags,
                    location,
                    title
                }); // Debugging

                let matches = false;

                if (query.startsWith("@")) {
                    let searchTerm = query.substring(1); // Remove @
                    matches = username.includes(searchTerm);
                    console.log("Searching by Username:", searchTerm, matches);
                } else if (query.startsWith("#")) {
                    let searchTerm = query.substring(1); // Remove #
                    matches = tags.includes(searchTerm);
                    console.log("Searching by Tag:", searchTerm, matches);
                } else {
                    matches = location.includes(query) || title.includes(query);
                    console.log("Searching by Title/Location:", query, matches);
                }

                post.style.display = matches ? "flex" : "none";
            });
        });
    }
}

// Initialize the SearchHandler class
new SearchHandler();
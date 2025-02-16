document.addEventListener("DOMContentLoaded", function() {
    // Auto-hide success messages after 3 seconds
    let messages = document.querySelectorAll(".message");
    messages.forEach(function(msg) {
        setTimeout(() => {
            msg.style.display = "none";
        }, 3000);
    });

    // Confirm before deleting a post (if needed later)
    let deleteButtons = document.querySelectorAll(".delete-btn");
    deleteButtons.forEach(function(btn) {
        btn.addEventListener("click", function(event) {
            if (!confirm("Are you sure you want to delete this post?")) {
                event.preventDefault();
            }
        });
    });
});

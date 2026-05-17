document.addEventListener("DOMContentLoaded", function() {
    const modal = document.getElementById("imageModal");
    const modalImg = document.getElementById("modalImg");
    const captionText = document.getElementById("caption");
    const closeBtn = document.querySelector(".close");

    document.querySelectorAll('.amenity-card img').forEach(img => {
        img.addEventListener('click', function() {
            modal.style.display = "block";
            modalImg.src = this.src;
            captionText.innerText = this.alt;
        });
    });

    closeBtn.onclick = function() {
        modal.style.display = "none";
    };

    // Close modal when clicking outside the image
    modal.onclick = function(event) {
        if (event.target === modal) {
            modal.style.display = "none";
        }
    };
});
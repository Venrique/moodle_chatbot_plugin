document.addEventListener("DOMContentLoaded", (event) => {
    console.log("DOM fully loaded and parsed");

    var arrows = document.querySelectorAll(".cta");

    arrows.forEach(function(arrow) {
        arrow.addEventListener('click', function() {
            var description = this.parentNode.querySelector('.description');

            var isVisible = description.style.display === 'block';
            if (isVisible) {
                description.style.display = 'none';
                this.classList.remove('up');
                this.classList.add('down');
            } else {
                description.style.display = 'block';
                this.classList.remove('down');
                this.classList.add('up');
            }
        });
    });
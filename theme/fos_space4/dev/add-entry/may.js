document.addEventListener("DOMContentLoaded", (event) => {
    console.log("DOM fully loaded and parsed");

    var arrows = document.querySelectorAll(".cta");
    console.log(arrows);

    arrows.forEach(function (arrow) {
        arrow.addEventListener('click', function () {
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


    // Assign a limit of 100 characters to the title field

    var titleDiv = document.getElementById("titleDiv");
    var titleInput = titleDiv.querySelector(".basefieldinput");
    titleInput.addEventListener('input', limitCharacterCount);

    function limitCharacterCount() {
        console.log("Adding maxlength attribute to input text");
        this.setAttribute('maxlength', '100');
    }

    // Assign a limit of 100 words to the description and comment field

    var textareaDivs = document.querySelectorAll(".value");

    if (textareaDivs.length >= 9) {
        var textareaDiv2 = textareaDivs[2];
        var textareaDiv8 = textareaDivs[8];

        textareaDiv2.addEventListener('input', limitWordCount);
        textareaDiv8.addEventListener('input', limitWordCount);
    }

    function limitWordCount() {
        var editorDiv = this.querySelector('.editor_atto_content.form-control');
        var content = editorDiv.innerText;
        var words = content.split(" ");
        var wordCount = words.length;

        if (wordCount > 100) {
            var newContent = words.slice(0, 100).join(" ");
            editorDiv.innerText = newContent;

            var range = document.createRange();
            var sel = window.getSelection();
            range.setStart(editorDiv.childNodes[0], newContent.length);
            range.collapse(true);
            sel.removeAllRanges();
            sel.addRange(range);
        }
    }

});
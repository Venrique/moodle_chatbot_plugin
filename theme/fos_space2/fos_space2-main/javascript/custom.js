require(["jquery"], function ($) {
  $(document).ready(function () {
    function loadStylesheet(href, integrity, crossorigin) {
      var link = document.createElement("link");
      link.rel = "stylesheet";
      link.href = href;
      link.integrity = integrity;
      link.crossOrigin = crossorigin;
      document.head.appendChild(link);
    }

    loadStylesheet(
      "https://cdnjs.cloudflare.com/ajax/libs/OwlCarousel2/2.3.4/assets/owl.carousel.min.css",
      "sha512-tS3S5qG0BlhnQROyJXvNjeEM4UpMXHrQfTGmbQ1gKmelCxlSEBUaxhRBj/EFTzpbP4RVSrpEikbmdJobCvhE3g==",
      "anonymous"
    );

    loadStylesheet(
      "https://cdnjs.cloudflare.com/ajax/libs/OwlCarousel2/2.3.4/assets/owl.theme.default.min.css",
      "sha512-sMXtMNL1zRzolHYKEujM2AqCLUR9F2C4/05cdbxjjLSRvMQIciEPCQZo++nk7go3BtSuK9kfa/s+a4f4i5pLkw==",
      "anonymous"
    );

    function loadScript(src, integrity, crossorigin) {
      return new Promise(function (resolve, reject) {
        var script = document.createElement("script");
        script.type = "text/javascript";
        script.src = src;
        script.integrity = integrity;
        script.crossOrigin = crossorigin;
        script.onload = resolve;
        script.onerror = reject;
        document.head.appendChild(script);
      });
    }

    Promise.all([
      loadScript(
        "https://cdnjs.cloudflare.com/ajax/libs/OwlCarousel2/2.3.4/owl.carousel.min.js",
        "sha512-bPs7Ae6pVvhOSiIcyUClR7/q2OAsRiovw4vAkX+zJbw3ShAeeqezq50RIIcIURq7Oa20rW2n2q+fyXBNcU9lrw==",
        "anonymous"
      ),
    ])
      .then(function () {
        console.log("All scripts have been loaded");
        var owl = $("#owl-carousel_slider");
        //owl.empty();
        owl.owlCarousel({
          items: 1,
          margin: 30,
          stagePadding: 30,
          smartSpeed: 450,
          loop: true,
          autoplay: true,
        });
      })
      .catch(function () {
        console.log("Something went wrong loading the scripts");
      });

    //$("#login").remove();
    //$("#page-header").remove();

    $(window).scroll(function () {
      if ($(this).scrollTop() > 200) {
        $("#scroll").fadeIn();
      } else {
        $("#scroll").fadeOut();
      }
    });
    $("#scroll").click(function () {
      $("html, body").animate({ scrollTop: 0 }, 600);
      return false;
    });

    // add entry
    $(".cta").click(function () {
      var description = $(this).parent().find(".description-cont");
      var isVisible = description.is(":visible");

      if (isVisible) {
        description.hide();
        $(this).find("div").removeClass("up").addClass("down");
      } else {
        description.show();
        $(this).find("div").removeClass("down").addClass("up");
      }
    });


    // Assign a limit of 100 characters to the title field
    var titleInput = $("#titleDiv").find(".basefieldinput");

    titleInput.on('click', function () {
      console.log("Adding maxlength attribute to input text");
      $(this).attr('maxlength', '100');
    });

    // Assign a limit of 100 words to the description and comment field
    var textareaDivs = $(".value");

    if (textareaDivs.length >= 9) {
      var textareaDiv2 = $(textareaDivs[2]);
      var textareaDiv8 = $(textareaDivs[8]);
      var linkfield = $(textareaDivs[9]);

      textareaDiv2.on('input', limitWordCount);
      textareaDiv8.on('input', limitWordCount);
      textareaDiv2.on('focusout', limitword);
      textareaDiv8.on('focusout', limitword);
      textareaDiv8.on('focusout', limitword);
      linkfield.on('focusout', removespace)
    }

    function limitWordCount() {
      var editorDiv = $(this).find('.editor_atto_content.form-control');
      var content = editorDiv.text();
      var words = content.split(" ");
      var wordCount = words.length;

      if (wordCount > 100) {
        var newContent = words.slice(0, 100).join(" ");
        editorDiv.text(newContent);

        var range = document.createRange();
        var sel = window.getSelection();
        range.setStart(editorDiv[0].childNodes[0], newContent.length);
        range.collapse(true);
        sel.removeAllRanges();
        sel.addRange(range);
      }
    }
    function limitword() {
      var editorDiv = $(this).find('.editor_atto_content.form-control');
      var content = editorDiv.text();
      var words = content.split(/\s+/);
      var wordCount = words.length;

      if (wordCount > 100) {
        var newContent = words.slice(0, 100).join(" ");
        editorDiv.text(newContent);
      }
    }
//remove space of link field
function removespace() {
  var linkinputs = $(this).find('.form-inline table td input');
  if (linkinputs.length > 0) { 
      var valuelink = linkinputs.eq(0).val().replace(/\s+/g, '');
      //console.log(valuelink);
      linkinputs.eq(0).val(valuelink);
  } else {
      console.log("No items matching the selector were found.");
  }
}

  // - list view
    var maxChars = 100;
    var maxWords = 30;

    $('.mx-chr').each(function() {
        var text = $(this).text();
        if (text.length > maxChars) {
            var newText = text.substr(0, maxChars) + '...';
            $(this).text(newText);
        }
    });
  
      $('.mx-wrd').each(function() {
          var text = $(this).text();
          var words = text.split(' ');
          if (words.length > maxWords) {
              var newText = words.slice(0, maxWords).join(' ') + '...';
              $(this).text(newText);
          }
      });

  });
});
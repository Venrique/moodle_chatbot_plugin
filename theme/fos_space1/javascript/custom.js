require(["jquery"], function ($) {
  $(document).ready(function () {
    var userToken = "786b2d70191e8e690e6c3b4ac7045a45";

    // URL para la API REST de Moodle
    var moodleUrl = M.cfg.wwwroot + "/webservice/rest/server.php";

    // Parámetros para la llamada a la API
    var data = {
      wstoken: userToken,
      wsfunction: "core_course_get_courses",
      moodlewsrestformat: "json",
    };

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
        script.onload = resolve; // Resuelve la promesa cuando el script se haya cargado
        script.onerror = reject; // Rechaza la promesa si hay un error
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
          autoplay: true
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
  });
});

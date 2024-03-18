require(["jquery"], function ($) {
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
  });
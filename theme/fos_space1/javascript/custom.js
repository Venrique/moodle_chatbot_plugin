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
            $("html, body").animate({scrollTop: 0}, 600);
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

        var hidden = true;

        $("#expertmode").find('.mod-data-input.mr-1').click(function() {
            if(hidden){
                $('[hidden]').removeAttr('hidden');
                hidden=false;
            }else{
                $(".lockTranslation").attr('hidden', true);
                $("#titleEN").attr('hidden', true);
                $("#titleES").attr('hidden', true);
                $("#titleFR").attr('hidden', true);
                $("#titlePT").attr('hidden', true);
                $("#descEN").attr('hidden', true);
                $("#descES").attr('hidden', true);
                $("#descFR").attr('hidden', true);
                $("#descPT").attr('hidden', true);
                hidden=true;
            }
        });

        //AUTOMATIC TRANSLATION OF TEXT
        $("#description").on('focusout', translateDescriptions);
        $("#descriptionEN").on('focusout', translateDescriptions);
        $("#descriptionES").on('focusout', translateDescriptions);
        $("#descriptionFR").on('focusout', translateDescriptions);
        $("#descriptionPT").on('focusout', translateDescriptions);
        $("#titleDiv").on('focusout', translateTitles);
        $("#titleDivEN").on('focusout', translateTitles);
        $("#titleDivES").on('focusout', translateTitles);
        $("#titleDivFR").on('focusout', translateTitles);
        $("#titleDivPT").on('focusout', translateTitles);
        function translateTitles(){
            let origin = $(this).find('.basefieldinput.form-control.d-inline.mod-data-input');
            let titleEN = $("#titleDivEN").find('.basefieldinput.form-control.d-inline.mod-data-input');
            let titleES = $("#titleDivES").find('.basefieldinput.form-control.d-inline.mod-data-input');
            let titleFR = $("#titleDivFR").find('.basefieldinput.form-control.d-inline.mod-data-input');
            let titlePT = $("#titleDivPT").find('.basefieldinput.form-control.d-inline.mod-data-input');
            let lockEN = $("#TitlelockENTranslation");
            let lockES = $("#TitlelockESTranslation");
            let lockFR = $("#TitlelockFRTranslation");
            let lockPT = $("#TitlelockPTTranslation");
            let targetLanguages = [];
            if (!lockEN.is(':checked')) {
                console.log("Translation to english");
                targetLanguages.push("EN");
            }
            if (!lockES.is(':checked')) {
                console.log("Translation to spanish");
                targetLanguages.push("ES");
            }
            if (!lockFR.is(':checked')) {
                console.log("Translation to french");
                targetLanguages.push("FR");
            }
            if (!lockPT.is(':checked')) {
                console.log("Translation to portuguese");
                targetLanguages.push("PT");
            }
            targetLanguages.forEach(function (targetLang) {
                translateText(origin.val(), '', targetLang, function (error, translatedText) {
                    if (error) {
                        console.error("Translation error:", error);
                    } else {
                        if (targetLang === "EN") {
                            titleEN.val(translatedText);
                        }
                        if (targetLang === "ES") {
                            titleES.val(translatedText);
                        }
                        if (targetLang === "FR") {
                            titleFR.val(translatedText);
                        }
                        if (targetLang === "PT") {
                            titlePT.val(translatedText);
                        }
                    }
                });
            });
        }
        function translateDescriptions() {
            let origin = $(this).find('.editor_atto_content.form-control');
            let descEN = $("#descriptionEN").find('.editor_atto_content.form-control');
            let descES = $("#descriptionES").find('.editor_atto_content.form-control');
            let descFR = $("#descriptionFR").find('.editor_atto_content.form-control');
            let descPT = $("#descriptionPT").find('.editor_atto_content.form-control');
            let lockEN = $("#lockENTranslation");
            let lockES = $("#lockESTranslation");
            let lockFR = $("#lockFRTranslation");
            let lockPT = $("#lockPTTranslation");
            let targetLanguages = [];
            if (!lockEN.is(':checked')) {
                console.log("Translation to english");
                targetLanguages.push("EN");
            }
            if (!lockES.is(':checked')) {
                console.log("Translation to spanish");
                targetLanguages.push("ES");
            }
            if (!lockFR.is(':checked')) {
                console.log("Translation to french");
                targetLanguages.push("FR");
            }
            if (!lockPT.is(':checked')) {
                console.log("Translation to portuguese");
                targetLanguages.push("PT");
            }
            targetLanguages.forEach(function (targetLang) {
                translateText(origin.text(), '', targetLang, function (error, translatedText) {
                    if (error) {
                        console.error("Translation error:", error);
                    } else {
                        if (targetLang === "EN") {
                            descEN.text(translatedText);
                        }
                        if (targetLang === "ES") {
                            descES.text(translatedText);
                        }
                        if (targetLang === "FR") {
                            descFR.text(translatedText);
                        }
                        if (targetLang === "PT") {
                            descPT.text(translatedText);
                        }
                    }
                });
            });
        }

        function translateText(text, sourceLang, targetLang, callback) {
            var authKey = 'bf185a4a-075b-9397-1bd3-7b10de0c9fa5:fx'; // Replace 'YOUR_DEEPL_API_KEY' with your actual DeepL API key
            var apiUrl = 'https://api-free.deepl.com/v2/translate';
            $.ajax({
                url: apiUrl,
                type: 'POST',
                contentType: 'application/x-www-form-urlencoded',
                data: {
                    'auth_key': authKey,
                    'text': text,
                    'source_lang': sourceLang,
                    'target_lang': targetLang
                },
                success: function (response) {
                    if (response && response.translations && response.translations.length > 0) {
                        var translatedText = response.translations[0].text;
                        callback(null, translatedText);
                    } else {
                        callback("Translation failed");
                    }
                },
                error: function (xhr, status, error) {
                    callback("Error occurred: " + error);
                }
            });
        }

        // Assign a limit of 100 characters to the title field
        var titleInput = $("#titleDiv").find(".basefieldinput");

        titleInput.on('click', function () {
            //console.log("Adding maxlength attribute to input text");
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

            linkfield.on('input', wordlimit);
            linkfield.on('focusout', removespace);

            var selectlist = $(textareaDivs[1]).find('div select');
            selectlist.each(function () {
                $(this).prop('readonly', true).attr('tabindex', '-1');
            });
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
        var tdList = $('.form-inline table tbody td input');

        function removespace() {
            if (tdList.length >= 2) {
                var valuelink = tdList.eq(0).val().replace(/\s+/g, '');
                tdList.eq(0).val(valuelink);
                var value = tdList.eq(1).val();
                var words = value.split(/\s+/);
                var wordCount = words.length;

                if (wordCount > 6) {
                    var newContent = words.slice(0, 100).join(" ");
                    tdList.eq(1).val(newContent);
                }
            } else {
                console.log("Not enough items in tdList to access index 0.");
            }
        }

        function wordlimit() {
            if (tdList.length >= 2) {
                var value = tdList.eq(1).val();
                var words = value.split(" ");
                var wordCount = words.length;
                if (wordCount > 6) {
                    var newContent = words.slice(0, 6).join(" ");
                    tdList.eq(1).val(newContent);
                }
            } else {
                console.log("Not enough items in tdList to access index 1.");
            }

        }

        //upload field

        $('#miCampo').prop('readonly', true);


        // - list view
        var maxChars = 100;
        var maxWords = 30;

        $('.mx-chr').each(function () {
            var text = $(this).text();
            if (text.length > maxChars) {
                var newText = text.substr(0, maxChars) + '...';
                $(this).text(newText);
            }
        });

        $('.mx-wrd').each(function () {
            var text = $(this).text();
            var words = text.split(' ');
            if (words.length > maxWords) {
                var newText = words.slice(0, maxWords).join(' ') + '...';
                $(this).text(newText);
            }
        });
        //block event on data entry view
        $('input[type=text]').on('keypress', function(e) {
          if (e.which === 13) {
              e.preventDefault();
          }
      });
    });
  });
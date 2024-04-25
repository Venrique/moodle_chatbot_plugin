require(["jquery"], function ($) {
    $(document).ready(function () {
        //----------------------------------------------------------------------------------------------------------------------------------------------------------------- 
        // Expert Mode

        /* var hidden = true;
 
         $("#expertmode").find('.mod-data-input.mr-1').click(function () {
             if (hidden) {
                 $('[hidden]').removeAttr('hidden');
                 hidden = false;
             } else {
                 $(".lockTranslation").attr('hidden', true);
                 $("#titleEN").attr('hidden', true);
                 $("#titleES").attr('hidden', true);
                 $("#titleFR").attr('hidden', true);
                 $("#titlePT").attr('hidden', true);
                 $("#descEN").attr('hidden', true);
                 $("#descES").attr('hidden', true);
                 $("#descFR").attr('hidden', true);
                 $("#descPT").attr('hidden', true);
                 hidden = true;
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
         function translateTitles() {
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
         }*/

        //-----------------------------------------------------------------------------------------------------------------------------------------------------------------
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

        //-----------------------------------------------------------------------------------------------------------------------------------------------------------------
        // Assign a limit of 100 characters to the title field
        var titleInput = $("#titleDiv").find(".basefieldinput");

        titleInput.on('click', function () {
            $(this).attr('maxlength', '100');
        });

        //-----------------------------------------------------------------------------------------------------------------------------------------------------------------
        // Assign a limit of 100 words to the description and comment field
        var descriptionDiv = $("#description");
        var commentDiv = $("#comments");
        var linkfield = $("#linkfield");

        descriptionDiv.on('input', limitWordCount);
        commentDiv.on('input', limitWordCount);
        commentDiv.on('focusout', limitword);
        descriptionDiv.on('focusout', limitword);

        linkfield.on('input', wordlimit);
        linkfield.on('focusout', removespace);

        var selectlist = $("#uploadDate").find('div select');
        selectlist.each(function () {
            $(this).prop('readonly', true).attr('tabindex', '-1');
        });


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

        //-----------------------------------------------------------------------------------------------------------------------------------------------------------------
        //remove space of link field
        var tdList = $('.form-inline table tbody td input');
        console.log(tdList);

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
                    var newContent = words.slice(0, 6).join(" ").trim();
                    tdList.eq(1).val(newContent);
                }
            } else {
                console.log("Not enough items in tdList to access index 1.");
            }

        }

        //-----------------------------------------------------------------------------------------------------------------------------------------------------------------
        //list view
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
        //-----------------------------------------------------------------------------------------------------------------------------------------------------------------
        // remove space on title and author field - advancedSearch
        var inputadvacedsearch = $(".input-advancedsearch input");
        console.log(inputadvacedsearch);

        if (inputadvacedsearch.length > 0) {
            var inputtitle = inputadvacedsearch.eq(0);
            var inputauthor = inputadvacedsearch.eq(1);

            inputtitle.on('focusout', removespaceAdvancesearch);
            inputauthor.on('focusout', removespaceAdvancesearch);

            function removespaceAdvancesearch(event) {
                var inputValue = $(event.target).val();
                var trimmedValue = $.trim(inputValue);
                $(event.target).val(trimmedValue);
            }
        }

        //-----------------------------------------------------------------------------------------------------------------------------------------------------------------
        //block event on data entry view
        $('input[type=text]').on('keypress', function (e) {
            if (e.which === 13) {
                e.preventDefault();
            }
        });


    });
});
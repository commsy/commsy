;(function(UI) {

    "use strict";

    UI.component('csupload', {

        defaults: {
            path: '',
            settings: {
                allow: '*.*'
            }
        },

        boot: function() {console.log("boot");
            // init code
            UI.ready(function(context) {
                UI.$("[data-uk-csupload]", context).each(function() {
                    let element = UI.$(this);

                    if (!element.data("csupload")) {
                        UI.csupload(element, UI.Utils.options(element.attr("data-uk-csupload")));
                    }
                });
            });
        },

        init: function() {
            let $this = this;

            let $progressbar = $($this.element).siblings('.uk-progress').first();
            let $bar = $progressbar.find('.uk-progress-bar');

            let elementSettings = {
                action: $this.options.path,
                single: false,

                loadstart: function() {
                    $bar.css("width", "0%").text("0%");
                    $progressbar.removeClass("uk-hidden");
                },

                progress: function(percent) {
                    percent = Math.ceil(percent);
                    $bar.css("width", percent+"%").text(percent+"%");
                },

                allcomplete: function(response) {
                    $bar.css("width", "100%").text("100%");

                    setTimeout(function(){
                        $progressbar.addClass("uk-hidden");
                    }, 250);
                    
                    let responseData = JSON.parse(response);

                    if (responseData['userImage']) {
                        $('#profile_form_user_image').attr('src', responseData['userImage'] + '?' + Math.random());
                    } else if (responseData['fileIds']) {
                        for (let key in responseData['fileIds']) {
                            $('#upload_oldFiles').append('<div class="uk-form-controls"><input type="checkbox" id="upload_oldFiles_' + key +'" name="upload[oldFiles][]" value="' + key +'" checked="checked"></div><label class="uk-form-label" for="upload_oldFiles_' + key +'">' + responseData['fileIds'][key] + '</label>');
                        }
                    }
                }
            };

            let merged = $.extend($this.options.settings, elementSettings);

            let select = UI.uploadSelect($this.element, merged);
            let drop = UI.uploadDrop($($this.element).parentsUntil('.uk-placeholder').parent(), merged);


            $(".uk-position-cover div.uk-form-controls").css("margin-left", "0px");

            let deleteRow = $("#general_settings_room_image_delete_custom_image").closest(".uk-form-row");
            let repeatRow = $("#general_settings_room_image_repeat_x").closest(".uk-form-row");

            $.merge(repeatRow, deleteRow).addClass('uk-form-controls');

            $('#general_settings_room_image_choice').on(
                'change', function() {
                    this.toggleUploadListener(this, deleteRow, repeatRow);
            });

            $("#bgPreview").closest("form").on(
                'submit',  function() {
                    // Disable the input[type='file'] field on submit to prevent it from being send to the server; 
                    // the actual image data is already transmitted via the hidden 'image_data' field, so it doesn't need to
                    // be send again via the input[type='file'] field (which is only used as an option for file selection!)!
                    $("input[type='file']", this).attr('disabled', 'disabled');
            });

            this.toggleUploadListener($("#general_settings_room_image_choice"), deleteRow, repeatRow);
        },

        toggleUploadListener: function(containerElement, deleteRow, repeatRow) {
            let bgPreview = $("#bgPreview");

            let imageType = $("input:checked", containerElement).val();

            // TODO: dynamically load bg preview depending on choice (custom image / default theme image)
            if(imageType === 'default_image') {
                $(".cs-upload-form").hide();
                repeatRow.closest(".uk-form-row").show();
                deleteRow.closest(".uk-form-row").hide();

                bgPreview.off();
                $("input[type='file']", bgPreview).off();
            } else if(imageType === 'custom_image') {
                $(".cs-upload-form").show();
                repeatRow.closest(".uk-form-row").hide();
                deleteRow.closest(".uk-form-row").show();

                bgPreview.on({
                    'dragover dragleave drop': function(e){
                        e.preventDefault();
                    },
                    'dragover': function(){
                        $(".uk-position-cover", this).css("opacity", "0.9");
                        return false;
                    },
                    'dragleave dragend drop': function(){
                        $(".uk-position-cover", this).css("opacity", "0.7");
                    },
                    'drop': function(e){
                        this.setBackgroundImage(e.dataTransfer.files[0], $('img', this));
                    }
                });

                $("input[type='file']", bgPreview).on({
                    'change': function(e){
                        this.setBackgroundImage(e.target.files[0], $('img', bgPreview));
                    }
                });
            }
        },

        setBackgroundImage: function(f, previewImage) {
            // TODO: set threshold to sensible value (e.g. the real upload size limit of the server)!
            if(f.size > 2000000) {
                alert("File size too large ("+(f.size / 1000) +" KB)! \n This service accepts image files up to 500 KB only!");
                return false;
            }
            let reader = new FileReader();
            reader.onload = function(event) {
                previewImage.attr("src", event.target.result);
                $('#general_settings_room_image_room_image_data').val(event.target.result);
            }
            reader.readAsDataURL(f);
        }
    });

})(UIkit);
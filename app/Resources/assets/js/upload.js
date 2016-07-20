;(function(UI) {

    "use strict";

    var settings = {
        allow: '*.*'
    };

    function setBackgroundImage(f, previewImage){
        console.debug("SetBackgroundImage to "+f.name);
        if(f.size > 500000){
            alert("File size too large ("+(f.size / 1000) +" KB)! \n This service accepts image files up to 500 KB only!");
            return false;
        }
        var reader = new FileReader();
        reader.onload = function(event){
            var result = event.target.result;
            previewImage.attr("src", result);
            $('#general_settings_room_image_room_image_data').val(result);
            /*
            $("#imageInfo").empty().append('<li>Name: '
                +f.name+'</li><li>Type: '
                +f.type+'</li><li>Size: '
                +f.size+' bytes</li><li>Data: '
                +result.substring(0,50)+'...</li>');
            */
        }
        reader.readAsDataURL(f);
    }

    $(document).ready(function() {
        $(".uk-position-cover div.uk-form-controls").css("margin-left", "0px");

        var deleteCustomImage = $("#general_settings_room_image_delete_custom_image");
        var repeatDefaultImageX = $("#general_settings_room_image_room_image_repeat_x");

        deleteCustomImage.closest(".uk-form-row").addClass("uk-form-controls");
        repeatDefaultImageX.closest(".uk-form-row").addClass("uk-form-controls");

        $('#general_settings_room_image_room_image_choice').on('change', function(){
            var imageType = $("input:checked", this).val();
            console.log("Room image choice changed to " + imageType);

            deleteCustomImage.prop("disabled", (imageType === 'default_image'));
            repeatDefaultImageX.prop("disabled", (imageType === 'default_image'));
        });
    });

    var setupUpload = function() {

        $('#bgPreview').on({
            'dragover, dragleave, drop': function(e){
                e.preventDefault();
            },
            'dragover': function(){
                $(".uk-position-cover", this).css("opacity", "0.9");
                return false;
            },
            'dragleave, dragend, drop': function(){
                $(".uk-position-cover", this).css("opacity", "0.7");
            },
            'drop': function(e){
                setBackgroundImage(e.dataTransfer.files[0], $('img', this));
            }
        });

        $("#bgPreview input[type='file']").on({
            'change': function(e){
                setBackgroundImage(e.target.files[0], $('#bgPreview img'));
            }
        });

        $('.upload').each(function() {
            // get data from input element
            var data = $(this).find('input').data('upload');

            // skip already initialized uploa fields, may be optimized
            if (data.initialized) {
                return true;
            }

            var progressbar = $(this).siblings('.uk-progress').first();
            var bar = progressbar.find('.uk-progress-bar');

            var elementSettings = {
                action: data.path,
                single: false,

                loadstart: function() {
                    bar.css("width", "0%").text("0%");
                    progressbar.removeClass("uk-hidden");
                },

                progress: function(percent) {
                    percent = Math.ceil(percent);
                    bar.css("width", percent+"%").text(percent+"%");
                },

                allcomplete: function(response) {
                    bar.css("width", "100%").text("100%");

                    setTimeout(function(){
                        progressbar.addClass("uk-hidden");
                    }, 250);
                    
                    var responseData = JSON.parse(response);

                    if (responseData['userImage']) {
                        $('#profile_form_user_image').attr('src', responseData['userImage'] + '?' + Math.random());
                    }
                    else if (responseData['fileIds']) {
                        console.log(responseData['fileIds']);
                        for (var key in responseData['fileIds']) {
                            $('#upload_oldFiles').append('<div class="uk-form-controls"><input type="checkbox" id="upload_oldFiles_' + key +'" name="upload[oldFiles][]" value="' + key +'" checked="checked"></div><label class="uk-form-label" for="upload_oldFiles_' + key +'">' + responseData['fileIds'][key] + '</label>');
                        }
                    }
                }
            };

            var merged = $.extend(settings, elementSettings);

            var select = UI.uploadSelect($(this).find('input'), merged);
            var drop = UI.uploadDrop(this, merged);

            // set an initialized flag to prevent re-setup
            data.initialized = true;
            $(this).find('input').data('upload', data);
        });
    };

    UIkit.on('beforeready.uk.dom', function() {
        console.debug("beforeready.uk.dom event registered");
        setupUpload();
    });

    UIkit.on('changed.uk.dom', function(event) {
        console.debug("changed.uk.dom event registered");
        setupUpload();
    });

})(UIkit);

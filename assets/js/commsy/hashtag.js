;(function(UI) {

    "use strict";

    UI.component('addHashtag', {

        defaults: {
            targetDiv: ''
        },

        boot: function() {
            // init code
            UI.ready(function(context) {
                UI.$("[data-cs-add-hashtag]", context).each(function() {
                    let element = UI.$(this);

                    if (!element.data("addHashtag")) {
                        var obj = UI.addHashtag(element, UI.Utils.options(element.attr("data-cs-add-hashtag")));
                    }
                });
            });
        },

        init: function() {
            let $this = this;

            this.element.on('click', function(event) {
                event.preventDefault();

                $this.onClickItem($this.element);
                
            });

            $('#itemLinks_newHashtag').keypress(function(e) {
                if(e.which == 13) {
                    e.preventDefault();
                    $this.onClickItem($this.element);
                }
            });
        },

        onClickItem: function(element) {
            var $this = this;

            // add new hashtag
            var hashtagValue = $('#itemLinks_newHashtag').val();
            var url = $('#itemLinks_newHashtagAdd').data('csAddHashtag');

            $.ajax({
                url: url,
                type: "POST",
                data: {
                    title: hashtagValue,
                }
            })
            .done(function(result, statusText, xhrObject) {
                // add new hashtag to form
                var countElements = $('#linksForm .hashtag-form').children().length;
                $('#linksForm .hashtag-form').append(
                    '<div>' +
                    '<input type="checkbox" id="itemLinks_hashtags_' + countElements + '" name="itemLinks[hashtags][]" value="' + result.buzzwordId + '">' +
                    '<label class="uk-form-label" for="itemLinks_hashtags_' + countElements + '">' + result.buzzwordTitle + '</label></div>'
                );

                // clear user input
                $('#itemLinks_newHashtag').val('');
            });
        }
    });

})(UIkit);
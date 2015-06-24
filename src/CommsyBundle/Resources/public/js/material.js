;(function(UI) {

    "use strict";

    var feedStart = 10;

    $('#materials-load-more').on('inview.uk.scrollspy', function() {
        $.ajax({
          url: $(this).data('feed').url + feedStart
        })
        .done(function(result) {
            $('#materials-feed').append(result);
            feedStart += 10;
        });
    });

})(UIkit);
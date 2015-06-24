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

    // handle clicks on articles
    $('#materials-feed article').click(function(event) {
        var article = $(this);

        // select mode?
        if (article.hasClass('selectable')) {
            // highlight the article
            article.toggleClass('uk-comment-primary');

            // toggle checkbox
            article.find('input[type="checkbox"]').prop('checked', article.hasClass('uk-comment-primary'));

            // disable normal click behaviour
            event.preventDefault();
        }
    });

    // modify normal checkbox click event
    $('#materials-feed input').click(function(event) {
        event.stopPropagation();
        $(this).parents('article').click();
    });

})(UIkit);
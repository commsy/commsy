;(function(UI){

    "use strict";

    $(".newArticle").on('click', function(){
        // Create new section element in material view
        
        var url = $(this).data('article-url');
        // send ajax request to get new section item
        $.ajax({
            url: url
        })
        .done(function(result) {
            // set section item in material view

            if ($('.discussion-article').last()[0]) {
                $('.discussion-article').last().after(result);
                $('.discussion-article').last()[0].scrollIntoView();
            } else {
                $('#article-content').html(result);
                $('#article-content').children()[0].scrollIntoView();
            }
            
        });
    });

})(UIkit);
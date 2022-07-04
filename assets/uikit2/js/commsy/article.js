;(function(UI){

    "use strict";

    $(".newArticle").on('click', function() {
        // Create new section element in discussion view
        
        var url = $(this).data('article-url');
        // send ajax request to get new section item
        $.ajax({
            url: url
        })
        .done(function(result) {
            let $result = $(result);

            let parentId = $result.data('parentid');

            if (parentId != 0) {
                let $liNode = $('li[data-id="' + parentId + '"]');

                let $ul;
                if (!$liNode.children('ul').length) {
                    $ul = $('<ul>');
                    $liNode.append($ul);
                } else {
                    $ul = $liNode.children('ul');
                }

                let $li = $('<li>').prependTo($ul);
                $li.append($result);
                $li[0].scrollIntoView();
            } else {
                if ($('.discussion-article').length) {
                    let $mainUl = $('#article-content').children('ul')[0];

                    let $li = $('<li>').prependTo($mainUl);
                    $li.append($result);
                    $li[0].scrollIntoView();
                } else {
                    $('#article-content').html(result);
                    $('#article-content').children()[0].scrollIntoView();
                }
            }
        });
    });

})(UIkit);
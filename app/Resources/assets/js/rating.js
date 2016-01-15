;(function(UI) {

    "use strict";

    var hasVoted = false;

    $('.cs-rating').hover(function(e){
            if (!hasVoted) {
                let ratingData = $(this).data('cs-rating');
                $('.cs-rating').each(function(){
                    changeStar($(this), false);
                    let tempDataRating = $(this).data('cs-rating');
                    if (tempDataRating.rating <= ratingData.rating) {
                        changeStar($(this), true);
                    }
                });
            }
        }
    );

    $('.cs-rating').on('click', function(e){
            e.preventDefault();
            console.log($(this).data('cs-rating'));
            hasVoted = true;
        }
    );
    
    $('#cs-rating-wrapper').mouseleave(function(e){
            if (!hasVoted) {
                $('.cs-rating').each(function(){
                    changeStar($(this), false);
                });
            }
        }
    );
    
    function changeStar (el, selected) {
        if (selected) {
            el.find('i').removeClass('uk-icon-star-o');
            el.find('i').addClass('uk-text-warning');
            el.find('i').addClass('uk-icon-star');
        } else {
            el.find('i').removeClass('uk-text-warning');
            el.find('i').removeClass('uk-icon-star');
            el.find('i').addClass('uk-icon-star-o');
        }
    }

})(UIkit);
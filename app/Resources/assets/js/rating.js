;(function(UI) {
    
    "use strict";

    if ($('#cs-rating-wrapper').data('cs-rating-wrapper')) {
        var hasVoted = $('#cs-rating-wrapper').data('cs-rating-wrapper').hasVoted;
    }

    initRating();

    function initRating() {
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
                hasVoted = true;
                $.ajax({
                  url: $(this).data('cs-rating').url
                })
                .done(function(result) {
                    $('#cs-rating-div').replaceWith(result);
                    initRating();
                });
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
        
        $('#cs-rating-remove').on('click', function(e){
                e.preventDefault();
                hasVoted = false;
                $.ajax({
                  url: $(this).data('cs-rating-remove').url
                })
                .done(function(result) {
                    $('.cs-rating').each(function(){
                        changeStar($(this), false);
                    });
                    $('#cs-rating-div').replaceWith(result);
                    initRating();
                });
            }
        );
    }
    
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
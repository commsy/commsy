;(function(UI) {

    "use strict";

    var feedStart = 10;

    // listen to "inview.uk.scrollspy" event on "feed-load-more" classes
    $('.room-load-more').on('inview.uk.scrollspy', function() {
        var el = $(this);

        // get current query string
        var queryString = document.location.search;

        // build up the url
        var url = el.data('rooms').url  + feedStart + queryString;

        // send ajax request to get more items
        $.ajax({
          url: url
        })
        .done(function(result) {
            if ($(result).filter('li').length) {
                // append the data
                var target = el.data('rooms').target;
                $(target).append(result);
    
                // increase for next run
                feedStart += 10;
            } else {
                $('.room-load-more').css('display', 'none');
            }
        });
    });

    $('#cs-moderation-support-link').on('click', function(e){
        e.preventDefault();
        
        let moderationSupport = $(this);
        
        $('#cs-moderation-support-link').toggleClass('uk-hidden');
        $('#cs-moderation-support-spinner').toggleClass('uk-hidden');
        
        $.ajax({
          url: moderationSupport.data('form-url'),
        })
        .done(function(result) {
            $('#cs-moderation-support').html($(result));
            $('#cs-moderation-support-spinner').toggleClass('uk-hidden');
            $('#cs-moderation-support').toggleClass('uk-hidden');
            
            $('#moderationsupport_send').on('click', function (event) {
                event.preventDefault();
                
                $('#moderationsupport_subject').removeClass('uk-form-danger');
                $('#moderationsupport_message').removeClass('uk-form-danger');
                
                if ($('#moderationsupport_subject').val() == '' || $('#moderationsupport_message').val() == '') {
                    UIkit.notify({
                        message : 'Bitte Betreff und Nachricht eingeben',
                        status  : '',
                        timeout : 5550,
                        pos     : 'top-center'
                    });
                    
                    if ($('#moderationsupport_subject').val() == '') {
                        $('#moderationsupport_subject').addClass('uk-form-danger');
                    }
                    
                    if ($('#moderationsupport_message').val() == '') {
                        $('#moderationsupport_message').addClass('uk-form-danger');
                    }
                } else {
                    $('#cs-moderation-support-spinner').toggleClass('uk-hidden');
                    $('#cs-moderation-support').toggleClass('uk-hidden');
                    
                    $.ajax({
                        url: moderationSupport.data('form-url'),
                        type: "POST",
                        data: $('#cs-moderation-support').find('form').serialize()
                    })
                    .done(function(result) {
                        $('#cs-moderation-support').html('');
                        $('#cs-moderation-support-link').toggleClass('uk-hidden');
                        $('#cs-moderation-support-spinner').toggleClass('uk-hidden');
                        
                        UIkit.notify({
                            message : result.message,
                            status  : result.status,
                            timeout : 5550,
                            pos     : 'top-center'
                        });
                    });
                }
            });
            
            $('#moderationsupport_cancel').on('click', function (event) {
                event.preventDefault();
                $('#cs-moderation-support-link').toggleClass('uk-hidden');
                $('#cs-moderation-support').toggleClass('uk-hidden');
                $('#cs-moderation-support').html('');
            });
        });
    });

})(UIkit);
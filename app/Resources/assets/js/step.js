;(function(UI){

    "use strict";

    $(".newStep").on('click', function(){
        // Create new step element in todo view
        
        var url = $(this).data('stepUrl');
        // send ajax request to get new step item
        $.ajax({
            url: url
        })
        .done(function(result) {
            // set step item in todo view

            if ($('.todo-step').last()[0]) {
                $('.todo-step').last().after(result);
                $('.todo-step').last()[0].scrollIntoView();
            } else {
                $('#step-content').html(result);
                $('#step-content').children()[0].scrollIntoView();
            }
            
        });
    });

    // THIS IS NOT USED RIGHT NOW BECAUSE TODO STEPS ARE NOT SORTABLE, YET!
    // UIkit.on('changed.uk.dom', function(event) {
    //     $("#sorting_save").unbind().on('click', function() {
    //         var article = $("#sorting_cancel").parents('.cs-edit-section');

    //         // show the loading spinner
    //         $(article).find('.cs-edit-spinner').toggleClass('uk-hidden', false);

    //         var sorting = [];
    //         $(".section-list li").each(function() {
    //             var id = $(this).attr('id').match(/([\d]+)/g);
    //             sorting.push(id);
    //         });
            
    //         $.ajax({
    //             type: "POST",
    //             url: $(this).data('ukUrl'),
    //             data: JSON.stringify(sorting)
    //         })
    //         .done(function(result) {
    //             location.reload();
    //         });
            
    //     });

    //     $("#sorting_cancel").unbind().on('click', function() {
    //         var article = $("#sorting_cancel").parents('.cs-edit-section');

    //         // show the loading spinner
    //         $(article).find('.cs-edit-spinner').toggleClass('uk-hidden', false);
    //         location.reload();
    //     });

    //     // remove insert title on click
    //     $('#remove-on-click input[type=text]').on('focus', function() {
    //         $(this).val("");
    //     });

    // });

})(UIkit);
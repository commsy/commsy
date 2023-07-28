;(function(UI){

    "use strict";

    $(".newStep").on('click', function(){
        // Create new step element in todo view

        let url = $(this).data('stepUrl');
        // send ajax request to get new step item
        $.ajax({
            url: url
        })
        .done(function(result) {
            // insert ajax response as last element in #step-content
            if ($('.todo-step').last()[0]) {
              $('.todo-step').last().after(result);
              $('.todo-step').last()[0].scrollIntoView();
            } else {
              $('#step-content').html(result);
              $('#step-content').children()[0].scrollIntoView();
            }
        });
    });

})(UIkit);

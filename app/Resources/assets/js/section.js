;(function(UI){

    "use strict";

    $("#newSection").on('click', function(){
        // Create new section element in material view
        
        var url = $(this).data('sectionUrl');
        // send ajax request to get new section item
        $.ajax({
            url: url
        })
        .done(function(result) {
            // set section item in material view
            $('.material-section').last().after(result);
            $('.material-section').last()[0].scrollIntoView();
        });
    });

})(UIkit);
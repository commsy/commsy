;(function(UI){

    "use strict";

    $(".newSection").on('click', function(){
        // Create new section element in material view
        
        var url = $(this).data('sectionUrl');
        // send ajax request to get new section item
        $.ajax({
            url: url
        })
        .done(function(result) {
            // set section item in material view
            $('.material-section').last().after(result);
            if ($('.material-section').last()) {
                $('.material-section').last()[0].scrollIntoView();
            }
            
        });
    });

    $('#commsy-section-action-delete').on('click', function() {
        let $this = this;
        event.preventDefault();
        UIkit.modal.confirm($(this).data('confirm-delete'), function() {
            $.ajax({
                url: $($this).data('sectionUrl'),
                type: 'POST',
                data: {}
            })
            .done(function(result) {
                $($this).parents('.material-section').hide();
            });

        }, {
            labels: {
                Cancel: $(this).data('confirm-delete-cancel'),
                Ok: $(this).data('confirm-delete-confirm')
            }
        });
    })

    UIkit.on('changed.uk.dom', function(event) {
        $("#sorting_save").unbind().on('click', function() {
            var article = $("#sorting_cancel").parents('.cs-edit-section');

            // show the loading spinner
            $(article).find('.cs-edit-spinner').toggleClass('uk-hidden', false);

            var sorting = [];
            $(".section-list li").each(function() {
                var id = $(this).attr('id').match(/([\d]+)/g);
                sorting.push(id);
            });
            
            $.ajax({
                type: "POST",
                url: $(this).data('ukUrl'),
                data: JSON.stringify(sorting)
            })
            .done(function(result) {
                location.reload();
            });
            
        });

        $("#sorting_cancel").unbind().on('click', function() {
            var article = $("#sorting_cancel").parents('.cs-edit-section');

            // show the loading spinner
            $(article).find('.cs-edit-spinner').toggleClass('uk-hidden', false);
            location.reload();
        });

        // remove insert title on click
        $('#remove-on-click input[type=text]').on('focus', function() {
            $(this).val("");
        });

    });

})(UIkit);
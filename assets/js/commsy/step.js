;(function(UI){

    "use strict";

    var handleForm = function(form) {
        form.find('button').click(function(event) {
            let $button = $(this);

            // cancel is not handled via ajax
            if ($button.attr('name') == "step[save]") {
                let form = $(this).closest('form');
                if (form[0].checkValidity()) {
                    event.preventDefault();

                    let formData = form.serializeArray();
                    formData.push({ name: this.name, value: this.value });

                    // submit the form manually
                    $.ajax({
                        url: form.attr('action'),
                        type: "POST",
                        data: formData
                    })
                    .done(function(result, statusText, xhrObject) {
                        let $result = $(result);

                        if ($result.find('ul.form-errors').length) {
                            // if form is invalid, replace with html response
                            let lastChild = $('#step-content').children().last();
                            lastChild.replaceWith($result);
                            handleForm($result.find('form'));
                        } else {
                            window.location.reload(true);
                        }
                    });
                }
            }
        });
    };

    $(".newStep").on('click', function(){
        // Create new step element in todo view

        let url = $(this).data('stepUrl');
        // send ajax request to get new step item
        $.ajax({
            url: url
        })
        .done(function(result) {
            // insert ajax response as last element in #step-content
            $('#step-content').append(result);

            // scroll to the appended element
            let lastChild = $('#step-content').children().last();
            if (lastChild) {
                lastChild[0].scrollIntoView();
            }

            // override form submit behaviour
            handleForm(lastChild.find('form'));
        });
    });

})(UIkit);
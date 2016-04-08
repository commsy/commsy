;(function(UI) {
    
    "use strict";

    let articles;
    let inputs;
    let selectedCounter;
    let selectAll;
    let selectable;
    let sort;
    let sortOrder;

    $('#commsy-select-actions-mark-read').on('click', function(event) {
        event.stopPropagation();
        event.preventDefault();
        startEdit($(this));
    });
    
    $('#commsy-select-actions-copy').on('click', function(event) {
        event.stopPropagation();
        event.preventDefault();
        startEdit($(this));
    });
    
    $('#commsy-select-actions-save').on('click', function(event) {
        event.stopPropagation();
        event.preventDefault();
        startEdit($(this));
    });
    
    $('#commsy-select-actions-delete').on('click', function(event) {
        event.stopPropagation();
        event.preventDefault();
        startEdit($(this));
    });

    $('#commsy-select-actions-send-list').on('click', function(event) {
        event.stopPropagation();
        event.preventDefault();
        startEdit($(this));
    });

    function startEdit (element) {
        console.log('startEdit ...');
        
        let $this = this;

        let target = $(element.data('commsy-list-action').target) ? UI.$(element.data('commsy-list-action').target) : [];
        if (!target.length) return;

        articles = target.find('article');    
        inputs = target.find('input');
        selectedCounter = 0;
        selectAll = false;
        selectable = false;
        sort = 'date';
        sortOrder = '';
        
        // show / hide further actions
        $('#commsy-select-actions').toggleClass('uk-hidden');
        $('#commsy-select-actions').parent('.uk-sticky-placeholder').css('height', '65px');
        //$(this).html($(this).data('alt-title'));

        $('#commsy-list-count-selected').html('0');

        articles.toggleClass('selectable');
        
        $('#commsy-list-count-display').toggleClass('uk-hidden');
        $('#commsy-list-count-edit').toggleClass('uk-hidden');
        
        selectable = true; 
        
        bind();       
    }
    
    function bind () {
        articles.off().on('click', function(event) {
            let article = $(this);
    
            // select mode?
            if (article.hasClass('selectable')) {
                let checkbox = article.find('input[type="checkbox"]').first();
    
                // only select if element has a checkbox
                if (checkbox.length) {
                    // highlight the article
                    article.toggleClass('uk-comment-primary');
    
                    // toggle checkbox
                    checkbox.prop('checked', article.hasClass('uk-comment-primary'));
    
                    if (checkbox.prop('checked')) {
                        selectedCounter++;
                    } else {
                        selectedCounter--;
                    }
                    $('#commsy-list-count-selected').html(selectedCounter);
    
                    // disable normal click behaviour
                    event.preventDefault();
                }
            }
        });
    
        // handle clicks on inputs
        inputs.off().on('click', function(event) {
            event.stopPropagation();
            $(this).parents('article').click();
        });
    }
        

})(UIkit);
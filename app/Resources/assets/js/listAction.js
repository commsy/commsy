;(function(UI) {
    
    "use strict";

    let element;
    let articles;
    let inputs;
    let selectedCounter;
    let selectAll;
    let selectable;
    let sort;
    let sortOrder;
    let action;
    let actionUrl;
    let errorMessage;

    $('#commsy-select-actions-mark-read').on('click', function(event) {
        event.stopPropagation();
        event.preventDefault();
        action = 'markread';
        startEdit($(this));
    });
    
    $('#commsy-select-actions-copy').on('click', function(event) {
        event.stopPropagation();
        event.preventDefault();
        action = 'copy';
        startEdit($(this));
    });
    
    $('#commsy-select-actions-save').on('click', function(event) {
        event.stopPropagation();
        event.preventDefault();
        action = 'save';
        startEdit($(this));
    });
    
    $('#commsy-select-actions-delete').on('click', function(event) {
        event.stopPropagation();
        event.preventDefault();
        action = 'delete';
        startEdit($(this));
    });

    $('#commsy-select-actions-send-list').on('click', function(event) {
        event.stopPropagation();
        event.preventDefault();
        action = 'send-list';
        startEdit($(this));
    });

    $('#commsy-select-actions-ok').on('click', function(event) {
        event.stopPropagation();
        event.preventDefault();
        performAction();
    });

    $('#commsy-select-actions-cancel').on('change.uk.button', function(event) {
        stopEdit();
    });

    $('#commsy-select-actions-select-all').on('change.uk.button', function(event) {
        $(this).addClass('uk-active');
        $('#commsy-select-actions-select-shown').removeClass('uk-active');
        
        inputs.each(function() {
            if (this.type == 'checkbox') {
                $(this).prop('checked', true);
            }
        });
        articles.each(function() {
            $(this).addClass('uk-comment-primary');
        });
        
        selectedCounter = parseInt($('#commsy-list-count-all').html());
        
        $('#commsy-list-count-selected').html($('#commsy-list-count-all').html());
        
        selectAll = true;
    });
    
    $('#commsy-select-actions-unselect').on('change.uk.button', function(event) {
        $('#commsy-select-actions-select-shown').removeClass('uk-active');
        $('#commsy-select-actions-select-all').removeClass('uk-active');
        $(this).removeClass('uk-active');
        
        inputs.each(function() {
            if (this.type == 'checkbox') {
                $(this).prop('checked', false);
            }
        });
        articles.each(function() {
            $(this).removeClass('uk-comment-primary');
        });
        
        selectedCounter = 0;
        $('#commsy-list-count-selected').html('0');

        selectAll = false;
    });

    function stopEdit () {
        $('#commsy-select-actions').toggleClass('uk-hidden');
        $('#commsy-select-actions').parent('.uk-sticky-placeholder').css('height', '0px');

        inputs.each(function() {
            if (this.type == 'checkbox') {
                $(this).prop('checked', false);
            }
        });
        articles.each(function() {
            $(this).removeClass('uk-comment-primary');
        });
        $(this).html($(this).data('title'));
        
        articles.toggleClass('selectable');
        
        selectedCounter = 0;
        $('#commsy-list-count-selected').html('0');
        
        $('#commsy-list-count-display').toggleClass('uk-hidden');
        $('#commsy-list-count-edit').toggleClass('uk-hidden');
        
        selectAll = false;
        selectable = false;
    }

    function startEdit (el) {
        element = el;
        
        actionUrl = element.data('commsy-list-action').actionUrl;

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
        
        $('#commsy-select-actions-select-shown').removeClass('uk-active');
        $('#commsy-select-actions-select-all').removeClass('uk-active');
        $('#commsy-select-actions-unselect').removeClass('uk-active');
        $('#commsy-select-actions-ok').removeClass('uk-active');
        $('#commsy-select-actions-cancel').removeClass('uk-active');
        
        selectable = true; 
        
        bind();       
    }
    
    UI.$html.on('changed.uk.dom', function(e) {
        if  (element) {
            let target = $(element.data('commsy-list-action').target) ? UI.$(element.data('commsy-list-action').target) : [];
            if (!target.length) return;
            
            articles = target.find('article');
            inputs = target.find('input');
    
            if (articles.first().hasClass('selectable')) {
                articles.addClass('selectable');
            }
    
            bind();
        }
    });
    
    window.addEventListener('feedDidLoad', function (e) {
        if  (element) {
            let target = $(element.data('commsy-list-action').target) ? UI.$(element.data('commsy-list-action').target) : [];
            if (!target.length) return;
                
            articles = target.find('article');
            inputs = target.find('input');
            
            if (articles.first().hasClass('selectable')) {
                articles.addClass('selectable');
            }
            
            if (selectAll == true) {
                var inputCounter = 0;
                inputs.each(function() {
                    if (inputCounter >= e.detail.feedStart) {
                        if (this.type == 'checkbox') {
                            $(this).prop('checked', true);
                        }
                    }
                    inputCounter++;
                });
    
                var articlesCounter = 0;
                articles.each(function() {
                    if (articlesCounter >= e.detail.feedStart) {
                        $(this).addClass('uk-comment-primary');
                    }
                    articlesCounter++;
                }); 
            }
            
            bind();
        }
    });
    
    window.addEventListener('feedDidReload', function (e) {
        let target = $(element.data('commsy-list-action').target) ? UI.$(element.data('commsy-list-action').target) : [];
        if (!target.length) return;
        
        articles = target.find('article');
        inputs = target.find('input');

        if (selectable) {
            articles.addClass('selectable');
        }

        bind();
    });
    
    $('#commsy-sort-title').on('click', function(event) {
        setSort('title');
    });
    
    $('#commsy-sort-modificator').on('click', function(event) {
        setSort('modificator');
    });
    
    $('#commsy-sort-date').on('click', function(event) {
        setSort('date');
    });
    
    $('#commsy-sort-assessment').on('click', function(event) {
        setSort('assessment');
    });
    
    $('#commsy-sort-workflow_status').on('click', function(event) {
        setSort('workflow_status');
    });
    
    function setSort (newSort) {
        if (newSort == sort) {
            if (sortOrder == '') {
                sortOrder = '_rev';
            } else {
                sortOrder = '';
            }
        } else {
            sortOrder = '';
        }
        sort = newSort;
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
     
    function performAction () {
        let target = $(element.data('commsy-list-action').target) ? UI.$(element.data('commsy-list-action').target) : [];
        
        let entries =  target.find('input:checked').map(function() {
            return this.value;
        }).get();
        
        let input =  target.find('input').map(function() {
            return this.value;
        }).get();
        
        if (entries.length > 0) {
            if (action != 'save' && action != 'send-list') {
                // send action request
                
                let execute = true;
                if (action == 'delete') {
                    execute = false;
                    UIkit.modal.confirm(element.data('confirm-delete'), function() {
                        execute = true;
                    }, {
                        labels: {
                            Cancel: element.data('confirm-delete-cancel'),
                            Ok: element.data('confirm-delete-confirm')
                        }
                    });
                }
                if (execute) {
                    $.ajax({
                        url: actionUrl,
                        type: 'POST',
                        data: {
                            act: action,
                            data: JSON.stringify(entries),
                            selectAll: selectAll,
                            selectAllStart: input.length
                        }
                    }).done(function(result) {
                        $('#commsy-select-actions-select-shown').removeClass('uk-active');
                        $('#commsy-select-actions-select-all').removeClass('uk-active');
                        $('#commsy-select-actions-unselect').removeClass('uk-active');
                        
                        target.find('input[type="checkbox"]').each(function() {
                            $(this).prop('checked', false);
                        });
                        target.find('article').each(function() {
                            $(this).removeClass('uk-comment-primary');
                        });
    
                        if (action == 'copy') {
                            let $indicator = $('#cs-nav-copy-indicator');
                            $indicator.html(result.data.count);
                        }
                        
                        // reload feed
                        reloadFeed(result);
                        stopEdit();
                    }).fail(function(jqXHR, textStatus, errorThrown) {
                        UIkit.notify(errorMessage, 'danger');
                    });
                }
            } else if (action == 'save') {
                let $form = $(document.createElement('form'))
                    .css({
                        display: 'none'
                    })
                    .attr('method', 'POST')
                    .attr('action', actionUrl);

                for (let i = 0; i < entries.length; i++) { 
                    let input = $(document.createElement('input')).attr('name','data[]').val(entries[i]);
                    $form.append(input);
                }

                let input = $(document.createElement('input')).attr('name','act').val('save');

                $form.append(input);
                $('body').append($form);
                $form.submit();
            } else if (action == 'send-list') {
                // send ajax request
                $.ajax({
                    url: $('#commsy-select-actions-send-list').data('cs-action-send-list').url,
                    type: 'POST',
                    data: JSON.stringify({
                    })
                }).done(function(data, textStatus, jqXHR) {
                    if (!jqXHR.responseJSON) {
                        // if we got back html, embed the form
                        let feedDom = $('.feed');

                        if (feedDom.length) {
                            feedDom.prepend(data);
                        }

                        setupForm();
                    } else {
                        console.log('json response');
                        console.log(data);
                    }

                }).fail(function(jqXHR, textStatus, errorThrown) {
                    UIkit.notify(errorMessage, 'danger');
                });
            }
        } else {
            UIkit.notify({
                message : $(element[0]).data('no-selection'),
                status  : 'warning',
                timeout : 5550,
                pos     : 'top-center'
            });
        }
        
        selectAll = false;
    }
    
    function reloadFeed ({message, status, timeout}) {
        let el = $('.feed-load-more');
        if (!el.length) {
            el = $('.feed-load-more-grid');    
        }
        
        let queryString = document.location.search;
        let url = el.data('feed').url  + 0 + '/' + sort + sortOrder + queryString;

        $.ajax({
          url: url
        }).done(function(result) {
            let foundArticles = false;
            if ($(result).filter('article').length) {
                foundArticles = true;
            } else if ($(result).find('article').length) {
                foundArticles = true
            }
            
            if (foundArticles) {
                let target = el.data('feed').target;
                $(target).empty();
                $(target).html(result);
                $(target).trigger('changed.uk.dom');
                
                bind();
                
                UIkit.notify({
                    message : message,
                    status  : status,
                    timeout : timeout,
                    pos     : 'top-center'
                });
            }
        }).fail(function(jqXHR, textStatus, errorThrown) {
            UIkit.notify(errorMessage, 'danger');
        });
    }

})(UIkit);
;(function(UI) {

    'use strict';

    UI.component('select', {

        defaults: {
        },

        boot: function() {
            // init code
            UI.ready(function(context) {
                UI.$('[data-commsy-select]', context).each(function() {
                    let element = UI.$(this);

                    if (!element.data('select')) {
                        let obj = UI.select(element, UI.Utils.options(element.attr('data-commsy-select')));
                    }
                });
            });
        },

        init: function() {
            let $this = this;

            let target = this.options.target ? UI.$(this.options.target) : [];
            if (!target.length) return;

            this.articles = target.find('article');
            this.inputs = target.find('input');
            this.selectedCounter = 0;
            this.selectAll = false;
            this.selectable = false;
            this.sort = 'date';
            this.sortOrder = '';
            
            // bind event handler
            this.bind();

            // button change
            this.on('change.uk.button', function(event) {
                // show / hide further actions
                $('#commsy-select-actions').toggleClass('uk-hidden');
                $('#commsy-select-actions').parent('.uk-sticky-placeholder').css('height', '65px');
                $(this).html($(this).data('alt-title'));

                $('#commsy-list-count-selected').html('0');

                $this.articles.toggleClass('selectable');
                
                $('#commsy-list-count-display').toggleClass('uk-hidden');
                $('#commsy-list-count-edit').toggleClass('uk-hidden');
                
                $this.selectable = true;
            });
            
            $('#commsy-select-actions-select-all').on('change.uk.button', function(event) {
                $(this).addClass('uk-active');
                $('#commsy-select-actions-select-shown').removeClass('uk-active');
                
                $this.inputs.each(function() {
                    if (this.type == 'checkbox') {
                        $(this).prop('checked', true);
                    }
                });
                $this.articles.each(function() {
                    $(this).addClass('uk-comment-primary');
                });
                
                $this.selectedCounter = parseInt($('#commsy-list-count-all').html());
                
                $('#commsy-list-count-selected').html($('#commsy-list-count-all').html());
                
                $this.selectAll = true;
            });
            
            $('#commsy-select-actions-unselect').on('change.uk.button', function(event) {
                $('#commsy-select-actions-select-shown').removeClass('uk-active');
                $('#commsy-select-actions-select-all').removeClass('uk-active');
                $(this).removeClass('uk-active');
                
                $this.inputs.each(function() {
                    if (this.type == 'checkbox') {
                        $(this).prop('checked', false);
                    }
                });
                $this.articles.each(function() {
                    $(this).removeClass('uk-comment-primary');
                });
                
                $this.selectedCounter = 0;
                $('#commsy-list-count-selected').html('0');

                $this.selectAll = false;
            });
            
            $('#commsy-select-actions-mark-read').on('click', function(event) {
                event.preventDefault();
                $this.action('markread');
            });
            
            $('#commsy-select-actions-copy').on('click', function(event) {
                event.preventDefault();
                $this.action('copy');
            });
            
            $('#commsy-select-actions-save').on('click', function(event) {
                event.preventDefault();
                $this.action('save');
            });
            
            $('#commsy-select-actions-delete').on('click', function(event) {
                event.preventDefault();
                
                let target = $this.options.target ? UI.$($this.options.target) : [];
                let entries =  target.find('input:checked').map(function() {
                    return this.value;
                }).get();
                
                if (entries.length > 0) {
                    UIkit.modal.confirm($($this.element).data('confirm-delete'), function() {
                        $this.action('delete');
                    }, {
                        labels: {
                            Cancel: $($this.element[0]).data('confirm-delete-cancel'),
                            Ok: $($this.element[0]).data('confirm-delete-confirm')
                        }
                    });
                } else {
                    UIkit.notify({
                        message : $($this.element[0]).data('no-selection'),
                        status  : 'warning',
                        timeout : 5550,
                        pos     : 'top-center'
                    });
                }
            });

            $('#commsy-select-actions-send-list').on('click', function(event) {
                event.preventDefault();
                $this.action('send-list');
            });

            $('#commsy-select-actions-cancel').on('change.uk.button', function(event) {
                $('#commsy-select-actions').toggleClass('uk-hidden');
                $('#commsy-select-actions').parent('.uk-sticky-placeholder').css('height', '0px');

                $this.inputs.each(function() {
                    if (this.type == 'checkbox') {
                        $(this).prop('checked', false);
                    }
                });
                $this.articles.each(function() {
                    $(this).removeClass('uk-comment-primary');
                });
                $(this).html($(this).data('title'));
                
                $this.articles.toggleClass('selectable');
                
                $this.selectedCounter = 0;
                $('#commsy-list-count-selected').html('0');
                
                $('#commsy-list-count-display').toggleClass('uk-hidden');
                $('#commsy-list-count-edit').toggleClass('uk-hidden');
                
                $this.selectAll = false;
                $this.selectable = false;
            });

            // listen for dom changes
            UI.$html.on('changed.uk.dom', function(e) {
                $this.articles = target.find('article');
                $this.inputs = target.find('input');

                if ($this.articles.first().hasClass('selectable')) {
                    $this.articles.addClass('selectable');
                }

                $this.bind();
            });
            
            window.addEventListener('feedDidLoad', function (e) {
                if ($this.selectAll == true) {
                    $this.articles = target.find('article');
                    $this.inputs = target.find('input');

                    var inputCounter = 0;
                    $this.inputs.each(function() {
                        if (inputCounter >= e.detail.feedStart) {
                            if (this.type == 'checkbox') {
                                $(this).prop('checked', true);
                            }
                        }
                        inputCounter++;
                    });

                    var articlesCounter = 0;
                    $this.articles.each(function() {
                        if (articlesCounter >= e.detail.feedStart) {
                            $(this).addClass('uk-comment-primary');
                        }
                        articlesCounter++;
                    }); 
                }
            });
            
            window.addEventListener('feedDidReload', function (e) {
                $this.articles = target.find('article');
                $this.inputs = target.find('input');

                if ($this.selectable) {
                    $this.articles.addClass('selectable');
                }

                $this.bind();
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
                if (newSort == $this.sort) {
                    if ($this.sortOrder == '') {
                        $this.sortOrder = '_rev';
                    } else {
                        $this.sortOrder = '';
                    }
                } else {
                    $this.sortOrder = '';
                }
                $this.sort = newSort;
            }
        },

        bind: function() {
            let $this = this;
            
            // handle clicks on articles
            
            this.articles.off().on('click', function(event) {
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
                            $this.selectedCounter++;
                        } else {
                            $this.selectedCounter--;
                        }
                        $('#commsy-list-count-selected').html($this.selectedCounter);

                        // disable normal click behaviour
                        event.preventDefault();
                    }
                }
            });

            // handle clicks on inputs
            this.inputs.off().on('click', function(event) {
                event.stopPropagation();
                $(this).parents('article').click();
            });
        },
        
        action: function(action) {
            let $this = this;
            let target = this.options.target ? UI.$(this.options.target) : [];
            
            let entries =  target.find('input:checked').map(function() {
                return this.value;
            }).get();
            
            let input =  target.find('input').map(function() {
                return this.value;
            }).get();
            
            if (entries.length > 0) {
                if (action != 'save' && action != 'send-list') {
                    // send action request
                    $.ajax({
                        url: $this.options.actionUrl,
                        type: 'POST',
                        data: {
                            act: action,
                            data: JSON.stringify(entries),
                            selectAll: $this.selectAll,
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
                        $this.reloadFeed(result);
                    }).fail(function(jqXHR, textStatus, errorThrown) {
                        UIkit.notify($this.options.errorMessage, 'danger');
                    });
                } else if (action == 'save') {
                    let $form = $(document.createElement('form'))
                        .css({
                            display: 'none'
                        })
                        .attr('method', 'POST')
                        .attr('action', $this.options.actionUrl);
    
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
    
                            $this.setupForm();
                        } else {
                            console.log('json response');
                            console.log(data);
                        }
    
                    }).fail(function(jqXHR, textStatus, errorThrown) {
                        UIkit.notify($this.options.errorMessage, 'danger');
                    });
                }
            } else {
                UIkit.notify({
                    message : $($this.element[0]).data('no-selection'),
                    status  : 'warning',
                    timeout : 5550,
                    pos     : 'top-center'
                });
            }
            
            $this.selectAll = false;
        },

        copy: function() {

        },

        reloadFeed: function({message, status, timeout}) {
            let $this = this;

            let el = $('.feed-load-more');
            if (!el.length) {
                el = $('.feed-load-more-grid');    
            }
            
            let queryString = document.location.search;
            let url = el.data('feed').url  + 0 + '/' + $this.sort + $this.sortOrder + queryString;

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
                    
                    $(target).find('article').each(function() {
                        $(this).toggleClass('selectable');
                    });
                    
                    $this.bind();
                    
                    UIkit.notify({
                        message : message,
                        status  : status,
                        timeout : timeout,
                        pos     : 'top-center'
                    });
                }
            }).fail(function(jqXHR, textStatus, errorThrown) {
                UIkit.notify($this.options.errorMessage, 'danger');
            });
        },
        
        setupForm: function() {
            let $this = this;
            let target = this.options.target ? UI.$(this.options.target) : [];
            
            $('.feed').find('form').submit(function (event) {
                event.preventDefault();
    
                let entries =  $('.feed').find('input:checked').map(function() {
                    return this.value;
                }).get();
    
                $('#sendList_entries').attr('value', entries);
    
                // submit the form manually
                $.ajax({
                    url: $('#commsy-select-actions-send-list').data('cs-action-send-list').url,
                    type: "POST",
                    data: $(this).serialize()
                })
                .done(function(result) {
                    $('.feed').find('form').remove();
                    
                    $('#commsy-select-actions-select-shown').removeClass('uk-active');
                    $('#commsy-select-actions-select-all').removeClass('uk-active');
                    $('#commsy-select-actions-unselect').removeClass('uk-active');
                        
                    target.find('input[type="checkbox"]').each(function() {
                        $(this).prop('checked', false);
                    });
                    target.find('article').each(function() {
                        $(this).removeClass('uk-comment-primary');
                    });
                    
                    $this.reloadFeed(result);
                });
            });
        }
    });

})(UIkit);
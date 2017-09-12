;(function(UI) {

    /*
     Action in template:

     <a href="#" class="commsy-select-action" data-uk-button data-commsy-list-action='{"target":".feed", "actionUrl": "{{ path('commsy_user_feedaction', {'roomId': roomId}) }}", "action": "user-delete"}'>
     <i class="uk-icon-justify uk-icon-small uk-icon-remove uk-visible-large"></i> {{ 'delete'|trans({},'user')|capitalize }}
     </a>

     - "class" must be "commsy-select-action"
     - "data-commsy-list-action" must contain the following values:
     - "target"      -> usualy the div where feed-entries can be selected and the returned feed-entries from the ajax call are inserted
     - "actionUrl"   -> path to controller
     - "action"      -> key that is send to controller
     */

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

    $('.commsy-select-action').on('click', function(event) {
        event.stopPropagation();
        event.preventDefault();
        action = $(this).data('commsy-list-action').action;
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

        articles.toggleClass('selectable', false);

        selectedCounter = 0;
        $('#commsy-list-count-selected').html('0');

        $('#commsy-list-count-display').toggleClass('uk-hidden');
        $('#commsy-list-count-edit').toggleClass('uk-hidden');

        $(".feed .uk-grid.uk-text-truncate div").css("padding-left", "35px");
        $(".feed .uk-grid .uk-icon-sign-in").toggleClass("uk-hidden");

        selectAll = false;
        selectable = false;

        reloadFeed (new Array('', 0, 0), true);
    }

    function startEdit(el)
    {
        element = el;

        actionUrl = element.data('commsy-list-action').actionUrl;

        let target = $(element.data('commsy-list-action').target) ? UI.$(element.data('commsy-list-action').target) : [];
        if (!target.length) {
            return;
        }

        selectable = true;

        articles = target.find('article');

        addCheckboxes(articles);

        inputs = target.find('input');
        selectedCounter = 0;
        selectAll = false;
        sort = 'date';
        sortOrder = '';

        // show / hide further actions
        $('#commsy-select-actions').toggleClass('uk-hidden');
        $('#commsy-select-actions').parent('.uk-sticky-placeholder').css('height', '65px');

        $('#commsy-list-count-selected').html('0');

        $('#commsy-list-count-display').toggleClass('uk-hidden');
        $('#commsy-list-count-edit').toggleClass('uk-hidden');

        $('#commsy-select-actions-select-shown').removeClass('uk-active');
        $('#commsy-select-actions-select-all').removeClass('uk-active');
        $('#commsy-select-actions-unselect').removeClass('uk-active');
        $('#commsy-select-actions-ok').removeClass('uk-active');
        $('#commsy-select-actions-cancel').removeClass('uk-active');

        $(".feed .uk-grid.uk-text-truncate div").css("padding-left", "0px");
        $(".feed .uk-grid .uk-icon-sign-in").toggleClass("uk-hidden");

        bind();
    }

    UI.$html.on('changed.uk.dom', function(e) {
        if  (element) {
            let target = $(element.data('commsy-list-action').target) ? UI.$(element.data('commsy-list-action').target) : [];
            if (!target.length) return;

            articles = target.find('article');

            inputs = target.find('input');

            if (articles.first().hasClass('selectable')) {
                //articles.addClass('selectable');
                addCheckboxes(articles);
            }

            bind();
        }
    });

    window.addEventListener('feedDidLoad', function (e) {
        if  (element) {
            let target = $(element.data('commsy-list-action').target) ? UI.$(element.data('commsy-list-action').target) : [];
            if (!target.length) return;

            articles = target.find('article');
            addCheckboxes(articles);

            inputs = target.find('input');

            if (articles.first().hasClass('selectable')) {
                //articles.addClass('selectable');
                addCheckboxes(articles);
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
        if (element) {
            let target = $(element.data('commsy-list-action').target) ? UI.$(element.data('commsy-list-action').target) : [];
            if (!target.length) return;

            articles = target.find('article');
            addCheckboxes(articles);

            inputs = target.find('input');

            if (selectable) {
                //articles.addClass('selectable');
                addCheckboxes(articles);
            }

            bind();
        }
    });

    $('#commsy-sort-title').on('click', function(event) {
        setSort('title');
    });

    $('#commsy-sort-modificator').on('click', function(event) {
        setSort('modificator');
    });

    $('#commsy-sort-creator').on('click', function(event) {
        setSort('creator');
    });

    $('#commsy-sort-date').on('click', function(event) {
        setSort('date');
    });

    $('#commsy-sort-latest').on('click', function(event) {
        setSort('latest');
    });

    $('#commsy-sort-assessment').on('click', function(event) {
        setSort('assessment');
    });

    $('#commsy-sort-workflow_status').on('click', function(event) {
        setSort('workflow_status');
    });

    $('#commsy-sort-status').on('click', function(event) {
        setSort('status');
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
            switch (action) {
                case 'save':
                    let $form = $(document.createElement('form'))
                        .css({
                            display: 'none'
                        })
                        .attr('method', 'POST')
                        .attr('action', actionUrl);

                    for (let i = 0; i < entries.length; i++) {
                        input = $(document.createElement('input')).attr('name','data[]').val(entries[i]);
                        $form.append(input);
                    }

                    input = $(document.createElement('input')).attr('name','act').val('save');

                    $form.append(input);
                    $('body').append($form);
                    $form.submit();

                    stopEdit();

                    break;

                case 'send-list':
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
                        }

                    }).fail(function(jqXHR, textStatus, errorThrown) {
                        UIkit.notify(errorMessage, 'danger');
                    });

                    break;

                case 'delete':
                case 'user-delete':
                    UIkit.modal.confirm(element.data('confirm-delete'), function() {
                        executeAction (actionUrl, entries, input, target);
                    }, {
                        labels: {
                            Cancel: element.data('confirm-delete-cancel'),
                            Ok: element.data('confirm-delete-confirm')
                        }
                    });

                    break;

                /* case 'sendmail':
                    // forward user to mailing form, providing user ids as query param
                    window.location.replace(actionUrl + '?' + $.param({ userIds: entries }));

                    break; */

                case 'user-block':
                case 'user-confirm':
                case 'user-status-reading-user':
                case 'user-status-user':
                case 'user-status-moderator':
                case 'user-contact':
                case 'user-contact-remove':
                    // forward user to user status change form, providing ids as query param
                    let uri = new URI(actionUrl);

                    // add ids
                    uri.setSearch('userIds[]', entries);

                    window.location.replace(uri.toString());

                    break;

                default:
                    executeAction (actionUrl, entries, input, target);

                    break;
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

    function executeAction (actionUrl, entries, input, target) {
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
            if ( typeof result.redirect != 'undefined' ) {
                window.location.replace(result.redirect);
            } else {
                $('#commsy-select-actions-select-shown').removeClass('uk-active');
                $('#commsy-select-actions-select-all').removeClass('uk-active');
                $('#commsy-select-actions-unselect').removeClass('uk-active');

                target.find('input[type="checkbox"]').each(function () {
                    $(this).prop('checked', false);
                });
                target.find('article').each(function () {
                    $(this).removeClass('uk-comment-primary');
                });

                if (action == 'copy' || action == 'remove') {
                    let $indicator = $('#cs-nav-copy-indicator');
                    $indicator.html(result.data.count);
                }

                if (action == 'remove') {
                    let $countDisplay = $('#commsy-list-count-display');
                    $countDisplay.html('(' + result.data.countSelected + ' - ' + result.data.count + ')')
                }

                if (action == 'user-delete') {
                    let $countDisplay = $('#commsy-list-count-display');
                    $countDisplay.html($countDisplay.html().replace(/\d+/g, function (match) {
                        return parseInt(match) - entries.length
                    }));
                }

                // reload feed
                reloadFeed(result, false);
                stopEdit();
            }
        }).fail(function(jqXHR, textStatus, errorThrown) {
            UIkit.notify(errorMessage, 'danger');
        });
    }

    function reloadFeed ({message, status, timeout}, hideMessage) {
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
            if (/<[a-z][\s\S]*>/i.test(result)){
                if ($(result).filter('article').length) {
                    foundArticles = true;
                } else if ($(result).find('article').length) {
                    foundArticles = true
                }
            }

            let target = el.data('feed').target;
            $(target).empty();

            if (foundArticles) {
                $(target).html(result);
                $(target).trigger('changed.uk.dom');

                bind();
            }

            if (!hideMessage) {
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

    function addCheckboxes (articles) {
        if (selectable) {
            articles.each(function() {
                if ($.inArray(action, $(this).data('allowed-actions')) > -1) {
                    $(this).toggleClass('selectable', true);
                }
            });
        }
    }

})(UIkit);

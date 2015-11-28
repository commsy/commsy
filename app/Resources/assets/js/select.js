;(function(UI) {

    "use strict";

    UI.component('select', {

        defaults: {
        },

        boot: function() {
            // init code
            UI.ready(function(context) {
                UI.$("[data-commsy-select]", context).each(function() {
                    var element = UI.$(this);

                    if (!element.data("select")) {
                        var obj = UI.select(element, UI.Utils.options(element.attr("data-commsy-select")));
                    }
                });
            });
        },

        init: function() {
            var $this = this;

            var target = this.options.target ? UI.$(this.options.target) : [];
            if (!target.length) return;

            this.articles = target.find('article');
            this.inputs = target.find('input');
            UI.modal.labels = {
            'Ok': $('#commsy-select-actions-delete').data('confirm-delete-confirm'),
            'Cancel': $('#commsy-select-actions-delete').data('confirm-delete-cancel')
            };

            this.bind();
            
            // button change
            this.on("change.uk.button", function(event) {
                $('#commsy-select-actions').toggleClass('uk-hidden')
                if ($('#commsy-select-actions').hasClass('uk-hidden')) {
                    $('#commsy-select-actions').parent('.uk-sticky-placeholder').css('height', '0px');
                    target.find('input[type="checkbox"]').each(function(){
                        $(this).prop('checked', false);
                    });
                    target.find('article').each(function(){
                        $(this).removeClass('uk-comment-primary');
                    });
                    $(this).html($(this).data('title'));
                } else {
                    $('#commsy-select-actions').parent('.uk-sticky-placeholder').css('height', '65px');
                    $(this).html($(this).data('alt-title'));
                }

                $this.articles.toggleClass('selectable');
            });
            
            $('#commsy-select-actions-select-all').on("change.uk.button", function(event) {
                $(this).addClass('uk-active');
                $('#commsy-select-actions-select-shown').removeClass('uk-active');
                
                target.find('input[type="checkbox"]').each(function(){
                    $(this).prop('checked', true);
                });
                target.find('article').each(function(){
                    $(this).addClass('uk-comment-primary');
                });
            });
            
            $('#commsy-select-actions-unselect').on("change.uk.button", function(event) {
                $('#commsy-select-actions-select-shown').removeClass('uk-active');
                $('#commsy-select-actions-select-all').removeClass('uk-active');
                $(this).removeClass('uk-active');
                
                target.find('input[type="checkbox"]').each(function(){
                    $(this).prop('checked', false);
                });
                target.find('article').each(function(){
                    $(this).removeClass('uk-comment-primary');
                });
            });
            
            $('#commsy-select-actions-mark-read').on("click", function(event) {
                event.preventDefault();
                $this.action('markread');
            });
            
            $('#commsy-select-actions-copy').on("click", function(event) {
                event.preventDefault();
                $this.action('copy');
            });
            
            $('#commsy-select-actions-save').on("click", function(event) {
                event.preventDefault();
                $this.action('save');
            });
            
            $('#commsy-select-actions-delete').on("click", function(event) {
                event.preventDefault();
                UIkit.modal.confirm($($this.element).data('confirm-delete'), function(){
                    $this.action('delete');
                });
            });

            // listen for dom changes
            UI.$html.on("changed.uk.dom", function(e) {
                $this.articles = target.find('article');
                $this.inputs = target.find('input');

                if ($this.articles.first().hasClass('selectable')) {
                    $this.articles.addClass('selectable');
                }

                $this.bind();
            });
        },

        bind: function() {
            // handle clicks on articles
            this.articles.off().on("click", function(event) {
                var article = $(this);

                // select mode?
                if (article.hasClass('selectable')) {
                    var checkbox = article.find('input[type="checkbox"]').first();

                    // only select if element has a checkbox
                    if (checkbox.length) {
                        // highlight the article
                        article.toggleClass('uk-comment-primary');

                        // toggle checkbox
                        checkbox.prop('checked', article.hasClass('uk-comment-primary'));

                        // disable normal click behaviour
                        event.preventDefault();
                    }
                }
            });

            // handle clicks on inputs
            this.inputs.off().on("click", function(event) {
                event.stopPropagation();
                $(this).parents('article').click();
            });
        },
        
        action: function(action) {
            var $this = this;
            var target = this.options.target ? UI.$(this.options.target) : [];
            
            var entries =  target.find('input:checked').map(function(){
                return this.value;
            }).get();
            
            if (action != 'save') {
                $.ajax({
                    url: $this.options.actionUrl,
                    type: "POST",
                    data: {act: action, data : JSON.stringify(entries)}
                })
                .done(function(result) {
                    $('#commsy-select-actions-select-shown').removeClass('uk-active');
                    $('#commsy-select-actions-select-all').removeClass('uk-active');
                    $('#commsy-select-actions-unselect').removeClass('uk-active');
                    
                    target.find('input[type="checkbox"]').each(function(){
                        $(this).prop('checked', false);
                    });
                    target.find('article').each(function(){
                        $(this).removeClass('uk-comment-primary');
                    });
                    
                    var el = $('.feed-load-more');
                    var queryString = document.location.search;
                    var url = el.data('feed').url  + 0 + queryString;
            
                    var message = result.message;
                    var status = result.status;
                    var layout = result.layout;
                    var timeout = result.timeout;
            
                    $.ajax({
                      url: url
                    })
                    .done(function(result) {
                        if ($(result).filter('article').length) {
                            var target = el.data('feed').target;
                            $(target).empty();
                            $(target).html(result);
                            
                            $(target).find('article').each(function(){
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
                    });
                });
            } else {
                var $form=$(document.createElement('form')).css({display:'none'}).attr("method","POST").attr("action",$this.options.actionUrl);
                for (var i = 0; i < entries.length; i++) { 
                    var $input=$(document.createElement('input')).attr('name','data[]').val(entries[i]);
                    $form.append($input)
                }
                var $input=$(document.createElement('input')).attr('name','act').val('save');
                $form.append($input)
                $("body").append($form);
                $form.submit();
            }
        }
    });

})(UIkit);
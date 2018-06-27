;(function(UI){

    "use strict";

    var currentPortfolioSource = null;
    var currentPortfolioId = null;
    var initialLoading = true;

    // Load initial portfolio
    loadPortfolioList($('#portfolioSourceTabs').find('li.uk-active').data('portfolio-source-id'));

    UI.$html.on('change.uk.tab', function(event, sortable, dragged, action) {
        if (!initialLoading) {
            let $target = $(event.target);
            if ($target.attr('id') === 'portfolioTabs') {
                if ($target.find('li.uk-active').data('portfolio-id') != 'new') {
                    loadPortfolioContent($target.find('li.uk-active').data('portfolio-id'));
                } else {
                    window.location.replace($target.find('li.uk-active a').attr('href'));
                }
            } else {
                loadPortfolioList($target.find('li.uk-active').data('portfolio-source-id'));
            }
        }
    });

    function loadPortfolioContent (portfolioId) {
        //if (portfolioId) {
        //    if (currentPortfolioId != portfolioId) {
        //       currentPortfolioId = portfolioId
        //    }
        //}
        resetPortfolioView(false);
        loadPortfolio(portfolioId);
    }

    function loadPortfolioList (portfolioSourceId) {
        //if (portfolioSourceId) {
        //    if (currentPortfolioSource != portfolioSourceId) {
        //        currentPortfolioSource = portfolioSourceId
        //    }
        //}
        resetPortfolioView(true);
        loadPortfolioTabs(portfolioSourceId);
    }

    function resetPortfolioView (complete) {
        if (complete) {
            $('#portfolio-tabs').html('<i class="uk-icon-spinner uk-icon-spin"></i>');
            $('#portfolio-table').html('');
        } else {
            $('#portfolio-table').html('<i class="uk-icon-spinner uk-icon-spin"></i>');
        }
    }

    function loadPortfolio (portfolioId) {
        $.ajax({
            url: $('#portfolio-wrapper').data('portfolio-url')+'/'+portfolioId,
        }).done(function(result) {
            $('#portfolio-table').html(result);
            //UIkit.notify('loaded portfolio', 'success');
            initialLoading = false;
        }).fail(function(jqXHR, textStatus, errorThrown) {
            //UIkit.notify('fail to load portfolio', 'danger');
        });
    }

    function loadPortfolioTabs (portfolioSourceId) {
        $.ajax({
            url: $('#portfolio-wrapper').data('portfolio-tabs-url')+'/'+portfolioSourceId,
        }).done(function(result) {
            $('#portfolio-tabs').html(result);
            //UIkit.notify('loaded portfolio tabs', 'success');
            if (typeof portfolioId !== 'undefined'){
                if (portfolioId) {
                    var tabIndex = 0;
                    $('#portfolioTabs li').each(function() {
                        var $this = $(this);
                        if ($this.data('portfolio-id') == portfolioId) {
                            UIkit.switcher($('#portfolioTabs')).show(tabIndex);
                            loadPortfolioContent(portfolioId);
                        }
                        tabIndex++;
                    });
                }
            } else {
                loadPortfolioContent($('#portfolioTabs').find('li.uk-active').data('portfolio-id'));
            }
        }).fail(function(jqXHR, textStatus, errorThrown) {
            //UIkit.notify('fail to load portfolio tabs', 'danger');
        });
    }

    (function($) {
        var origAppend = $.fn.append;
        $.fn.append = function () {
            return origAppend.apply(this, arguments).trigger("append");
        };
    })(jQuery);

    $("#portfolio-tabs").bind("append", function() {
        $('#portfolio-tabs div.uk-dropdown').each(function () {
            $(this).addClass('uk-dropdown-scrollable');
        });
    });

})(UIkit);
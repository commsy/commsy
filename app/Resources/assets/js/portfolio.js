;(function(UI){

    "use strict";

    var currentPortfolioSource = null;
    var currentPortfolioId = null;

    UI.$html.on('change.uk.tab', function(event, sortable, dragged, action) {
        let $target = $(event.target);
        if ($target.attr('id') === 'portfolioTabs') {
            loadPortfolioContent($target.find('li.uk-active').data('portfolio-id'));
        } else {
            loadPortfolioList($target.find('li.uk-active').data('portfolio-source-id'));
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
        }).fail(function(jqXHR, textStatus, errorThrown) {
            //UIkit.notify('fail to load portfolio tabs', 'danger');
        });
    }

})(UIkit);
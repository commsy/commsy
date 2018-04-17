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
        resetPortfolioView();
        loadPortfolio(portfolioId);
    }

    function loadPortfolioList (portfolioSourceId) {
        //if (portfolioSourceId) {
        //    if (currentPortfolioSource != portfolioSourceId) {
        //        currentPortfolioSource = portfolioSourceId
        //    }
        //}
        resetPortfolioView();
    }

    function resetPortfolioView () {
        $('#portfolio-table').html('<i class="uk-icon-spinner uk-icon-spin"></i>');
    }

    function loadPortfolio (portfolioId) {
        $.ajax({
            url: $('#portfolio-wrapper').data('portfolio-url')+'/'+portfolioId,
        }).done(function(result) {
            $('#portfolio-table').html(result);
            UIkit.notify('loaded portfolio', 'success');
        }).fail(function(jqXHR, textStatus, errorThrown) {
            UIkit.notify('fail to load portfolio', 'danger');
        });
    }

})(UIkit);
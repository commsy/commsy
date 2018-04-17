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
        console.log(portfolioId);
        if (currentPortfolioId != portfolioId) {
            currentPortfolioId = portfolioId
            resetPortfolioView();
        }
    }

    function loadPortfolioList (portfolioSourceId) {
        console.log(portfolioSourceId);
        if (currentPortfolioSource != portfolioSourceId) {
            currentPortfolioSource = portfolioSourceId
            resetPortfolioView();
        }
    }

    function resetPortfolioView () {
        console.log('resetPortfolioView');
        $('#portfolios-wrapper').html('<i class="uk-icon-spinner uk-icon-spin"></i>');
    }

})(UIkit);
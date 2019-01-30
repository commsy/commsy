import * as $ from 'jquery';
import * as URI from "urijs";

'use strict';

export class Portfolio {
    public bootstrap() {
        // Listen on source tab changes
        // This is also trigger initially
        $('#portfolioSourceTabs').on('change.uk.tab', (event) => {
            this.onSourceTabsChanged($(event.currentTarget));
        });
    }

    private getCurrentSourceId(): string {
        return $('#portfolioSourceTabs').find('li.uk-active').data('portfolio-source-id');
    }

    private getActivePortfolioId(): number {
        return Number.parseInt($('#portfolioTabs').find('li.uk-active').data('portfolio-id'));
    }

    private resetPortfolioView(full: boolean) {
        if (full) {
            $('#portfolio-tabs').html('<i class="uk-icon-spinner uk-icon-spin"></i>');
            $('#portfolio-table').html('');
        } else {
            $('#portfolio-table').html('<i class="uk-icon-spinner uk-icon-spin"></i>');
        }
    }

    private onSourceTabsChanged($actor: JQuery) {
        // console.log('source tabs changed');

        this.resetPortfolioView(true);
        this.loadPortfolioTabs(this.getCurrentSourceId());
    }

    private onPortfolioTabsChanged($actor: JQuery) {
        // console.log('portfolio tabs changed');

        this.resetPortfolioView(false);
        this.loadPortfolio(this.getActivePortfolioId());
    }

    private loadPortfolioTabs(sourceId: string) {
        let self = this;

        $.ajax({
            url: $('#portfolio-wrapper').data('portfolio-tabs-url') + '/' + sourceId,
        }).done(function(result) {
            // console.log(result);

            $('#portfolio-tabs').html(result);
            // UIkit.notify('loaded portfolio tabs', 'success');

            // Register on change listener for portfolio tabs
            $('#portfolioTabs').on('change.uk.tab', (event) => {
                self.onPortfolioTabsChanged($(event.currentTarget));
            });

            // after loading the tabs, load the initial portfolio data
            let portfolioId: number = self.getActivePortfolioId();

            /**
             * Check if we got a query parameter "portfolioId". If this is the case search all current tabs for the
             * corresponding id, select it and load the portfolio.
             */
            let uri = new URI();
            let queryParams: Object = uri.search(true);
            if (queryParams.hasOwnProperty('portfolioId')) {
                let queryPortfolioId = queryParams['portfolioId'];
                let tabIndex: number = 0;
                $('#portfolioTabs li').each(function() {
                    let $li = $(this);
                    if ($li.data('portfolio-id') == queryPortfolioId) {
                        (UIkit as any).switcher($('#portfolioTabs')).show(tabIndex);
                        portfolioId = queryPortfolioId;
                    }
                    tabIndex++;
                });
            }

            if (!isNaN(portfolioId)) {
                self.loadPortfolio(portfolioId);
            }
        }).fail(function(jqXHR, textStatus, errorThrown) {
            //UIkit.notify('fail to load portfolio tabs', 'danger');
        });
    }

    private loadPortfolio(portfolioId: number) {
        // console.log('loading portfolio content with id ' + portfolioId);

        $.ajax({
            url: $('#portfolio-wrapper').data('portfolio-url')+'/'+portfolioId,
        }).done(function(result) {
            $('#portfolio-table').html(result);

            //UIkit.notify('loaded portfolio', 'success');
        }).fail(function(jqXHR, textStatus, errorThrown) {
            //UIkit.notify('fail to load portfolio', 'danger');
        });
    }
}


//     (function($) {
//         var origAppend = $.fn.append;
//         $.fn.append = function () {
//             return origAppend.apply(this, arguments).trigger("append");
//         };
//     })(jQuery);
//
//     $("#portfolio-tabs").bind("append", function() {
//         $('#portfolio-tabs div.uk-dropdown').each(function () {
//             $(this).addClass('uk-dropdown-scrollable');
//         });
//     });
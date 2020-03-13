import * as $ from 'jquery';
import * as URI from "urijs";

'use strict';

declare var UIkit: any;

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
        this.resetPortfolioView(true);
        this.loadPortfolioTabs(this.getCurrentSourceId());
    }

    private onPortfolioTabsChanged($actor: JQuery) {
        this.resetPortfolioView(false);
        this.loadPortfolio(this.getActivePortfolioId());
    }

    private loadPortfolioTabs(sourceId: string) {
        let self = this;

        $.ajax({
            url: $('#portfolio-wrapper').data('portfolio-tabs-url') + '/' + sourceId,
        }).done(function(result) {

            $('#portfolio-tabs').html(result);

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
        $.ajax({
            url: $('#portfolio-wrapper').data('portfolio-url')+'/'+portfolioId,
        }).done(function(result) {
            $('#portfolio-table').html(result);

            let stopActivationLink: JQuery = $('a#portfolio-stop-activation');
            stopActivationLink.on('click', function() {
                $.ajax({
                    url: stopActivationLink.data('portfolio-stop-activation-url'),
                }).done(function (result) {

                });
            });

            //UIkit.notify('loaded portfolio', 'success');
        }).fail(function(jqXHR, textStatus, errorThrown) {
            //UIkit.notify('fail to load portfolio', 'danger');
        });
    }
}
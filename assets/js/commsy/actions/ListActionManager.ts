import UIkit from 'uikit';
import * as $ from 'jquery';
import * as Actions from './Actions';
import {ActionExecuter, ListActionData} from "./Actions";
import {BaseAction} from "./AbstractAction";

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

'use strict';

export class ListActionManager {
    private currentActionData: ListActionData;
    private actionActor: JQuery;

    private selectMode: boolean = false;

    private selectAll: boolean = false;
    private positiveSelection: number[];
    private negativeSelection: number[];
    private numSelected: number = 0;

    public bootstrap() {
        this.registerClickEvents();

        window.addEventListener('feedDidLoad', () => {
            this.onFeedLoad();
        });
    }

    private onFeedLoad() {
        if (this.selectMode) {
            this.updateSelectables();
            this.registerArticleEvents();

            if (this.selectAll) {
                this.onSelectAll(true);
            }
        }
    }

    private registerClickEvents() {
        // register all actions listed in the dropdown menu, identified by .commsy-select-action
        $('.commsy-select-action').on('click', (event) => {
            event.stopPropagation();
            event.preventDefault();

            // store data from data-comsy-list-action
            this.currentActionData = $(event.currentTarget).data('cs-action');

            // store actor to get needed data later on
            this.actionActor = $(event.currentTarget);

            if (this.currentActionData.mode == 'selection') {
                this.onStartEdit();
            }
        });

        // confirm action button
        $('#commsy-select-actions-ok').on('click', (event) => {
            event.stopPropagation();
            event.preventDefault();

            this.onClickPerform();
        });

        // cancel action button
        $('#commsy-select-actions-cancel').on('click', (event) => {
            event.stopPropagation();
            event.preventDefault();

            this.onStopEdit();
        });

        // select all
        $('#commsy-select-actions-select-all').on('click', (event) => {
            event.stopPropagation();
            event.preventDefault();

            this.onSelectAll();
        });

        // deselect all
        $('#commsy-select-actions-unselect').on('click', (event) => {
            event.stopPropagation();
            event.preventDefault();

            this.onDeselectAll($(event.currentTarget));
        });
    }

    private onSelectAll(isLoadMore: boolean = false) {
        let self = this;

        // highlight button as active
        this.actionActor.addClass('uk-active');

        let $feed: JQuery = $('.feed ul, .feed div.uk-grid');

        // check all visible checkboxes
        $feed.find('input').filter(":visible").each(function() {
            let element = <HTMLInputElement>this;
            if (element.type == 'checkbox') {
                let selectElement: boolean = true;
                if (isLoadMore) {
                    // when loading more entries in the feed, make sure we do not recheck items previously deselected
                    let checkboxValue: Number = Number($(element).val());

                    if (self.negativeSelection.indexOf(checkboxValue.valueOf()) !== -1) {
                        selectElement = false;
                    }
                }

                if (selectElement) {
                    $(element).prop('checked', true);

                    // highlight the enclosing article
                    $(element).closest('article').addClass('uk-comment-primary');
                }
            }
        });

        // update selection
        this.positiveSelection = [];
        if (!isLoadMore) {
            this.negativeSelection = [];
        }

        let $listCountAll: JQuery = $('#commsy-list-count-all');
        this.numSelected = parseInt($listCountAll.html());
        this.updateCurrentSelected();

        // persist select all
        this.selectAll = true;
    }

    private onDeselectAll($actor: JQuery) {
        // undo select all modifications
        $actor.removeClass('uk-active');

        let $feed: JQuery = $('.feed ul, .feed div.uk-grid');

        $feed.find('input').each(function() {
            let element = <HTMLInputElement>this;
            if (element.type == 'checkbox') {
                $(element).prop('checked', false);
            }
        });

        $feed.find('article').each(function() {
            $(this).removeClass('uk-comment-primary');
        });

        // update selection
        this.positiveSelection = [];
        this.negativeSelection = [];

        this.numSelected = 0;
        this.updateCurrentSelected();

        this.selectAll = false;
    }

    private onStartEdit() {
        this.onStopEdit();
        this.selectMode = true;

        let $feed: JQuery = $('.feed ul:first-child, .feed div.uk-grid');
        if (!$feed.length) {
            return;
        }

        this.updateSelectables();

        // show the action dialog
        let $actionDialog: JQuery = $('#commsy-select-actions');
        $actionDialog
            .removeClass('uk-hidden')
            .parent('.uk-sticky-placeholder')
                .css('height', '65px');

        // reset current selected count
        this.positiveSelection = [];
        this.negativeSelection = [];

        this.numSelected = 0;
        this.updateCurrentSelected();

        // hide normal list count / show edit count
        $('#commsy-list-count-display').addClass('uk-hidden');
        $('#commsy-list-count-edit').removeClass('uk-hidden');

        // reset dialog state
        $('#commsy-select-actions-select-all').removeClass('uk-active');
        $('#commsy-select-actions-unselect').removeClass('uk-active');
        $('#commsy-select-actions-ok').removeClass('uk-active');
        $('#commsy-select-actions-cancel').removeClass('uk-active');

        $(".feed .uk-grid.uk-text-truncate div").css("padding-left", "0px");
        $(".feed .uk-grid .uk-icon-sign-in").toggleClass("uk-hidden");

        // reset select all
        this.selectAll = false;

        this.registerArticleEvents();
    }

    private onStopEdit() {
        this.selectMode = false;

        let $feed: JQuery = $('.feed ul, .feed div.uk-grid');
        if (!$feed.length) {
            return;
        }

        // hide the action dialog
        let $actionDialog: JQuery = $('#commsy-select-actions');
        $actionDialog
            .addClass('uk-hidden')
            .parent('.uk-sticky-placeholder')
                .css('height', '0px');

        // uncheck all checkboxes
        $feed.find('input').each(function() {
            let element = <HTMLInputElement>this;
            if (element.type == 'checkbox') {
                $(element).prop('checked', false);
            }
        });

        // reset articles
        $feed.find('article').
            each(function() {
                $(this).removeClass('uk-comment-primary');
            })
            .removeClass('selectable');

        // show normal list count / hide edit count
        $('#commsy-list-count-display').removeClass('uk-hidden');
        $('#commsy-list-count-edit').addClass('uk-hidden');

        $(".feed .uk-grid.uk-text-truncate div").css("padding-left", "35px");
        $(".feed .uk-grid .uk-icon-sign-in").toggleClass("uk-hidden");

        this.selectAll = false;
    }

    private onClickPerform() {
        // collect values of selected checkboxes
        let $feed: JQuery = $('.feed ul, .feed div.uk-grid');

        // if no entries are selected, present notification
        if (this.numSelected == 0) {
            UIkit.notify({
                message : this.currentActionData.noSelectionMessage,
                status  : 'warning',
                timeout : 5550,
                pos     : 'top-center'
            });

            return;
        }

        let action: BaseAction = Actions.createAction(this.currentActionData);
        let actionExecuter: ActionExecuter = new ActionExecuter();
        actionExecuter.invokeListAction(this.actionActor, action, this.positiveSelection, this.negativeSelection, this.selectAll, 0)
            .then(() => {
                $('#commsy-select-actions-select-all').removeClass('uk-active');
                $('#commsy-select-actions-unselect').removeClass('uk-active');

                $feed.find('input[type="checkbox"]').each(function () {
                    $(this).prop('checked', false);
                });
                $feed.find('article').each(function () {
                    $(this).removeClass('uk-comment-primary');
                });

                this.onStopEdit();
            })
            .catch( (error: Error) => {
                // Catching here does not have to be a fatal error, e.g. rejecting a confirm dialog.
                // So we check for the error parameter
                if (error) {
                    UIkit.notify(error.message, 'danger');
                }
            });
    }

    private updateSelectables() {
        let $feed: JQuery = $('.feed ul, .feed div.uk-grid');
        if (!$feed.length) {
            return;
        }

        let $articles: JQuery = $feed.find('article')

        let currentAction: string = this.currentActionData.action;

        $articles.each(function() {
            // each article has a data attribute listing the allowed actions
            if ($.inArray(currentAction, $(this).data('allowed-actions')) > -1) {
                $(this).toggleClass('selectable', true);
            }
        });
    }

    private updateCurrentSelected() {
        $('#commsy-list-count-selected').html(this.numSelected.toString());
    }

    private registerArticleEvents() {
        let $feed: JQuery = $('.feed ul, .feed div.uk-grid');

        // handle click on article
        $feed.find('article').off().on('click', (event) => {
            let $article: JQuery = $(event.currentTarget);

            // select mode?
            if ($article.hasClass('selectable')) {
                let checkbox: JQuery = $article.find('input[type="checkbox"]').first();

                // only select if element has a checkbox
                if (checkbox.length) {
                    // highlight the article
                    $article.toggleClass('uk-comment-primary');

                    // toggle checkbox
                    checkbox.prop('checked', $article.hasClass('uk-comment-primary'));

                    if (checkbox.prop('checked')) {
                        // article has been added
                        if (this.selectAll) {
                            let index: number = this.negativeSelection.findIndex((element: number) => {
                                return element === Number(checkbox.val());
                            });

                            this.negativeSelection.splice(index, 1);
                        } else {
                            this.positiveSelection.push(Number(checkbox.val()));
                        }

                        this.numSelected++;
                    } else {
                        // article has been removed
                        if (this.selectAll) {
                            this.negativeSelection.push(Number(checkbox.val()));
                        } else {
                            let index: number = this.positiveSelection.findIndex((element: number) => {
                                return element === Number(checkbox.val());
                            });

                            this.positiveSelection.splice(index, 1);
                        }

                        this.numSelected--;
                    }

                    // ensure uniqueness
                    this.positiveSelection = Array.from(new Set(this.positiveSelection));
                    this.negativeSelection = Array.from(new Set(this.negativeSelection));

                    this.updateCurrentSelected();

                    // disable normal click behaviour
                    event.preventDefault();
                }
            }
        });

        // handle click on checkboxes
        $feed.find('input').off().on('click', function(event) {
            event.stopPropagation();
            $(this).parents('article').trigger('click');
        });
    }
}
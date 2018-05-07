import * as $ from 'jquery';
import {CopyAction} from "./CopyAction";
import {BaseAction} from "./AbstractAction";
import {DeleteAction} from "./DeleteAction";
import {WorkflowAction} from "./WorkflowAction";
import {JoinAction} from "./JoinAction";
import {LeaveAction} from "./LeaveAction";
import {MarkReadAction} from "./MarkReadAction";
import * as URI from 'urijs';
import {SaveAction} from "./SaveAction";

'use strict';

export interface ActionData {
    url: string;
    action: string;
    errorMessage: string;
    mode: string;
}

export interface DetailActionData extends ActionData {
    itemId: number;
}

export interface ListActionData extends ActionData {
    noSelectionMessage: string;
}

export interface ActionRequest {
    /**
     * Positive list of id's to operate on
     */
    positiveItemIds: number[];

    /**
     * Negative list of id's to operate on
     */
    negativeItemIds: number[];

    /**
     * The action to perform
     */
    action: string;

    /**
     * Additional payload
     */
    payload?: object;

    /**
     * Does this request operate on all possibly selected entries?
     */
    selectAll: boolean;

    // TODO: what is this for?
    selectAllStart: number;
}

export interface ActionResponse {
    html?: string;
    payload?: object;
    redirect?: {
        route: string;
    };
    error?: string;
}

// TODO:
// if (backendResponse.redirect) {
//     window.location.replace(backendResponse.redirect.route);
//     resolve();
// }
//
// resolve(backendResponse);

export class ActionExecuter {
    public invokeAction($actor: JQuery, action: BaseAction, itemId: number): Promise<ActionResponse> {
        let actionPayload: ActionRequest = {
            positiveItemIds: [itemId],
            negativeItemIds: [],
            action: action.action,
            selectAll: false,
            selectAllStart: 0
        };

        return this.invoke($actor, action, actionPayload);
    }

    public invokeListAction($actor: JQuery, action: BaseAction, positiveItemIds: number[], negativeItemIds: number[], selectAll: boolean, selectAllStart: number): Promise<ActionResponse> {
        let actionPayload: ActionRequest = {
            positiveItemIds: positiveItemIds,
            negativeItemIds: negativeItemIds,
            action: action.action,
            selectAll: selectAll,
            selectAllStart: selectAllStart
        };

        return this.invoke($actor, action, actionPayload);
    }

    private invoke($actor: JQuery, action: BaseAction, actionPayload: ActionRequest): Promise<any> {
        let promiseChain = action.preExecute($actor)
            .then(() => {
                // set current query parameters also on the request URI
                let currentURI = new URI(location.href);
                let requestURI = new URI(action.url);
                requestURI.search(function() {
                    return currentURI.search(true);
                });

                return action.execute(actionPayload, requestURI.toString());
            })
            .then((backendResponse: ActionResponse) => {
                return action.onSuccess(backendResponse);
            })
            .catch((backendResponse: ActionResponse) => {
                action.onError(backendResponse);
            });

        return promiseChain;
    }
}





// PRE


//
//     case 'send-list':
//         // TODO:
//         // // send ajax request
//         // $.ajax({
//         //     url: $('#commsy-select-actions-send-list').data('cs-action-send-list').url,
//         //     type: 'POST',
//         //     data: JSON.stringify({
//         //     })
//         // }).done(function(data, textStatus, jqXHR) {
//         //     if (!jqXHR.responseJSON) {
//         //         // if we got back html, embed the form
//         //         let feedDom = $('.feed');
//         //
//         //         if (feedDom.length) {
//         //             feedDom.prepend(data);
//         //         }
//         //
//         //         // setupForm();
//         //     }
//         //
//         // }).fail(function(jqXHR, textStatus, errorThrown) {
//         //     UIkit.notify(errorMessage, 'danger');
//         // });
//
//         break;
//
//     case 'user-block':
//     case 'user-confirm':
//     case 'user-status-reading-user':
//     case 'user-status-user':
//     case 'user-status-moderator':
//     case 'user-contact':
//     case 'user-contact-remove':
//         // TODO:
//         // // forward user to user status change form, providing ids as query param
//         // let uri = new URI(actionUrl);
//         //
//         // // add ids
//         // uri.setSearch('userIds[]', <any>entries);
//         //
//         // window.location.replace(uri.toString());
//
//         break;
// }







// POST

//
// if (action == 'copy' || action == 'remove') {
//     let $indicator = $('#cs-nav-copy-indicator');
//     $indicator.html(result.data.count);
// }
//
// if (action == 'remove') {
//     let $countDisplay = $('#commsy-list-count-display');
//     $countDisplay.html('(' + result.data.countSelected + ' - ' + result.data.count + ')')
// }
//
// if (action == 'user-delete') {
//     let $countDisplay = $('#commsy-list-count-display');
//     $countDisplay.html($countDisplay.html().replace(/\d+/g, function (match) {
//         return (parseInt(match) - entries.length).toString();
//     }));
// }
//




export function createAction(actionData: ActionData): BaseAction {
    switch (actionData.action) {
        case 'delete':
        // case 'user-delete':
            return new DeleteAction(actionData);
        case 'copy':
            return new CopyAction(actionData);
        case 'workflow':
            return new WorkflowAction(actionData);
        case 'join':
            return new JoinAction(actionData);
        case 'leave':
            return new LeaveAction(actionData);
        case 'markread':
            return new MarkReadAction(actionData);
        case 'save':
            return new SaveAction(actionData);
    }

    return null;
}
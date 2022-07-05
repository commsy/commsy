import * as $ from 'jquery';
import {MarkAction} from "./MarkAction";
import {BaseAction} from "./AbstractAction";
import {DeleteAction} from "./DeleteAction";
import {WorkflowAction} from "./WorkflowAction";
import {JoinAction} from "./JoinAction";
import {LeaveAction} from "./LeaveAction";
import {MarkReadAction} from "./MarkReadAction";
import * as URI from 'urijs';
import {SaveAction} from "./SaveAction";
import {TodoStatusAction} from "./TodoStatusAction";
import {SendMailAction} from "./SendMailAction";
import {UserStatusAction} from "./UserStatusAction";
import {InsertAction} from "./InsertAction";
import {RemoveAction} from "./RemoveAction";
import {CategorizeAction} from "./CategorizeAction";
import {HashtaggingAction} from "./HashtaggingAction";
import {InsertUserroomAction} from "./InsertUserroomAction";

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

export class ActionExecuter {
    public buildActionRequest(
        action: BaseAction,
        positiveItemIds: number[],
        negativeItemIds: number[],
        selectAll: boolean,
        selectAllStart: number
    ): ActionRequest {
        return {
            positiveItemIds: positiveItemIds,
            negativeItemIds: negativeItemIds,
            action: action.actionData.action,
            selectAll: selectAll,
            selectAllStart: selectAllStart
        };
    }

    public loadCustomFormData(action: BaseAction): Promise<any> {
        return new Promise<ActionResponse>((resolve) => {
            let requestURI = new URI(action.actionData.url);

            return action.loadCustomFormData(requestURI.toString());
        })
        .then((backendResponse: ActionResponse) => {
            return action.onPostLoadCustomFormData(backendResponse);
        })
        .catch((error: Error) => {
            if (error) {
                action.onError(error);
            }
        });
    }

    public invoke($actor: JQuery, action: BaseAction, actionPayload: ActionRequest): Promise<any> {
        return action.preExecute($actor)
            .then(() => {
                // set current query parameters also on the request URI
                let currentURI = new URI(location.href);
                let requestURI = new URI(action.actionData.url);
                requestURI.search(function() {
                    return currentURI.search(true);
                });

                return action.execute(actionPayload, requestURI.toString());
            })
            .then((backendResponse: ActionResponse) => {
                return action.onSuccess(backendResponse);
            })
            .catch((error: Error) => {
                if (error) {
                    action.onError(error);
                }
            });
    }
}

export function createAction(actionData: ActionData): BaseAction {
    switch (actionData.action) {
        case 'delete':
            return new DeleteAction(actionData);
        case 'mark':
            return new MarkAction(actionData);
        case 'insert':
            return new InsertAction(actionData);
        case 'insertuserroom':
            return new InsertUserroomAction(actionData);
        case 'remove':
            return new RemoveAction(actionData);
        case 'categorize':
            return new CategorizeAction(actionData);
        case 'hashtagging':
            return new HashtaggingAction(actionData);
        case 'workflow':
            return new WorkflowAction(actionData);
        case 'join':
            return new JoinAction(actionData);
        case 'leave':
            return new LeaveAction(actionData);
        case 'markread':
            return new MarkReadAction(actionData);
        case 'markpending':
        case 'markinprogress':
        case 'markdone':
            return new TodoStatusAction(actionData);
        case 'save':
            return new SaveAction(actionData);
        case 'sendmail':
            return new SendMailAction(actionData);
        case 'user-delete':
        case 'user-block':
        case 'user-confirm':
        case 'user-status-reading-user':
        case 'user-status-user':
        case 'user-status-moderator':
        case 'user-contact':
        case 'user-contact-remove':
            return new UserStatusAction(actionData);
    }

    if (actionData.action.substr(0, 4) === 'mark') {
        return new TodoStatusAction(actionData);
    }

    return null;
}
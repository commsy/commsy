import * as $ from 'jquery';

'use strict';

import {ActionData, ActionResponse} from "./Actions";
import {XHRAction} from "./AbstractAction";

declare var UIkit: any;

export class WorkflowAction extends XHRAction {
    private successMessage: string;
    private read: boolean;

    constructor(actionData: ActionData) {
        super(actionData);
    }

    public preExecute(actionActor: JQuery): Promise<void> {
        return new Promise<void>((resolve) => {
            this.successMessage = actionActor.data('cs-action').successMessage;
            this.read = actionActor.data('cs-action').read;

            this.setExtraData('read', this.read);

            resolve();
        });
    }

    public onSuccess(backendResponse: ActionResponse): Promise<boolean> {
        return new Promise<boolean>((resolve) => {
            UIkit.notify(this.successMessage, "success");

            $('.cs-workflow-action').toggleClass('uk-hidden');

            window.location.href = backendResponse.redirect.route;

            resolve(true);
        });
    }
}
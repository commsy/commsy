'use strict';

import {ActionData, ActionResponse} from "./Actions";
import {XHRAction} from "./AbstractAction";

declare var UIkit: any;

export class MarkReadAction extends XHRAction {
    constructor(actionData: ActionData) {
        super(actionData);
    }

    public onSuccess(backendResponse: ActionResponse): Promise<boolean> {
        return new Promise<boolean>((resolve) => {
            let payload: any = backendResponse.payload;

            UIkit.notify(payload.message, 'success');

            window.location.href = window.location.href;

            resolve(true);
        });
    }
}
'use strict';

import {ActionData, ActionResponse} from "./Actions";
import {XHRAction} from "./AbstractAction";

export class MarkReadAction extends XHRAction {
    constructor(actionData: ActionData) {
        super(actionData);
    }

    public onSuccess(backendResponse: ActionResponse): Promise<ActionResponse> {
        return new Promise((resolve) => {
            let payload: any = backendResponse.payload;

            UIkit.notify(payload.message, 'success');

            resolve(backendResponse);
        });
    }
}
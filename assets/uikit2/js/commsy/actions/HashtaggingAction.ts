import {ActionData, ActionResponse} from "./Actions";
import {XHRAction} from "./AbstractAction";

'use strict';

declare var UIkit: any;

export class HashtaggingAction extends XHRAction {
    constructor(actionData: ActionData) {
        super(actionData, true);
    }

    public onSuccess(backendResponse: ActionResponse): Promise<ActionResponse> {
        return new Promise<ActionResponse>((resolve) => {
            let payload: any = backendResponse.payload;

            UIkit.notify(payload.message, 'success');

            resolve(backendResponse);
        });
    }
}
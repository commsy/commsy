import {ActionData, ActionResponse} from "./Actions";
import {XHRAction} from "./AbstractAction";

'use strict';

declare var UIkit: any;

export class RemoveAction extends XHRAction {

    private returnUrl: string;

    constructor(actionData: ActionData) {
        super(actionData);

        let deleteData: any = actionData;

        this.returnUrl = deleteData.returnUrl;
    }

    public onSuccess(backendResponse: ActionResponse): Promise<ActionResponse> {
        return new Promise((resolve) => {
            let payload: any = backendResponse.payload;

            UIkit.notify(payload.message, 'success');

            window.location.href = this.returnUrl;

            resolve(backendResponse);
        });
    }
}
import {ActionData, ActionResponse} from "./Actions";
import {XHRAction} from "./AbstractAction";

'use strict';

declare var UIkit: any;

export class HashtaggingAction extends XHRAction {
    private chosenHashtagIds: number[];

    constructor(actionData: ActionData) {
        super(actionData, true);
    }

    public onPostLoadCustomFormData(backendResponse: ActionResponse): Promise<ActionResponse> {
        return new Promise<ActionResponse>((resolve) => {
            // TODO: register event listener onchange and store all hashtags selected by the user in $chosenHashtagIds

            resolve(backendResponse);
        });
    }

    // TODO: why is this method overwrite necessary here?
    public preExecute(actionActor: JQuery): Promise<void> {
        return new Promise<void>((resolve) => {
            resolve();
        });
    }

    public onSuccess(backendResponse: ActionResponse): Promise<ActionResponse> {
        return new Promise<ActionResponse>((resolve) => {
            let payload: any = backendResponse.payload;

            UIkit.notify(payload.message, 'success');

            resolve(backendResponse);
        });
    }
}
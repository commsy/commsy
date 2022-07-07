import {ActionData, ActionResponse} from "./Actions";
import {BaseAction, XHRAction} from "./AbstractAction";

'use strict';

declare var UIkit: any;

export class HashtagAction extends XHRAction {
    private chosenHashtagIds: number[];

    constructor(actionData: ActionData) {
        super(actionData, true);
    }

    public onPostLoadCustomFormData(backendResponse: ActionResponse): Promise<ActionResponse> {
        return new Promise<ActionResponse>((resolve) => {
            let responseHtml: string = backendResponse.html;

            let $customChoicesPlaceholder = $('#commsy-select-actions-custom-choices');
            $customChoicesPlaceholder.html(responseHtml);

            // TODO: register event listener onchange and store all hashtags selected by the user in $chosenHashtagIds

            resolve(backendResponse);
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
import {ActionData, ActionResponse} from "./Actions";
import {BaseAction, XHRAction} from "./AbstractAction";

'use strict';

declare var UIkit: any;

export class HashtagAction extends XHRAction {
    constructor(actionData: ActionData) {
        super(actionData, true);
    }

    public onPostLoadCustomFormData(backendResponse: ActionResponse): Promise<ActionResponse> {
        return new Promise<ActionResponse>((resolve) => {
            let responseHtml: string = backendResponse.html;

            let $customChoicesPlaceholder = $('#commsy-select-actions-custom-choices');
            $customChoicesPlaceholder.html(responseHtml);

            // TODO: better initialize dynamically loaded HTML components?
            const children: any = $customChoicesPlaceholder.children('.js-select2-choice');
            children.eq(0).select2();

            // TODO: this doesn't work yet: register event listener onchange and store all hashtags selected by the user in extraData
            children.eq(0).on('select2:select', function (e) {
                console.log('select2-choice was changed'); // DEBUG
            });

            this.setExtraData('choices', [0, 1, 2]); // DEBUG

            resolve(backendResponse);
        });
    }

    public onSuccess(backendResponse: ActionResponse): Promise<boolean> {
        return new Promise<boolean>((resolve) => {
            let payload: any = backendResponse.payload;

            if (backendResponse.html) {
                resolve(false);
            }

            UIkit.notify(payload.message, 'success');

            resolve(true);
        });
    }
}
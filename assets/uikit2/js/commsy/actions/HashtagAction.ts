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

            // insert form HTML with Select2Choice control containing available hashtags
            let $customChoicesPlaceholder = $('#commsy-select-actions-custom-choices');
            $customChoicesPlaceholder.html(responseHtml);

            // TODO: better initialize dynamically loaded HTML components?
            const children: any = $customChoicesPlaceholder.find('.js-select2-choice');
            children.select2();

            // listen to changes to the Select2Choice control and store IDs of all chosen hashtags as extra data
            let choices = [];
            let self = this;
            children.on('change.select2', function (e) {
                const data = ($(this) as any).select2('data');

                choices = data.map(function(sel) {
                    return sel.id;
                })

                self.setExtraData('choices', choices);
            });

            resolve(backendResponse);
        });
    }

    public onSuccess(backendResponse: ActionResponse): Promise<boolean> {
        return new Promise<boolean>((resolve) => {
            // display again any returned HTML (potentially containing form validation errors)
            if (backendResponse.html) {
                this.onPostLoadCustomFormData(backendResponse)
                    .then(() => {
                        resolve(false);
                    });
            }

            let payload: any = backendResponse.payload;
            UIkit.notify(payload.message, 'success');

            resolve(true);
        });
    }
}
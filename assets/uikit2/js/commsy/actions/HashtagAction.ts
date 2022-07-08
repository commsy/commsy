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

            console.log($customChoicesPlaceholder);

            // TODO: better initialize dynamically loaded HTML components?
            const children: any = $customChoicesPlaceholder.find('.js-select2-choice');
            children.select2();

            // TODO: this doesn't work yet: register event listener onchange and store all hashtags selected by the user in extraData
            let choices = [];
            let self = this;
            children.on('change.select2', function (e) {
                const data = ($(this) as any).select2('data');

                choices = data.map(function(sel) {
                    return sel.id;
                })

                console.log(choices);
                self.setExtraData('choices', choices);
            });

            resolve(backendResponse);
        });
    }

    public onSuccess(backendResponse: ActionResponse): Promise<boolean> {
        return new Promise<boolean>((resolve) => {
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
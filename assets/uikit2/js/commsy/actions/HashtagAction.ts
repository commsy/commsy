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

            // insert form HTML with select control containing available categories
            let $customChoicesPlaceholder = $('#commsy-select-actions-custom-choices');
            $customChoicesPlaceholder.html(responseHtml);

            // listen to select changes and store IDs of all chosen categories as extra data
            const select: any = $customChoicesPlaceholder.find('select').get(0);
            const self = this;
            select.addEventListener("change", (event) => {
              const options = Array.from(event.target.selectedOptions);
              const choices = options.map(function(option: HTMLOptionElement) {
                return option.value;
              });

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

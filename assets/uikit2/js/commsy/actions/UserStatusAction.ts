import {ActionData, ActionRequest, ActionResponse} from "./Actions";
import {XHRAction} from "./AbstractAction";
import * as $ from "jquery";
import * as URI from 'urijs';

export class UserStatusAction extends XHRAction {

    private status: string;

    constructor(actionData: ActionData) {
        super(actionData);

        let todoData: any = actionData;
        this.status = todoData.status;
    }

    public execute(actionPayload: ActionRequest, requestURI: string): Promise<ActionResponse> {
        return new Promise<ActionResponse>((resolve) => {
            let currentURI = new URI(location.href);
            let requestURI = new URI(this.url);
            requestURI.search(function() {
                return currentURI.search(true);
            });

            let $form = $(document.createElement('form'))
                .css({
                    display: 'none'
                })
                .attr('method', 'POST')
                .attr('action', requestURI.toString());

            for (let i = 0; i < actionPayload.positiveItemIds.length; i++) {
                $form.append($('<input>').attr('name','positiveItemIds[]').val(actionPayload.positiveItemIds[i]));
            }
            for (let i = 0; i < actionPayload.negativeItemIds.length; i++) {
                $form.append($('<input>').attr('name','negativeItemIds[]').val(actionPayload.negativeItemIds[i]));
            }

            $form.append($('<input>').attr('name', 'action').val(actionPayload.action));
            $form.append($('<input>').attr('name', 'selectAll').val(String(actionPayload.selectAll)));

            $form.append($('<input>').attr('name', 'status').val(this.status));

            $('body').append($form);
            $form.trigger('submit');

            resolve(null);
        });
    }
}
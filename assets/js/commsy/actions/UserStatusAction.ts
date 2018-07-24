import {ActionData, ActionRequest, ActionResponse} from "./Actions";
import {XHRAction} from "./AbstractAction";
import * as $ from "jquery";

export class UserStatusAction extends XHRAction {

    private status: string;

    constructor(actionData: ActionData) {
        super(actionData);

        let todoData: any = actionData;
        this.status = todoData.status;
    }

    public execute(actionPayload: ActionRequest, requestURI: string): Promise<ActionResponse> {
        return new Promise((resolve) => {
            let $form = $(document.createElement('form'))
                .css({
                    display: 'none'
                })
                .attr('method', 'POST')
                .attr('action', this.url);

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

            resolve();
        });
    }
}
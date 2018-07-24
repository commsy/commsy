import * as $ from 'jquery';

'use strict';

import {ActionRequest, ActionResponse} from "./Actions";
import {BaseAction} from "./AbstractAction";

export class SendMailAction extends BaseAction {

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

            $('body').append($form);
            $form.trigger('submit');

            resolve();
        });
    }
}
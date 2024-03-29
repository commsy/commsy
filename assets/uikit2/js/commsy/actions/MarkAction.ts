import * as $ from 'jquery';

'use strict';

import {ActionData, ActionResponse} from "./Actions";
import {XHRAction} from "./AbstractAction";

declare var UIkit: any;

export class MarkAction extends XHRAction {
    constructor(actionData: ActionData) {
        super(actionData);
    }

    public onSuccess(backendResponse: ActionResponse): Promise<boolean> {
        return new Promise<boolean>((resolve) => {
            let payload: any = backendResponse.payload;

            UIkit.notify(payload.message, 'success');

            let $indicator = $('#cs-nav-mark-indicator');
            $indicator.html(payload.count);

            resolve(true);
        });
    }
}
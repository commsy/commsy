import * as $ from 'jquery';

'use strict';

import {ActionData, ActionResponse} from "./Actions";
import {XHRAction} from "./AbstractAction";

declare var UIkit: any;

export class DeleteAction extends XHRAction {
    private itemId: number;
    private itemType: string;
    private title: string;
    private confirmQuestion: string;
    private confirmCancel: string;
    private confirmOk: string;
    private returnUrl: string;
    private recurring: boolean;

    constructor(actionData: ActionData) {
        super(actionData);

        let deleteData: any = actionData;

        this.itemId = deleteData.itemId;
        this.itemType = deleteData.itemType;
        this.title = deleteData.title;
        this.confirmQuestion = deleteData.confirmQuestion;
        this.confirmCancel = deleteData.confirmCancel;
        this.confirmOk = deleteData.confirmOk;
        this.returnUrl = deleteData.returnUrl;
        this.recurring = deleteData.recurring;
    }

    public preExecute(actionActor: JQuery): Promise<void> {
        return new Promise<void>((resolve, reject) => {
            // deletion requires separate confirmation
            UIkit.modal.confirm(this.confirmQuestion, () => {
                this.setExtraData('recurring', this.recurring);
                resolve();
            }, () => {
                reject();
            }, <any>{
                labels: {
                    Cancel: this.confirmCancel,
                    Ok: this.confirmOk
                }
            });
        });
    }

    public onSuccess(backendResponse: ActionResponse): Promise<boolean> {
        return new Promise<boolean>((resolve) => {
            if (backendResponse.redirect) {
                window.location.href = backendResponse.redirect.route;
            } else {
                switch (this.itemType) {
                    case 'section':
                        $('.material-section').hide();
                        break;

                    default:
                        window.location.href = this.returnUrl;
                }
            }

            resolve(true);
        });
    }
}

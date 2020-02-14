'use strict';

import UIkit from 'uikit';
import {ActionData, ActionRequest, ActionResponse} from "./Actions";
import * as $ from "jquery";

export abstract class BaseAction {
    private actionData: ActionData;

    constructor(actionData: ActionData) {
        this.actionData = actionData;
    }

    get url(): string {
        return this.actionData.url;
    }

    get action(): string {
        return this.actionData.action;
    }

    get errorMessage(): string {
        return this.actionData.errorMessage;
    }

    public preExecute(actionActor: JQuery): Promise<void> {
        return new Promise((resolve) => {
            resolve();
        });
    }

    public execute(actionPayload: ActionRequest, requestURI: string): Promise<ActionResponse> {
        return new Promise((resolve) => {
            resolve();
        });
    }

    public onSuccess(backendResponse: ActionResponse): Promise<ActionResponse> {
        return new Promise((resolve) => {
            resolve(backendResponse);
        });
    }

    public onError(error: Error) {
        UIkit.notify(this.errorMessage, 'danger');
    }
}


export abstract class XHRAction extends BaseAction {
    private extraData: object;

    constructor(actionData: ActionData) {
        super(actionData);

        this.extraData = {};
    }

    public getExtraData(): object {
        return this.extraData;
    }

    public setExtraData(key: string, value: any) {
        this.extraData[key] = value;
    }

    public execute(actionPayload: ActionRequest, requestURI: string): Promise<ActionResponse> {
        return new Promise((resolve, reject) => {
            $.extend(actionPayload, {
                payload: this.getExtraData()
            });

            $.ajax({
                url: requestURI,
                data: actionPayload,
                type: 'POST'
            }).done((result) => {
                let backendResponse: ActionResponse = result;

                if (backendResponse.error) {
                    reject(new Error(backendResponse.error));
                }

                if (backendResponse.payload == null &&
                    backendResponse.html == null &&
                    backendResponse.redirect == null) {
                    reject(new Error('Unexpected response'));
                }

                resolve(backendResponse);
            }).fail((jqXHR, textStatus, errorThrown) => {
                reject(new Error(errorThrown));
            })
        });
    }
}
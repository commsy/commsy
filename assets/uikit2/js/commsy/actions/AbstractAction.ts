'use strict';

import {ActionData, ActionRequest, ActionResponse} from "./Actions";
import * as $ from "jquery";
import htmlString = JQuery.htmlString;

declare var UIkit: any;

export abstract class BaseAction {
    private _actionData: ActionData;

    /**
     * Returns true if this action needs to load additional form controls, otherwise false. Defaults to false.
     */
    private _wantsCustomFormData: boolean;

    constructor(actionData: ActionData, wantsCustomFormData = false) {
        this._actionData = actionData;
        this._wantsCustomFormData = wantsCustomFormData;
    }

    get actionData(): ActionData {
        return this._actionData;
    }

    get wantsCustomFormData(): boolean {
        return this._wantsCustomFormData;
    }

    public loadCustomFormData(requestURI: string): Promise<ActionResponse> {
        return new Promise<ActionResponse>((resolve) => {
            resolve(null);
        });
    }

    public onPostLoadCustomFormData(backendResponse: ActionResponse): Promise<ActionResponse> {
        return new Promise<ActionResponse>((resolve) => {
            resolve(backendResponse);
        });
    }

    public preExecute(actionActor: JQuery): Promise<void> {
        return new Promise<void>((resolve) => {
            resolve();
        });
    }

    public execute(actionPayload: ActionRequest, requestURI: string): Promise<ActionResponse> {
        return new Promise<ActionResponse>((resolve) => {
            resolve(null);
        });
    }

    public onSuccess(backendResponse: ActionResponse): Promise<boolean> {
        return new Promise<boolean>((resolve) => {
            resolve(true);
        });
    }

    public onError(error: Error) {
        UIkit.notify(this._actionData.errorMessage, 'danger');
    }
}


export abstract class XHRAction extends BaseAction {
    private extraData: object;

    constructor(actionData: ActionData, wantsCustomFormData = false) {
        super(actionData, wantsCustomFormData);

        this.extraData = {};
    }

    public getExtraData(): object {
        return this.extraData;
    }

    public setExtraData(key: string, value: any) {
        this.extraData[key] = value;
    }

    // TODO: do we actually need a separate loadCustomFormData() method or could execute() be (re)used instead?
    public loadCustomFormData(requestURI: string): Promise<ActionResponse> {
        return new Promise<ActionResponse>((resolve, reject) => {
            $.ajax({
                url: requestURI,
                type: 'GET'
            }).done((result) => {
                let backendResponse: ActionResponse = result; // result (a JsonHTMLResponse) is also a valid ActionResponse

                resolve(backendResponse);
            }).fail((jqXHR, textStatus, errorThrown) => {
                reject(new Error(errorThrown));
            })
        });
    }

    public execute(actionPayload: ActionRequest, requestURI: string): Promise<ActionResponse> {
        return new Promise<ActionResponse>((resolve, reject) => {
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
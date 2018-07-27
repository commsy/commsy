import {ActionData, ActionResponse} from "./Actions";
import {XHRAction} from "./AbstractAction";

export class TodoStatusAction extends XHRAction {
    private returnUrl: string;
    private status: string;

    constructor(actionData: ActionData) {
        super(actionData);

        let todoData: any = actionData;
        this.returnUrl = todoData.returnUrl;
        this.status = todoData.status;
    }

    public preExecute(actionActor: JQuery): Promise<void> {
        return new Promise((resolve) => {
            this.setExtraData('status', this.status);

            resolve();
        });
    }

    public onSuccess(backendResponse: ActionResponse): Promise<ActionResponse> {
        return new Promise((resolve) => {
            let payload: any = backendResponse.payload;

            UIkit.notify(payload.message, 'success');
            window.location.href = this.returnUrl;

            resolve(backendResponse);
        });
    }
}
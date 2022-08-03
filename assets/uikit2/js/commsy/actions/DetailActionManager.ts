import * as $ from 'jquery';

'use strict';

import {DetailActionData, ActionExecuter, createAction, ActionRequest} from "./Actions";
import {BaseAction} from "./AbstractAction";

declare var UIkit: any;

export class DetailActionManager {
    public registerActors() {
        $('[data-cs-action]').each((index, element) => {
            let $actor: JQuery = $(element);

            let actionData: DetailActionData = $actor.data('cs-action');

            if (actionData.mode == 'click') {
                $actor.on('click', (event) => {
                    event.preventDefault();

                    let action: BaseAction = createAction(actionData);

                    let actionExecuter: ActionExecuter = new ActionExecuter();
                    let actionRequest: ActionRequest = actionExecuter.buildActionRequest(
                        action,
                        [actionData.itemId],
                        [],
                        false,
                        0
                    );

                    actionExecuter.invoke($actor, action, actionRequest)
                        .catch((error: Error) => {
                            // Catching here does not have to be a fatal error, e.g. rejecting a confirm dialog.
                            // So we check for the error parameter
                            if (error) {
                                UIkit.notify(error.message, 'danger');
                            }
                        });
                });
            }
        });
    }
}
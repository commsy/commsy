import * as $ from 'jquery';

'use strict';

import UIkit from 'uikit';
import {DetailActionData, ActionExecuter, createAction} from "./Actions";
import {BaseAction} from "./AbstractAction";

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
                    actionExecuter.invokeAction($actor, action, actionData.itemId)
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
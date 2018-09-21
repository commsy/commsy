import * as $ from 'jquery';

'use strict';

import {ActionData, ActionResponse} from "./Actions";
import {XHRAction} from "./AbstractAction";

export class JoinAction extends XHRAction {
    private successMessage: string;
    private groupId: number;

    constructor(actionData: ActionData) {
        super(actionData);
    }

    public preExecute(actionActor: JQuery): Promise<void> {
        return new Promise((resolve) => {
            this.successMessage = actionActor.data('cs-action').successMessage;
            this.groupId = actionActor.data('cs-action').itemId;

            resolve();
        });
    }

    public onSuccess(backendResponse: ActionResponse): Promise<ActionResponse> {
        return new Promise((resolve) => {
            UIkit.notify(this.successMessage, 'success');

            window.location.href = window.location.href;

            // update 'additional actions' list
            $('#leave-group-link').removeClass('uk-text-muted').css('pointer-events', 'auto');
            $('#join-group-link, #join-group-and-room-link').addClass('uk-text-muted').css('pointer-events', 'none');

            // update member information
            let $membersDiv = $("#member" + this.groupId);
            if($membersDiv.length > 0) {
                let membersUrl = this.url.replace("join", "members");
                $.ajax({
                    url: membersUrl,
                    type: 'POST',
                    data: JSON.stringify({})
                }).done(function(result) {
                    $membersDiv.html(result);
                });
            }

            // update grouproom information
            let $grouproomDiv = $("#grouproom" + this.groupId);
            if($grouproomDiv.length > 0) {
                let grouproomUrl = this.url.replace("join", "grouproom");
                $.ajax({
                    url: grouproomUrl,
                    type: 'POST',
                    data: JSON.stringify({})
                }).done(function(result) {
                    $grouproomDiv.html(result);
                });
            }

            // update link information
            let $linksDiv = $("#links" + this.groupId);
            if($linksDiv.length > 0) {
                let linksUrl = this.url.replace("group", "item").replace("join", "links");
                $.ajax({
                    url: linksUrl,
                    type: 'POST',
                    data: JSON.stringify({})
                }).done(function(result) {
                    $linksDiv.html(result);
                });
            }

            resolve(backendResponse);
        });
    }
}
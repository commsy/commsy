import * as $ from 'jquery';

'use strict';

import {ActionData, ActionResponse} from "./Actions";
import {XHRAction} from "./AbstractAction";

export class DeleteAction extends XHRAction {
    private itemType: string;
    private title: string;
    private confirmQuestion: string;
    private confirmCancel: string;
    private confirmOk: string;
    private returnUrl: string;

    constructor(actionData: ActionData) {
        super(actionData);

        let deleteData: any = actionData;

        this.itemType = deleteData.itemType;
        this.title = deleteData.title;
        this.confirmQuestion = deleteData.confirmQuestion;
        this.confirmCancel = deleteData.confirmCancel;
        this.confirmOk = deleteData.confirmOk;
        this.returnUrl = deleteData.returnUrl;
    }

    public preExecute(actionActor: JQuery): Promise<void> {
        return new Promise((resolve, reject) => {
            // deletion required separate confirmation
            UIkit.modal.confirm(this.confirmQuestion, () => {
                resolve();
            }, () => {
                reject();
            }, <any>{
                labels: {
                    Cancel: this.confirmCancel,
                    Ok: this.confirmOk
                }
            });

            // TODO:
            //     var $confirm_calendar_delete = false;
            //     $('#calendar_edit_delete').on('click', function(event){
            //         if ($confirm_calendar_delete) {
            //             $confirm_calendar_delete = false;
            //             return;
            //         }
            //
            //         event.preventDefault();
            //         let $this = $(this);
            //         UIkit.modal.confirm($(this).data('confirm-delete'), function() {
            //             $confirm_calendar_delete = true;
            //             $this.trigger('click');
            //         }, {
            //             labels: {
            //                 Cancel: $(this).data('confirm-delete-cancel'),
            //                 Ok: $(this).data('confirm-delete-confirm')
            //             }
            //         });
            //     });
        });
    }

    public onSuccess(backendResponse: ActionResponse): Promise<ActionResponse> {
        return new Promise((resolve) => {
            switch (this.itemType) {
                case 'section':
                    $('.material-section').hide();
                case 'step':
                    $('.todo-step').hide();
                case 'discarticle':
                    $('.discussion-article').hide();

                    let urlPathParts = this.url.split("/");
                    let listElement = $("#" + this.itemType + "-list a[href='#" + this.itemType + urlPathParts[urlPathParts.length-2]+"']").closest("li");
                    listElement.nextAll("li").each(function(){
                        let lineParts = $(this).find("a").text().trim().split(" ");
                        lineParts[0] = (parseInt(lineParts[0]) - 1).toString() + ".";
                        $(this).find("a").text(lineParts.join(" "));
                    });
                    listElement.remove();
                    let listHeader = $("#" + this.itemType + "-list").closest("article").find("h4").first();
                    listHeader.text( listHeader.text().replace(/\d+/g, $("#" + this.itemType + "-list li").length.toString()));

                    break;

                default:
                    window.location.href = this.returnUrl;
            }

            resolve(backendResponse);
        });
    }
}
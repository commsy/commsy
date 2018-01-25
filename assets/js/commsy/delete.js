;(function(UI){

    "use strict";

    $('.commsy-action-delete').on('click', function(event) {
        let $this = this;
        event.preventDefault();
        UIkit.modal.confirm($(this).data('confirm-delete'), function() {
            $.ajax({
                url: $($this).data('deleteUrl'),
                type: 'POST',
                data: {}
            })
            .done(function(result) {
                var type = $($this).data('itemType');
                if (type == 'section' || type == 'step' || type == 'discarticle') {
                    if(type == 'section'){
                        $($this).parents('.material-section').hide();
                    }
                    if(type == 'step'){
                        $($this).parents('.todo-step').hide();
                    }
                    if(type == 'discarticle'){
                        $($this).parents('.discussion-article').hide();
                    }
                    var urlPathParts = $($this).data('deleteUrl').split("/");
                    var listElement = $("#"+type+"-list a[href='#"+type+urlPathParts[urlPathParts.length-2]+"']").closest("li");
                    listElement.nextAll("li").each(function(){
                        var lineParts = $(this).find("a").text().trim().split(" ");
                        lineParts[0] = (parseInt(lineParts[0]) - 1).toString() + ".";
                        $(this).find("a").text(lineParts.join(" "));
                    });
                    listElement.remove();
                    var listHeader = $("#"+type+"-list").closest("article").find("h4").first();
                    listHeader.text( listHeader.text().replace(/\d+/g, $("#"+type+"-list li").length));
                } else {
                    location.href = $($this).data('returnUrl');
                }
            });
        }, {
            labels: {
                Cancel: $(this).data('confirm-delete-cancel'),
                Ok: $(this).data('confirm-delete-confirm')
            }
        });
    });

    var $confirm_calendar_delete = false;
    $('#calendar_edit_delete').on('click', function(event){
        if ($confirm_calendar_delete) {
            $confirm_calendar_delete = false;
            return;
        }

        event.preventDefault();
        let $this = $(this);
        UIkit.modal.confirm($(this).data('confirm-delete'), function() {
            $confirm_calendar_delete = true;
            $this.trigger('click');
        }, {
            labels: {
                Cancel: $(this).data('confirm-delete-cancel'),
                Ok: $(this).data('confirm-delete-confirm')
            }
        });
    });

})(UIkit);
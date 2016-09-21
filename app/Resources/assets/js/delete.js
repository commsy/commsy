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
                if (type == 'section') {
                    $($this).parents('.material-section').hide();
                    var urlPathParts = $($this).data('deleteUrl').split("/");
                    var sectionLi =$("#section-list a[href='#section"+urlPathParts[urlPathParts.length-2]+"']").closest("li");
                    sectionLi.nextAll("li").each(function(){
                        var sectionLineParts = $(this).find("a").text().trim().split(" ");
                        sectionLineParts[0] = (parseInt(sectionLineParts[0]) - 1).toString() + ".";
                        $(this).find("a").text(sectionLineParts.join(" "));
                    });
                    sectionLi.remove();
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

})(UIkit);
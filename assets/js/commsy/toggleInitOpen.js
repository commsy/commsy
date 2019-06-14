;(function(UI){

    "use strict";
    
    $(document).ready(function() {
        if( $("#detail_toggle_long").length) {
            let toggleElement = $( "div[id^='links']" ).find('a.cs-toggle');
            toggleElement.click();
        }else{
        }
    });

})(UIkit);
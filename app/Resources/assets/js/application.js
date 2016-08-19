;(function($, document, window) {
    "use strict";

    // highligh sections that can be toggled
    $(document).ready(function() {
        $('div.cs-toggle')
            .mouseover(function() {
                $(this).toggleClass('cs-toggle-selected', true);
            })
            .mouseout(function() {
                $(this).toggleClass('cs-toggle-selected', false);
            });
    });

    $(window).on( "load resize", function(){
        // tooltip left margin only needs to be updated manually in firefox
        if (typeof InstallTrigger !== 'undefined'){
            var stylesheet = document.styleSheets[0];
            if(stylesheet.cssRules[stylesheet.cssRules.length-1].cssText.substring(0, 11) == ".uk-tooltip"){
                stylesheet.cssRules[stylesheet.cssRules.length-1].style.marginLeft = $('body').offset().left+"px";
            }
            else{
                stylesheet.insertRule(".uk-tooltip {margin-left: "+$('body').offset().left+"px}", stylesheet.cssRules.length);
            }
        }
    });

    // NProgress configuration
    NProgress.configure({
        showSpinner: false,
        speed: 700,
        minimum: 0.2
    });

    // global AJAX event handler
    $(document).ajaxSend(function() {
        NProgress.start();
        NProgress.inc(0.1);   
    });

    $(document).ajaxStop(function() {
        NProgress.done();
    });

    // global unload event
    $(window).on('beforeunload', function() {
        NProgress.start();
        NProgress.inc(0.1);
    });

})(jQuery, document, window);
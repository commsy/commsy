/*
*   CKEditor plugin which on scroll or resize keeps the CKEditor toolbar fixed at the top of the screen.
*   Based on the "Fixed toolbar on top" plugin (<https://ckeditor.com/cke4/addon/fixed>).
*/

CKEDITOR.plugins.add('stickytoolbar', {
    init: function (editor) {
        window.addEventListener('scroll', manageToolbarStickiness, false);
        window.addEventListener('resize', manageToolbarStickiness, false);

        function manageToolbarStickiness() {

            // since offsetTop takes the position relative to its parent element(s) having a relative
            // position, we iterate over all the parent elements
            const getOffset = (element, horizontal = false) => {
                if (!element) return 0;
                return getOffset(element.offsetParent, horizontal) + (horizontal ? element.offsetLeft : element.offsetTop);
            }

            // var content = document.getElementsByClassName('cke_contents').item(0);
            var toolbar = document.getElementsByClassName('cke_top').item(0);
            var editor = document.getElementsByClassName('cke').item(0);
            var inner = document.getElementsByClassName('cke_inner').item(0);

            // body.scrollTop will work for Safari, documentElement.scrollTop for other browsers
            var scrollvalue = document.documentElement.scrollTop > document.body.scrollTop ? document.documentElement.scrollTop : document.body.scrollTop;

            toolbar.style.width = editor.clientWidth + "px";
            toolbar.style.boxSizing = "border-box";

            // top CKEditor area partly hidden
            if (getOffset(editor) <= scrollvalue) {
                toolbar.style.position = "fixed";
                toolbar.style.top = "0px";
                inner.style.paddingTop = toolbar.offsetHeight + "px";
            }

            // CKEditor area not hidden
            if (getOffset(editor) > scrollvalue && (getOffset(editor) + editor.offsetHeight) >= (scrollvalue + toolbar.offsetHeight)) {
                toolbar.style.position = "relative";
                toolbar.style.top = "auto";
                inner.style.paddingTop = "0px";
            }

            const minContentHeight = toolbar.offsetHeight * 2;

            // not enough CKEditor area left to display the full toolbar
            if ((getOffset(editor) + editor.offsetHeight) < (scrollvalue + minContentHeight)) {
                toolbar.style.position = "absolute";
                toolbar.style.top = "calc(100% - " + minContentHeight + "px)";
                inner.style.position = "relative";
            }
        }
    }
});

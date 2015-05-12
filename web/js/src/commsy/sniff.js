define(["dojo/has"], function(has) {
	
    // check for windows os
    has.add("isWindows", function() {
        return (navigator.appVersion.indexOf("Win")!=-1);
    });
});
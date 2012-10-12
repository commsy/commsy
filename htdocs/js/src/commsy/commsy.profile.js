var profile = (function(){
	
	var		testResourceRegEx = /^commsy\/tests\//,
			
			copyOnly = function(filename, mid){
				var list = {
					"commsy/commsy.profile":	true,
					"commsy/package.json":		true
				};
				return		(mid in list) ||														/* check for files in list */
							(/^commsy\/resources\//.test(mid) && !/\.css$/.test(filename)) ||		/* check for files in /resources but not css */
							/(png|jpg|jpeg|gif|tiff)$/.test(filename);								/* check for images */
			};
	
    return {
        resourceTags: {
        	test: function(filename, mid){
				return testResourceRegEx.test(mid) || mid=="dijit/robot" || mid=="dijit/robotx";
			},

			copyOnly: function(filename, mid){
				return copyOnly(filename, mid);
			},

			amd: function(filename, mid){
				return !testResourceRegEx.test(mid) && !copyOnly(filename, mid) && /\.js$/.test(filename) && !/^commsy\/ckeditor\/plugins\//.test(mid);
			},

			miniExclude: function(filename, mid){
				return false;
				//return /^commsy\/bench\//.test(mid) || /^dijit\/themes\/themeTest/.test(mid);
			},
			
			declarative: function(flename, mid) {
				return false;
			}
        }
    };
})();
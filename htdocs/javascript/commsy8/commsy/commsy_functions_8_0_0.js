/**
 * CommSy Functions Module
 */

define(["libs/jQuery/jquery-1.7.1.min"], function() {
	return {
		init: function() {
			// wait for dom loaded
			jQuery(document).ready(this.onDomLoaded());
		},
		
		onDomLoaded: function() {
			// Tag Tree
			// look for divs with id="tag_tree"
			jQuery('div[id="tag_tree"]').each(function() {
				var div_object = jQuery(this);
				require(["commsy/tag_tree"], function($) {
					// build tag tree
					$.buildTree(div_object);
				});
			});
			
			// home list rubric expander
			// only load on home context
			if(this.getURLParam('mod') === 'home') {
				require(["commsy/div_expander"], function($) {
					// register the click event on </a>- and </img>-tags(actors) of each rubric to the corresponding div
					var expanderMapping = [];
					
					// go through each list wrap
					jQuery('div[class="content_item"] div[class="list_wrap"]').each(function() {
						// find actors
						var actors = [];
						actors.push(jQuery(this).parent().find('a[class="open_close"]'));
						actors.push(actors[0].children());
						
						// register actors to div
						$.registerEvent(jQuery(this), actors, 'click', false);
					});
				});
			}
		},
		
		getURLParam: function(name) {
			name = name.replace(/[\[]/,"\\\[").replace(/[\]]/,"\\\]");
			var regexS = "[\\?&]"+name+"=([^&#]*)";
			var regex = new RegExp( regexS );
			var results = regex.exec( window.location.href );
			if( results == null )
				return "";
			else
			    return results[1];
		}
	};
});
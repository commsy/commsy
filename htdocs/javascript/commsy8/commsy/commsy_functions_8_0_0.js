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
			
			// list selection
			if(this.getURLParam('fct') === 'index') {
				require(["commsy/list_selection"], function($) {
					// get input tags
					var input_tags = jQuery('input[type="checkbox"][name^="attach"]');
					
					// get selection state from cookies
					input_tags.each(function() {
						if($.getSelectionStateFromCookie(jQuery(this))) {
							jQuery(this).attr('checked', 'checked');
						}
					});
					
					// store selection in cookie
					input_tags.each(function() {
						var input_object = jQuery(this);
						
						// register onChange
						input_object.change({input_object: input_object, class_ref: $}, $.storeSelectionInCookie);
					});
					
					//$.storeSelectioninCookie();
				});
			}
			
			// home list rubric expander
			// only load on home context
			if(this.getURLParam('mod') === 'home') {
				require(["commsy/div_expander"], function($) {
					// register the click event on </a>- and </img>-tags(actors) of each rubric to the corresponding div
					
					// go through each list wrap
					jQuery('div[class="content_item"] div[class="list_wrap"]').each(function() {
						// find actors
						var actors = [];
						var a = {
							object: jQuery(this).parent().find('a[class="open_close"]')
						};
						var img = {
							object: a.object.children(),
							images: ['btn_ci_close.gif', 'btn_ci_open.gif']
						};
						a.modify_images = img;
						
						actors.push(a);
						actors.push(img);
						
						// register actors to div
						$.registerEvent(jQuery(this), actors, 'click');
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
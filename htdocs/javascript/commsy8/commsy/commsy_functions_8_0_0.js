/**
 * CommSy Functions Module
 */

define(["libs/jQuery/jquery-1.7.1.min"], function() {
	return {
		init: function() {
			// wait for dom loaded
			jQuery(document).ready(function() {
				// look for divs with id="tag_tree"
				jQuery('div[id="tag_tree"]').each(function() {
					var div_object = jQuery(this);
					require(["commsy/tag_tree"], function($) {
						// build tag tree
						$.buildTree(div_object);
					});
				});
			});
		}
	};
});
/**
 * Discussion Tree Module
 */

define([	"order!libs/jQuery/jquery-1.7.1.min",
        	"order!libs/jQuery/jquery-ui-1.8.16.custom.min",
        	"order!libs/jQuery_plugins/dynatree-1.2.0/jquery.cookie",
        	"order!libs/jQuery_plugins/dynatree-1.2.0/jquery.dynatree.min",
        	"commsy/commsy_functions_8_0_0"], function() {
	return {
		init: function(commsy_functions, object) {
			jQuery.ui.dynatree.nodedatadefaults['icon'] = false;		// Turn off icons by default
			
			// set preconditions
			this.setPreconditions(commsy_functions, this.setup, {object: object, handle: this});
		},
		
		setPreconditions: function(commsy_functions, callback, parameters) {
			var preconditions = {
			};
			
			// register preconditions
			commsy_functions.registerPreconditions(preconditions, callback, parameters);
		},
		
		initProgressBar: function() {
			var tree = jQuery('[id=discussion_tree]');

			if(tree.length) {
				jQuery.ui.dynatree.nodedatadefaults["icon"] = false;

				// set progressbar
				jQuery('[id=discussion_tree_progressbar]').progressbar();

				// get div width
				var div_width = jQuery('div[class="item_body"]').width();
				
				var creators = jQuery('[id=discussion_tree] [class=discussion_threaded_tree_creator]');
				var dates = jQuery('[id=discussion_tree] [class=discussion_threaded_tree_date]');
				
				this.formatDiscussionTreeWithProgress(div_width, 0, creators, dates);

				return false;
			}
		},
		
		formatDiscussionTreeWithProgress: function(div_width, iteration, creators, dates) {
			var total = creators.length;
			
			if(iteration == total) {
				this.setupDiscussionTree();
				return false;
			}

			// do css stuff
			// set all creator texts at 50%
			var creator_width = (Math.floor(div_width / 2) * 1);
			jQuery(creators[iteration]).css('position', 'absolute');
			jQuery(creators[iteration]).css('display', 'inline');
			jQuery(creators[iteration]).css('left', creator_width);

			// set all date texts at 80%
			var date_width = (Math.floor(div_width / 5) * 4);
			jQuery(dates[iteration]).css('position', 'absolute');
			jQuery(dates[iteration]).css('display', 'inline');
			jQuery(dates[iteration]).css('left', date_width);

			// update progressbar
			var percent = (iteration+1) * 100 / total;
			jQuery('[id=discussion_tree_progressbar]').progressbar("value", percent);
			jQuery('[id=discussion_tree_progressbar_percent]').html(Math.floor(percent));

			// call recursivly
			var handle = this;
			setTimeout(function() {
				handle.formatDiscussionTreeWithProgress(div_width, ++iteration, creators, dates);
			}, 1);

			return false;
		},
		
		setupDiscussionTree: function() {
			var tree = jQuery('[id=discussion_tree]');
	
			tree.dynatree({
				fx: { height: "toggle", duration: 200 },
				onActivate: function(dtnode) {
					if(dtnode.data.url) {
						try {
							window.location(dtnode.data.url);
						}
						catch(e) {
						}
					}
				},
				onClick: function(dtnode, event) {
					/*
					// Hervorgehobenen Hintergrund verhindern, wenn nicht auf einen Link für einen Beitrag geklickt wird
					if(	event.target.nodeName == 'IMG' ||
							(event.target.nodeName == 'SPAN' &&
							event.target.className != 'ui-dynatree-expander')) {
						return false;
					}
	
					// set max tree depth
					if(dtnode.getLevel() > 11) return false;
	
					if(event.target.nodeName == 'A') {
						jQuery(location).attr('href', event.target.href);
	
						return false;
					}
					*/
				}
			});
			
			/*
			var max_visible_nodes = 10;
			var max_expand_level = getExpandLevel(tree, max_visible_nodes);
	
			// root immer ausklappen
			if(max_expand_level < 2) max_expand_level = 2;
			
			*/
			tree.dynatree("getRoot").visit(function(dtnode) {
				//if(dtnode.getLevel() < max_expand_level) {
					dtnode.expand(true);
				//}
			});
			/*
	
			// "ge�nderte" und "neue" Eintr�ge ausklappen
			jQuery('[id=discussion_tree]').dynatree("getRoot").visit(function(dtnode) {
			    var title = dtnode.data.title;
			    var regexp = /(change)/g;
	
			    if(regexp.test(title) == true) {
			    	dtnode.focus();
			    }
			});
			
			// build show all / hide all link
			
			var showAndHide = {
				status: "hide",
				
				init: function(tree, span_id) {
					jQuery(document).ready(function($) {
						jQuery('span[id="' + span_id + '"]').append('<a id="dicussion_threaded_show_hide_a" href="#"></a>');
					
						var link = jQuery('a[id="dicussion_threaded_show_hide_a"]');
						
						// get actual dynatree status - try to find a not expanded node
						tree.dynatree("getRoot").visit(function(node) {
							if(!node.isVisible()) {
								showAndHide.status = 'show';
								return false;
							}
						}, false);
						
						// set link text
						if(showAndHide.status == 'show') {
							link.text(show_all);
						} else {
							link.text(hide_all);
						}
						
						// bind onClick
						link.bind('click', function(e) {
							showAndHide.onClick($, tree, link, e);
						});
					});
				},
				
				onClick: function($, tree, link, e) {
					// switch status - expand / compress tree
					if(showAndHide.status == 'show') {
						link.text(hide_all);
						showAndHide.status = 'hide';
						tree.dynatree("getRoot").visit(function(node) {
							node.expand(true);
						});
					} else {
						link.text(show_all);
						showAndHide.status = 'show';
						tree.dynatree("getRoot").visit(function(node) {
							node.expand(false);
						}, false);
					}
				}
			};
			
			showAndHide.init(tree, "discussion_show_hide_all");
			*/
			
			// make tree visible
			jQuery('div[id=discussion_tree]').fadeIn(200);
	
			// remove progressbar
			jQuery('div[id=discussion_tree_progressbar_wrap]').remove();
	
			// set commsy body to a fixed size
			/*
			var body_width = jQuery('[class=commsy_body]').width();
			jQuery('[class=commsy_body]').css('width', body_width);
			jQuery('[class=commsy_footer]').css('width', body_width);
			*/
		},
		
		setup: function(preconditions, parameters) {
			var handle = parameters.handle;
			
			handle.initProgressBar();
		}
	};
});
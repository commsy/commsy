/**
 * CommSy Functions Module
 */

define(["libs/jQuery/jquery-1.7.1.min"], function() {
	return {
		preconditions_callbacks: null,
		modules_registered: 0,
		modules_loaded: 0,
		
		init: function() {
			// wait for dom loaded
			jQuery(document).ready(this.onDomLoaded());
		},
		
		onDomLoaded: function() {
			var commsy_functions = this;
			
			// Tag Tree
			this.registerModule('commsy/tag_tree', 'div[id="tag_tree"]');
			
			// Threaded Discussion Tree
			if(this.getURLParam('mod') === 'discussion' && this.getURLParam('fct') === 'detail') {
				this.registerModule('commsy/discussion_tree', 'div[id="discussion_tree"]');
			}
			
			// Uploadify
			var upload_object = jQuery('a[id="uploadify_doUpload"]');
			var clear_object = jQuery('a[id="uploadify_clearQuery"]');
			this.registerModule('commsy/uploadify', 'input[id="uploadify"]', {upload_object: upload_object, clear_object: clear_object});
			
			// list selection
			if(this.getURLParam('fct') === 'index') {
				// get input tags
				var input_tags = jQuery('input[type="checkbox"][name^="attach"]');
				
				// get counter objects
				var counter_object = jQuery('div[class="ii_right"] span[id="selected_items"]');
				
				// register moule
				this.registerModule('commsy/list_selection', null, {input_tags: input_tags, counter_object: counter_object});
			}
			
			// list rubric expander
			// register the click event on </a>- and </img>-tags(actors) of each rubric to the corresponding div
			
			// go through each list wrap
			var objects = [];
			jQuery('div[class="content_item"] div[class^="list_wrap"]').each(function() {
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
				
				objects.push({actors: actors, div: jQuery(this)});
			});
			
			this.registerModule('commsy/div_expander', null, {objects: objects, action: 'click'});
			
			// portlet expander
			var objects = [];
			jQuery('div[class="portlet_rc"]  div[class^="portlet_rc_body"]').each(function() {
				// find actors
				var actors = [];
				var a = {
					object: jQuery(this).parent().find('a[class="btn_head_rc"]')	
				};
				var img = {
					object: a.object.children(),
					images: ['btn_close_rc.gif', 'btn_open_rc.gif']
				};
				a.modify_images = img;
				
				actors.push(a);
				actors.push(img);
				
				objects.push({actors: actors, div: jQuery(this)});
			});
			
			this.registerModule('commsy/div_expander', null, {objects: objects, action: 'click'});
			
			// on detail context
			if(this.getURLParam('fct') === 'detail') {
				// action overlay
				var div_objects = jQuery('div[class="item_actions"]');
				
				this.registerModule('commsy/action_overlay', null, {objects: div_objects});
			}
			
			// ckeditor
			// load on detail context
			if(this.getURLParam('fct') === 'detail') {
				var input_object = jQuery('input[id="ckeditor_content"]');
				this.registerModule('commsy/ck_editor', 'div[id="ckeditor"]', {input_object: input_object});
				
				var input_object = jQuery('input[id="ckeditor_content_second"]');
				this.registerModule('commsy/ck_editor', 'div[id="ckeditor_second"]', {input_object: input_object});
			}
		},
		
		registerModule: function(module, selector, parameters) {
			var commsy_functions = this;	
			
			var register = true;
			
			// check arguments
			if(arguments.length === 1) {
				// selector and parameters not given
				require([module], function($) {
					// call init
					$.init(commsy_functions);
				});
			} else if(arguments.length === 2) {
				// selector given
				var count = 0;
				jQuery(selector).each(function() {
					var object = jQuery(this);
					count++;
					
					require([module], function($) {
						// call init
						$.init(commsy_functions, object);
					});
				});
				
				if(count === 0) register = false;
			} else {
				// parameter given, maybe selector
				if(selector === null) {
					// only parameters
					require([module], function($) {
						// call init
						$.init(commsy_functions, parameters);
					});
				} else {
					// all arguments given
					var count = 0;
					jQuery(selector).each(function() {
						var object = jQuery(this);
						count++;
						
						require([module], function($) {
							// call init
							$.init(commsy_functions, object, parameters);
						});
					});
					if(count === 0) register = false;
				}
			}
			
			if(register === true) this.modules_registered++;
		},
		
		registerPreconditions: function(conditions, callback, parameters) {
			if(this.preconditions_callbacks === null) this.preconditions_callbacks = new Array;
			
			var store = {
					conditions: conditions,
					callback: callback,
					parameters: parameters
			};
			this.preconditions_callbacks.push(store);
			this.modules_loaded++;
			
			if(this.modules_loaded === this.modules_registered) this.processPreconditions();
		},
		
		processPreconditions: function() {
			var merge = new Object;
			
			jQuery(this.preconditions_callbacks).each(function() {
				jQuery.extend(merge, this.conditions);
			});
			
			var cid = this.getURLParam('cid');
			
			var handle = this;
			
			jQuery.ajax({
				type: 'POST',
				url: 'commsy.php?cid=' + cid + '&mod=ajax&fct=preconditions&action=getInfo',
				data: JSON.stringify(merge),
				contentType: 'application/json; charset=utf-8',
				dataType: 'json',
				error: function() {
					console.log("error while getting preconditions");
				},
				success: function(data, status) {
					handle.preconditionsSuccess(data);
				}
			});
		},
		
		preconditionsSuccess: function(data) {
			jQuery(this.preconditions_callbacks).each(function() {
				// callback
				this.callback(data, this.parameters);
			});
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
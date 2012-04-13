/**
 * CommSy Functions Module
 */

define(["libs/jQuery/jquery-1.7.1.min"], function() {
	return {
		preconditions_callbacks: null,
		module_callbacks: null,
		modules_registered: 0,
		modules_loaded: 0,
		
		init: function() {
			// wait for dom loaded
			jQuery(document).ready(this.onDomLoaded());
		},
		
		onDomLoaded: function() {
			var commsy_functions = this;
			
			// Tag Tree
			this.registerModule('commsy/tag_tree', {register_on: jQuery('div[id="tag_tree"]')});
			
			// Threaded Discussion Tree
			if(this.getURLParam('mod') === 'discussion' && this.getURLParam('fct') === 'detail') {
				this.registerModule('commsy/discussion_tree', {register_on: 'div[id="discussion_tree"]'});
			}
			
			// Uploadify
			var upload_object = jQuery('a[id="uploadify_doUpload"]');
			var clear_object = jQuery('a[id="uploadify_clearQuery"]');
			this.registerModule('commsy/uploadify', {register_on: jQuery('input[id="uploadify"]'), upload_object: upload_object, clear_object: clear_object});
			
			// list selection
			if(this.getURLParam('fct') === 'index') {
				// get input tags
				var input_tags = jQuery('input[type="checkbox"][name^="form_data[attach]"]');
				
				// get counter objects
				var counter_object = jQuery('div[class="ii_right"] span[id="selected_items"]');
				
				// register moule
				this.registerModule('commsy/list_selection', {input_tags: input_tags, counter_object: counter_object});
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
			
			this.registerModule('commsy/div_expander', {objects: objects, action: 'click'});
			
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
			
			this.registerModule('commsy/div_expander', {objects: objects, action: 'click'});
			
			// on detail context
			var objects = [];
			if(this.getURLParam('fct') === 'detail') {
				// action expander
				var actors = [];
				var objects = [];
				
				// edit
				jQuery.merge(actors, jQuery('div[class="item_actions"] a[class="edit"]'));
				jQuery.merge(objects, jQuery('div[class="content_item"] div[class^="fade_in_ground_actions"]'));
				
				// detail
				jQuery.merge(actors, jQuery('div[class="item_actions"] a[class="detail"]'));
				jQuery.merge(objects, jQuery('div[class="content_item"] div[class^="fade_in_ground_panel"]'));
				
				// linked
				jQuery.merge(actors, jQuery('div[class="item_actions"] a[class="linked"]'));
				jQuery.merge(objects, jQuery('div[class="content_item"] div[class^="fade_in_ground_linked"]'));
				
				// annotations
				jQuery.merge(actors, jQuery('div[class="item_actions"] a[class="annotations"]'));
				jQuery.merge(objects, jQuery('div[class="content_item"] div[class^="fade_in_ground_annotations"]'));
				
				this.registerModule('commsy/action_expander', {actors: actors, objects: objects});
				
				// follow anchors
				if(window.location.href.indexOf("#") !== -1) {
					var anchor = window.location.href.substring(window.location.href.indexOf("#") + 1);

					this.registerModule('commsy/anchor_follower', {anchor: anchor});
				}
			}
			
			// progressbar
			var objects = [];
			
			objects = jQuery('div[class="progressbar"]');
			this.registerModule('commsy/progressbar', {objects: objects});
			
			// ckeditor
			// load on detail context
			//if(this.getURLParam('fct') === 'detail') {
				var input_object = jQuery('input[id="ckeditor_content"]');
				this.registerModule('commsy/ck_editor', {register_on: jQuery('div[id="ckeditor"]'), input_object: input_object});
				
				var input_object = jQuery('input[id="ckeditor_content_second"]');
				this.registerModule('commsy/ck_editor', {register_on: jQuery('div[id="ckeditor_second"]'), input_object: input_object});
			//}
			
			// search
			if(this.getURLParam('mod') === 'search') {
				this.registerModule('commsy/search', 'input[id="search_input"]');
			}
			
			// ajax attachment overlay
			this.registerModule('commsy/attachments_overlay', 'a[class="attachment"]');
			
			// lightbox
			this.registerModule('commsy/lightbox', 'a[rel="lightbox"]');
			
			// noticed list overlay
			if(this.getURLParam('fct') === 'index') {
				this.registerModule('commsy/noticed_overlay', 'a[class="new_item_2"]');
			}
			
			// ajax popup handler
			var new_objects = jQuery('a[id="create_new"]');
			var edit_objects = jQuery('a[id="action_edit"]');
			var objects = [];
			jQuery.merge(objects, new_objects);
			jQuery.merge(objects, edit_objects);
			
			var handling = {
				objects:	objects/*,
				module:		this.getURLParam('mod')*/
			};
			
			this.registerModule('commsy/ajax_popup_handler', handling);
		},
		
		registerModule: function(module, parameters) {
			var commsy_functions = this;	
			
			var register = true;
			
			// check arguments
			if(arguments.length === 1) {
				// parameters not given
				require([module], function($) {
					// call init
					$.init(commsy_functions);
					commsy_functions.registerModuleCallback(module, $);
				});
			} else if(arguments.length === 2) {
				// parameters given
				require([module], function($) {
					// call init
					$.init(commsy_functions, parameters);
					commsy_functions.registerModuleCallback(module, $);
				});
			} else {
				// unknown
				register = false;
			}
			
			if(register === true) this.modules_registered++;
		},
		
		registerModuleCallback: function(module, callback) {
			if(this.module_callbacks === null) this.module_callbacks = new Array;
				
			var module_object = {
				name:		module,
				callback:	callback
			};
			this.module_callbacks.push(module_object);
			
			
		},
		
		getModuleCallback: function(module) {
			var ret = null;
			
			jQuery.each(this.module_callbacks, function() {
				if(this.name === module) {
					ret = this.callback;
					
					return false;
				}
			});
			
			return ret;
		},
		
		registerPreconditions: function(conditions, callback, parameters) {
			// if preconditions are empty, preprocessing is not needed - call directly
			if(jQuery.isEmptyObject(conditions)) {
				this.modules_loaded++;
				callback(null, parameters);
			} else {
				if(this.preconditions_callbacks === null) this.preconditions_callbacks = new Array;
			
				var store = {
						conditions: conditions,
						callback: callback,
						parameters: parameters
				};

				this.preconditions_callbacks.push(store);
				this.modules_loaded++;
			}
			
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
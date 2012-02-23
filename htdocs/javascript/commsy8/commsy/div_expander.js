/**
 * Div Expander Module
 */

define([	"libs/jQuery/jquery-1.7.1.min",
        	"commsy/commsy_functions_8_0_0"], function() {
	return {
		/*
		 * this module is a general implementation for expand/collapse-tasks
		 * target - specifies the div to update
		 * actors - an array of objects working as trigger specified as following:
		 * 		actor.object - the trigger object
		 * 		actor.images - if the object is an </img>-tag you can specify an array of two images(collapsing, expanding)
		 * 		actor.modify_images - a reference to an </img>-tag-object for updating(useful for surrounding elements)
		 * 
		 * event - the event type string
		 * 
		 * initial hidden tags need to be set to display 'none' with correct images
		 * 
		 * TODO: implement general updating mechanism for attributes(title, alt)
		 */
		init: function(commsy_functions, parameters) {
			parameters.handle = this;
			
			// set preconditions
			this.setPreconditions(commsy_functions, this.registerEvent, parameters);
		},
		
		setPreconditions: function(commsy_functions, callback, parameters) {
			var preconditions = {
			};
			
			// register preconditions
			commsy_functions.registerPreconditions(preconditions, callback, parameters);
		},
		
		registerEvent: function(preconditions, parameters) {
			var event = parameters.action;
			
			// store handler
			var handler = parameters.handle.onEvent;
			
			jQuery(parameters.objects).each(function() {
				var actors = this.actors;
				var target = this.div;
				
				// go through all actors
				jQuery.each(actors, function() {
					// bind
					this.object.bind(event, {actor: this, target: target, images: this.images, modify_images: this.modify_images}, handler);
				});
			});
		},
		
		onEvent: function(event) {
			var target = event.data.target;
			var images = event.data.images;
			var actor = event.data.actor;
			var modify_images = event.data.modify_images;
			
			// toggle
			target.toggle('fast', function() {
				// process images
				if(typeof(images) !== 'undefined') {
					// determe collapse / expand
					if(target.css('display') === 'none') {
						actor.object.attr('src', actor.object.attr('src').split(images[0]).join(images[1]));
					} else {
						actor.object.attr('src', actor.object.attr('src').split(images[1]).join(images[0]));
					}
				}
				
				// process modify images
				if(typeof(modify_images) !== 'undefined') {
					// determe collapse / expand
					if(target.css('display') === 'none') {
						modify_images.object.attr('src', modify_images.object.attr('src').split(modify_images.images[0]).join(modify_images.images[1]));
					} else {
						modify_images.object.attr('src', modify_images.object.attr('src').split(modify_images.images[1]).join(modify_images.images[0]));
					}
				}
			});
			
			// stop page reload
			return false;
		}
	};
});
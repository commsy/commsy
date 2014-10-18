define([	"dojo/_base/declare",
        	"commsy/base",
        	"commsy/request",
        	"dojox/widget/Pager",
        	"dojo/_base/fx",
        	"dojo/fx",
        	"dojo/dom-construct",
        	"dojo/query",
        	"dojo/on",
        	"dojo/dom",
        	"dojo/_base/lang",
        	"dojo/mouse",
        	"commsy/lightbox"
        	], function(declare, BaseClass, request, pager, coreFX, FX, DomConstruct, Query, On, Dom, lang, mouse, lightbox) {
	return declare(BaseClass, {
		carouselScroller:			null,
		newLeft:					0,
		boxMixin:					null,
		
		cid: 						null,
		item_id: 					null,
		module: 					null,
		imageCount:					0,
		
		constructor: function(options) {
			options = options || {};
			declare.safeMixin(this, options);
		},
		
		init: function(node, cid, item_id, module) 
		{
			this.cid = cid;
			this.item_id = item_id;
			this.module = module;
			
			this.initDo(node);
		},
		
		createLoadingAnimation: function(node, nodeId)
		{
			var loadingDivNode = DomConstruct.create("div",
			{
				style:			"margin-left: 10px: width: 200px;",
				id:				nodeId
			}, node, "first");
			
				DomConstruct.create("span",
				{
					innerHTML:		""
				}, loadingDivNode, "last");
				
				DomConstruct.create("img",
				{
					src:			this.from_php.template.tpl_path + "img/ajax_loader.gif",
					style:			"top: 50px; margin-bottom: 100px; position: relative;"
				}, loadingDivNode, "last");
		},
		
		destroyLoadingAnimation: function(nodeId)
		{
			DomConstruct.destroy(nodeId);
		},
		
		preloadImageEvent: function(node) 
		{
			On(node, "load", lang.hitch(this, function(event){
				//disable preloadImage for node
				this.destroyLoadingAnimation(node.getAttribute('id'));
			}));
		},
		
		hoverEvents: function() 
		{
			On(Dom.byId("carousel"), mouse.enter, function(){
				var buttonPrev = Dom.byId("buttonPrevSpan");
				var buttonNext = Dom.byId("buttonNextSpan");

				buttonNext.setAttribute('style', 'display: inline;');
				buttonPrev.setAttribute('style', 'display: inline;');

			});
			
			On(Dom.byId("carousel"), mouse.leave, function(){
				var buttonPrev = Dom.byId("buttonPrevSpan");
				var buttonNext = Dom.byId("buttonNextSpan");

				buttonNext.setAttribute('style', 'display: none;');
				buttonPrev.setAttribute('style', 'display: none;');
			});
			
			On(Dom.byId("buttonNextSpan"), mouse.enter, function(){
				var buttonPrev = Dom.byId("buttonPrevSpan");
				var buttonNext = Dom.byId("buttonNextSpan");

				buttonNext.setAttribute('style', 'display: inline;');
				buttonPrev.setAttribute('style', 'display: inline;');

			});
			
			On(Dom.byId("buttonPrevSpan"), mouse.enter, function(){
				var buttonPrev = Dom.byId("buttonPrevSpan");
				var buttonNext = Dom.byId("buttonNextSpan");

				buttonNext.setAttribute('style', 'display: inline;');
				buttonPrev.setAttribute('style', 'display: inline;');

			});

		},
		
		clickEvents: function() {
			// set click events
			this.carouselScroller = Dom.byId("carouselScroll");
			this.boxMixin = {duration: 1000};
	       
	       var next = Query("span#buttonNextSpan")[0];
			// next click
			On(next, "click", lang.hitch(this, function(event) {
	    	   var diff = Dom.byId('carousel').offsetWidth - Dom.byId("carouselScroll").offsetWidth;
	    	   if(this.newLeft>diff){
	    		   this.newLeft = this.newLeft - 200;
	    		   var style = this.carouselScroller.style;
	    		   var anim1 = coreFX.animateProperty({
	    			   node: this.carouselScroller,
	    			   duration: this.boxMixin.duration/2,
	    			   properties: {
	    				   left: {end: this.newLeft, unit:"px"}
	    			   }
	    		   });
	    		   animG = FX.chain([anim1]).play();
	    	   }
	    	   
	       }));
			
		   var prev = Query("span#buttonPrevSpan")[0];
		   // prev click
	       On(prev, "click", lang.hitch(this, function(event) {
	    	   if(this.newLeft < 0){
	    		   this.newLeft = this.newLeft + 200;
	    		   var style = this.carouselScroller.style;
	    		   var anim1 = coreFX.animateProperty({
	    			   node: this.carouselScroller,
	    			   duration: this.boxMixin.duration/2,
	    			   properties: {
	    				   left: {end: this.newLeft, unit:"px"}
	    			   }
	    		   });
	    		   animG = FX.chain([anim1]).play();
	    	   }
	       }));
			
		},
		
		initDo: function(node) {
			// request pictures
			request.ajax({
				query: {
					cid:	this.uri_object.cid,
					mod:	'ajax',
					fct:	'picture',
					action:	'getMaterialPictures'
				},
				data: {
					item_id: this.item_id,
					module: this.module
				}
			}).then(
				lang.hitch(this, function(response) {
					// create html for gallery
					if(response.data.length > 0){
					
						// carousel
						var carouselNode = DomConstruct.create("div", {
							id:	"carousel"
						}, node, "last");
						
							// carouselScroll
							var carouselScrollNode = DomConstruct.create("div", {
								id:	"carouselScroll",
								style: "width:200px;"
							}, carouselNode, "last");
						
						// foreach picture
						dojo.forEach(response.data, lang.hitch(this, function(data, index, arr) {
							this.imageCount = this.imageCount + 1;
							
							// carouselBox
							var carouselBoxNode = DomConstruct.create("div", {
								className:	"carouselBox"
							}, carouselScrollNode, "last");
								
								// carouselImages
								var carouselImageNode = DomConstruct.create("div", {
									className:	"carouselImages"
								}, carouselBoxNode, "last");
								
									var imageLink = DomConstruct.create("a", {
										href:		data.url.replace(/&amp;/g, "&"),
										className:	"lightbox_gallery_" + this.item_id
									}, carouselImageNode, "last");
										
										var imageNode = DomConstruct.create("img", {
											src:		data.url.replace(/&amp;/g, "&"),
											id:			"gallery_image_" + this.imageCount,
											className:	"gallery_image"
										}, imageLink, "last");
										
										// preload image
										this.createLoadingAnimation(carouselImageNode, "gallery_image_" + this.imageCount);
										this.preloadImageEvent(imageNode);
									
							
							// image title
							var carouselBoxNode = DomConstruct.create("div", {
								className:	"carouselImageTitle",
								innerHTML:	data.name.substring(0,20)
							}, carouselBoxNode, "last");
							
//							DomConstruct.create
							
						}));
							
						DomConstruct.create("span", {
							id:		"buttonPrevSpan"
						}, node, "last");
							
						DomConstruct.create("span", {
							id:		"buttonNextSpan"
						}, node, "last");
						
//						this.preloadEvents();
						this.clickEvents();
						this.hoverEvents();
						// set carousel scroller width
						this.carouselScroller.setAttribute('style', 'width:' + (this.imageCount * 200) + 'px !important;');
						
						// lightbox
						lightbox.addImageGroup(Query("a[class^='lightbox_gallery']"));
					} else {
						this.destroyLoadingAnimation();
					}
				})
				
				
			);
			
		}
		
		
	
	});
});

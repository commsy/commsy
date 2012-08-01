define([	"dojo/_base/declare",
        	"commsy/base",
        	"dojo/on",
        	"dojo/_base/lang",
        	"dojo/_base/fx",
        	"dojo/query",
        	"dojo/dom-class",
        	"dojo/dom-attr",
        	"dojo/dom-construct",
        	"dojo/dom-style"], function(declare, BaseClass, On, Lang, FX, Query, DomClass, DomAttr, DomConstruct, DomStyle) {
	return declare(BaseClass, {
		constructor: function(args) {
			args = args || {};
			declare.safeMixin(this, args);
		},
		
		setup: function(nodeList) {
			// get custom attribute data for all nodes
			dojo.forEach(nodeList, Lang.hitch(this, function(node, index, arr) {
				var customObject = this.getAttrAsObject(node, "data-custom");
				
				// register click
				On(node, "click", Lang.hitch(this, function(event) {
					// call function if exist
					if(this[customObject.action]) Lang.hitch(this, this[customObject.action](customObject));
				}));
			}));
		},
		
		addToClipboard: function(customObject) {
			var itemId = customObject.iid;
			
			// send ajax requets
			this.AJAXRequest("actions", "addToClipboard", { itemId: itemId }, Lang.hitch(this, function(response) {
				// item was added, update number of items in clipboard
				var ClipboardButtonNode = Query("a#tm_clipboard")[0];
				
				if(ClipboardButtonNode) {
					var spanNode = Query("span#tm_clipboard_copies")[0];
					
					if(!spanNode) {
						// create span
						spanNode = DomConstruct.create("span", {
							"id":		"tm_clipboard_copies",
							innerHTML:	0
						}, ClipboardButtonNode, "after");
					}
					
					// increase count
					DomAttr.set(spanNode, "innerHTML", parseInt(DomAttr.get(spanNode, "innerHTML")) + 1);
				}
			}));
		},
		
		versionMakeNew: function(customObject) {
			var itemId = customObject.iid;
			var versionID = customObject.vid;

			// send ajax requets
			this.AJAXRequest("actions", "versionMakeNew", { itemId: itemId, versionID: versionID }, Lang.hitch(this, function(response) {
				this.reload(itemId);
			}));
		},
		
		exportToWordpress: function(customObject) {
			var itemId = customObject.iid;

			// send ajax requets
			this.AJAXRequest("actions", "exportToWordpress", { itemId: itemId }, Lang.hitch(this, function(response) {
				this.reload(itemId);
			}));
		},
		
		exportToWiki: function(customObject) {
			var itemId = customObject.iid;

			// send ajax requets
			this.AJAXRequest("actions", "exportToWiki", { itemId: itemId }, Lang.hitch(this, function(response) {
				this.reload(itemId);
			}));
		}
		
		/*closeParticipation: function(customObject) {
			this.button_close_participation_room = new dijit.form.Button({
				label:		"Teilnahme beenden in diesem Raum",
				onClick:	Lang.hitch(this, function(event) {
					this.onPopupSubmit({
	                   part: "user_configuration",
	                   action: "close_participation_room",
	                });
					// destroy the dialog
					this.dialog.destroyRecursive();
				})
			});
			
			this.button_delete_participation_room = new dijit.form.Button({
				label:		"Teilnahme l&ouml;schen in diesem Raum",
				onClick:	Lang.hitch(this, function(event) {
					this.onPopupSubmit({
	                   part: "user_configuration",
	                   action: "delete_participation_room",
	                });
					// destroy the dialog
					this.dialog.destroyRecursive();
				})
			});
			
			this.button_close_participation_portal = new dijit.form.Button({
				label:		"Teilnahme beenden im Portal",
				onClick:	Lang.hitch(this, function(event) {
					this.onPopupSubmit({
	                   part: "user_configuration",
	                   action: "close_participation_portal",
	                });
					// destroy the dialog
					this.dialog.destroyRecursive();
				})
			});
			
			this.button_delete_participation_portal = new dijit.form.Button({
				label:		"Teilnahme l&ouml;schen im Portal",
				onClick:	Lang.hitch(this, function(event) {
					this.onPopupSubmit({
	                   part: "user_configuration",
	                   action: "delete_participation_portal",
	                });
					// destroy the dialog
					this.dialog.destroyRecursive();
				})
			});
			
			this.button_cancel = new dijit.form.Button({
				label:		"Abbrechen",
				onClick:	Lang.hitch(this, function(event) {
					// destroy the dialog
					this.dialog.destroyRecursive();
				})
			});

			this.dialog = new dijit.Dialog({
				title:		"Mitgliedschaft beenden",
				content: 	"Sie haben zwei M&ouml;glichkeiten:<br/>Wenn Sie <b>Teilnahme beenden in diesem Raum</b> w&auml;hlen, wird ihre Kennung f&uuml;r diesen Raum gesperrt." +
							"Sie haben dann keinen Zutritt mehr zu dem Raum Blog. Ihre Beitr&auml;ge bleiben aber erhalten. Falls Sie bestimmte Eintr&auml;ge l&ouml;schen wollen," +
							"tun Sie das bitte, bevor Sie ihre Teilnahme beenden. Ihr Zugang kann bei Bedarf wieder freigeschaltet werden.<br/><br/>" +
							"Wenn Sie <b>Teilnahme l&ouml;schen in diesem Raum</b> w&auml;hlen, werden s&auml;mtliche ihrer Beitr&auml;ge und ihre Kennung in dem Raum gel&ouml;scht." +
							"Sie k&ouml;nnen danach den Raum nicht mehr betreten. Achtung: Dies kann nicht r&uuml;ckg&auml;ngig gemacht werden.<br/><br/>" +
                            "Alternativ k&ouml;nnen Sie f&uuml;r das gesamte CommSy Portal und alle R&auml;ume:" +
                            "<ul><li><b>Teilnahme beenden</b> oder</li><li><b>Teilnahme l&ouml;schen</b>."
			});
			console.log(this.dialog.containerNode);
			dojo.place(this.button_close_participation_room.domNode, this.dialog.containerNode);
			dojo.place(this.button_delete_participation_room.domNode, this.dialog.containerNode);
			dojo.place(this.button_close_participation_portal.domNode, this.dialog.containerNode);
			dojo.place(this.button_delete_participation_portal.domNode, this.dialog.containerNode);
			dojo.place(this.button_cancel.domNode, this.dialog.containerNode);
			this.dialog.show();
		}*/
		
	});
});
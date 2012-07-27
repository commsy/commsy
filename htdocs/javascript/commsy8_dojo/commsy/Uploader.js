define([	"dojo/_base/declare",
        	"commsy/base",
        	"dojo/_base/lang",
        	"dojox/embed/Flash",
        	"dojox/form/Uploader",
        	"dijit/ProgressBar",
        	"dojox/form/uploader/FileList",
        	"dojo/dom-construct",
        	"dojo/dom-attr",
        	"dojo/on",
        	"dijit/form/Button",
        	"dojo/query",
        	"dojo/_base/connect"], function(declare, BaseClass, Lang, Flash, Uploader, ProgressBar, FileList, DomConstruct, DomAttr, On, Button, Query, connect) {
	return declare(BaseClass, {
		uploader:		null,
		loadingImgNode:	null,
		//fileList: null,
		progressbar:	null,
		single:			false,
		callback:		null,

		constructor: function(options) {
			options = options || {};
			declare.safeMixin(this, options);
		},

		setCallback: function(func) {
			this.callback = func;
		},

		setup: function(uploaderNode) {
			/*
			if(Flash.available) {
				dojo.require("dojox/form/uploader/plugins/Flash");
			} else {*/
				dojo.require("dojox/form/uploader/plugins/IFrame");
			//}

			dojo.ready(Lang.hitch(this, function() {
				this.uploader = new dojox.form.Uploader({
					multiple:		!this.single,
					uploadOnSelect: false,
					"class":		"fileSelector",

					//force:			"flash",
					//force:			"iframe",
					isDebug:		true,

					url:			"commsy.php?cid=" + this.uri_object.cid + "&mod=ajax&fct=upload&action=upload"
				}, Query("input.fileSelector", uploaderNode)[0]);

				// setup event handler
				On(this.uploader, "begin", Lang.hitch(this, function(fileArray) {
					this.onUploadBegin(fileArray);
				}));

				On(this.uploader, "complete", Lang.hitch(this, function(response) {
					this.onUploadComplete(response);
				}));

				On(this.uploader, "error", Lang.hitch(this, function(error) {
					this.onUploadError(error);
				}));

				On(this.uploader, "progress", Lang.hitch(this, function(statusObject) {
					this.onProgress(statusObject);
				}));

				On(this.uploader, "change", Lang.hitch(this, function(fileArray) {
					// prepare data to send
					var targetRubric = this.uri_object.mod;
					if(targetRubric === "todo") {
						targetRubric = "step";
					} else if(targetRubric === "discussion") {
						targetRubric = "discarticle";
					}

					var send = {
						file_upload_rubric:		targetRubric
					};

					this.uploader.upload(send);
					
					// setup loading
					this.setupLoading();
				}));

				this.uploader.startup();
			}));
		},

		onUploadBegin: function(fileArray) {
			// seems not to be called by iframe method
			this.progressbar = new ProgressBar({
				value:		"0%",
				className:	"ui-progressbar"
			});

			this.progressbar.placeAt(Query("div.fileList")[0]);
		},

		onUploadComplete: function(data) {
			// remove loading
			this.destroyLoading();
			
			if(this.callback) {
				this.callback(data);
			} else {
				var fileListNode = Query("div#files_finished")[0];

				if(!data.length) data = [data];

				dojo.forEach(data, Lang.hitch(this, function(file, index, arr) {
					// add file to file finished
					DomConstruct.create("input", {
						type:		"checkbox",
						checked:	"checked",
						name:		"form_data[file_" + index + "]",
						value:		file.file_id
					}, fileListNode, "last");

					DomAttr.set(fileListNode, "innerHTML", DomAttr.get(fileListNode, "innerHTML") + file.name + "</br>");
				}));
			}
		},

		onUploadError: function(error) {
			console.log(error);
		},

		onProgress: function(statusObject) {
			// update progress bar
			this.progressbar.set("value", statusObject.percent);

			if(statusObject.percent === "100%") this.progressbar.destroy(false);
		},

		destroy: function() {
			this.uploader.destroy(false);
		}
	});
});
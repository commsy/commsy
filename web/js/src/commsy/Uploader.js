define([	"dojo/_base/declare",
        	"commsy/base",
        	"dojo/_base/lang",
        	"dojo/_base/array",
        	"dojox/embed/Flash",
        	"dojox/form/Uploader",
        	"dijit/ProgressBar",
        	"dijit/Dialog",
        	"dojox/form/uploader/FileList",
        	"dojo/dom-construct",
        	"dojox/timing",
        	"dojo/dom-attr",
        	"dijit/Tooltip",
        	"dojo/i18n!./nls/tooltipErrors",
        	"dojo/on",
        	"dijit/form/Button",
        	"dojo/query",
        	"dojo/_base/connect",
        	"dojo/has",
        	"dojo/sniff",
        	"commsy/sniff"], function(declare, BaseClass, Lang, arrayUtil, Flash, Uploader, ProgressBar, Dialog, FileList, DomConstruct, Timing, DomAttr, Tooltip, ErrorTranslations, On, Button, Query, connect, has) {
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
			
			var options = {
				/* multiple false seems to be bugy */
				multiple:		true,//!this.single,
				uploadOnSelect: false,
				"class":		"fileSelector",
				
				isDebug:		false,

				url:			"commsy.php?cid=" + this.uri_object.cid + "&mod=ajax&fct=upload&action=upload"
			};
			
			if(has("isWindows") && has("safari")){
				options.multiple = false;
				options.force = "iframe";
			}

			dojo.ready(Lang.hitch(this, function() {
				this.uploader = new dojox.form.Uploader(options, Query("input.fileSelector", uploaderNode)[0]);

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
					// check file size
					var fileSizeLimit = this.from_php.environment.max_upload_size;
					var isTooLarge = arrayUtil.some(fileArray, function(item) {
						return item.size > fileSizeLimit;
					});

					if (isTooLarge) {
						this.uploader.reset();

						var myDialog = new dijit.Dialog({
						    title: "Uploadgrenze",
						    content: ErrorTranslations.uploadLimit,
						    style: "width: 300px"
						});
						myDialog.show();
						
						return false;
					}

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
			
			// check if something went wrong
			if ( data.file === null )
			{
				if( data.file === null && data.virus === null) {
					Tooltip.show(ErrorTranslations.upload, this.uploader.domNode);
				} else if( data.virus !== null ) {
					Tooltip.show(ErrorTranslations.virus_found + '</br></br>' + data.name, this.uploader.domNode);
				}
				var timer = new Timing.Timer(3000);
				timer.onTick = Lang.hitch(this, function(event)
				{
					Tooltip.hide(this.uploader.domNode);
					timer.stop();
				});
				timer.start();
			}
			else
			{
				if(this.callback) {
					this.callback(data);
				} else {
					var fileListNode = Query("div#files_finished")[0];
					var showError = false;
					var virusFileNames = [];

					if(!data.length) data = [data];

					dojo.forEach(data, Lang.hitch(this, function(file, index, arr) {

						if(file.virus) {
							showError = true;
							virusFileNames[virusFileNames.length] = file.name;
						} else {
							// add file to file finished
							DomConstruct.create("input", {
								type:		"checkbox",
								checked:	"checked",
								name:		"form_data[file_" + index + "]",
								value:		file.file_id
							}, fileListNode, "last");

							DomAttr.set(fileListNode, "innerHTML", DomAttr.get(fileListNode, "innerHTML") + file.name + "</br>");

						}
					}));

					if(showError) {
						var fileNameOutput = '';
						dojo.forEach(virusFileNames, Lang.hitch(this, function(fileName, index, arr) {
							fileNameOutput += fileName + '</br>';
						}));
						// show error message
						Tooltip.show(ErrorTranslations.virus_found + '</br></br>' + fileNameOutput, this.uploader.domNode);
						var timer = new Timing.Timer(4000);
						timer.onTick = Lang.hitch(this, function(event)
						{
							Tooltip.hide(this.uploader.domNode);
							timer.stop();
						});
						timer.start();
					}


				}
			}
		},

		onUploadError: function(error) {
		},

		onProgress: function(statusObject) {
			// update progress bar, if not destroyed yet
			if ( this.progressbar )
			{
				this.progressbar.set("value", statusObject.percent);
				
				// destroy on complete
				if(statusObject.percent === "100%") {
					this.progressbar.destroy(false);
					this.progressbar = null;
				}
			}
		},

		destroy: function() {
			this.uploader.destroy(false);
		}
	});
});
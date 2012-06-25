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
		uploader: null,
		//fileList: null,
		progressbar: null,
		
		constructor: function(options) {
			declare.safeMixin(this, options);
		},
		
		setup: function(uploaderNode) {
			if(Flash.available) {
				dojo.require("dojox/form/uploader/plugins/Flash");
			} else {
				dojo.require("dojox/form/uploader/plugins/IFrame");
			}
			
			dojo.ready(Lang.hitch(this, function() {
				this.uploader = new dojox.form.Uploader({
					multiple:		true,
					uploadOnSelect: false,
					"class":		"fileSelector",
					//force:			"flash",
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
				}));
				
				
				
				/*
				On(Query("input.upload", uploaderNode)[0], "click", Lang.hitch(this, function() {
					this.onUploadButtonClick(send);
				}));
				
				this.fileList = new dojox.form.uploader.FileList({
					uploader:		this.uploader
				}, Query("div.fileList", uploaderNode)[0]);
				
				this.fileList.startup();
				*/
				
				/*
				this.uploader._connectButton = Lang.hitch(this.uploader, function() {
					this._cons.push(connect.connect(this.inputNode, "change", this, function(evt){
						if(this._files) {
							this._files[1] = this.inputNode.files;
							this._files.length++;
						} else {
							this._files = this.inputNode.files;
						}
						console.log(this._files);
						
						//this._files = this.inputNode.files;
						this.onChange(this.getFileList(evt));
						if(!this.supports("multiple") && this.multiple) this._createInput();
					}));
			
					if(this.tabIndex > -1){
						this.inputNode.tabIndex = this.tabIndex;
			
						this._cons.push(connect.connect(this.inputNode, "focus", this, function(){
							this.titleNode.style.outline= "1px dashed #ccc";
						}));
						this._cons.push(connect.connect(this.inputNode, "blur", this, function(){
							this.titleNode.style.outline = "";
						}));
					}
				});
				*/
				
				this.uploader.startup();
			}));
		},
		
		onUploadBegin: function(fileArray) {
			this.progressbar = new ProgressBar({
				value:		"0%",
				"class":	"ui-progressbar"
			});
			
			this.progressbar.placeAt(Query("div.fileList")[0]);
		},
		
		onUploadComplete: function(data) {
			var fileListNode = Query("div#files_finished")[0];
			
			if(!data.length) data = [data];
			
			data.forEach(Lang.hitch(this, function(file, index, arr) {
				// add file to file finished
				DomConstruct.create("input", {
					type:		"checkbox",
					checked:	"checked",
					name:		"form_data[file_" + index + "]",
					value:		file.file_id
				}, fileListNode, "last");
				
				DomAttr.set(fileListNode, "innerHTML", DomAttr.get(fileListNode, "innerHTML") + file.name + "</br>");
			}));
			
			// remove progressbar
			this.progressbar.destroy();
			
			console.log(data);
			console.log("complete");
		},
		
		onUploadError: function(error) {
			console.log(error);
		},
		/*
		onUploadButtonClick: function(postData) {
			console.log("click");
			//console.log(this.uploader.getFileList());
			this.uploader.upload(postData);
		},*/
		
		onProgress: function(statusObject) {
			console.log(statusObject);
			
			// update progress bar
			this.progressbar.set("value", statusObject.percent + "%");
		},
		
		destroy: function() {
			this.uploader.destroyRecursive(false);
			//this.fileList.destroyRecursive(false);
		}
	});
});
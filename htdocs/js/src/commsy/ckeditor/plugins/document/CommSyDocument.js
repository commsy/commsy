/*
Copyright (c) 2003-2012, CKSource - Frederico Knabben. All rights reserved.
For licensing, see LICENSE.html or http://ckeditor.com/license
*/

CKEDITOR.plugins.add( "CommSyDocument",
{
	init: function( editor )
	{
		editor.addCommand( "CommSyDocument", new CKEDITOR.dialogCommand( "CommSyDocument" ) );
		
		editor.ui.addButton( "CommSyDocument",
		{
			label:		"Dokumente",
			command:	"CommSyDocument",
			icon:		"../../src/commsy/ckeditor/plugins/document/images/icon.png"
		} );
		
		CKEDITOR.dialog.add( 'CommSyDocument', function ( instance )
				{
					var audio;
					
					var SelectBoxItems = new Array(
							new Array( '<Bitte Dokumententyp auswählen>', 'null'),
					        new Array( 'Slideshare', 'slideshare' ),
					        new Array( 'Onyx', 'onyx' )
					);
					
					// parse filenames from edit dialog
					var files = document.getElementsByName('file_name');
					
					fileItems = new Array (
							new Array( '<Auswahl>' , 'null')
					);
					
					// fill select with filenames
					var i,fileId;
					for(i = 0; i < files.length; i++){
						fileId = document.getElementsByName('form_data[file_' + i + ']');
						fileItems.push(new Array(files[i].innerHTML, fileId[0].value, files[i].innerHTML.substr(files[i].innerHTML.lastIndexOf('.')+1, 3)));
					}
					
					var floatItems = new Array (
							new Array ('<nichts>','null'),
							new Array ('Links','left'),
							new Array ('Rechts','right')
					);
					
					return {
						title : 'Dokument-Eigenschaften',
						minWidth : 500,
						minHeight : 200,
						contents :
							[{
								id : 'documentTab',
								label: 'Document',
								expand : true,
								elements :
									[{
										type: 'select',
										id: 'selectbox',
										style: 'width=100%',
										label: 'Dokument Type',
										items: SelectBoxItems,
										'default' : 'null',
										onLoad : function () 
										{
											var dialog = this.getDialog();
											var startAt = dialog.getContentElement( 'documentTab', 'startAt');
											var uploadButton = dialog.getContentElement( 'documentTab', 'uploadButton');
											var upload = dialog.getContentElement( 'documentTab', 'upload');
											
											uploadButton.disable();
											upload.disable();
											
											startAt.enable();
										},
										onChange : function ()
										{
											// show input if onyx is selected
											var dialog = this.getDialog();
											var textInput = dialog.getContentElement('documentTab', 'linkText');
											var fileSelect = dialog.getContentElement( 'documentTab', 'fileselect' );
											var startAt = dialog.getContentElement( 'documentTab', 'startAt');
											var uploadButton = dialog.getContentElement( 'documentTab', 'uploadButton');
											var upload = dialog.getContentElement( 'documentTab', 'upload');
											
											if(this.getValue() == 'onyx'){
												textInput.enable();
												fileSelect.enable();
												startAt.disable();
												uploadButton.enable();
												upload.enable();
												
												// select only zip files for onyx
												var j;
												fileSelect.clear();
												fileSelect.add('<Auswahl>', 'null');
												for(j = 0; j < fileItems.length; j++) {
													if(fileItems[j][2] == 'zip'){
														fileSelect.add(fileSelect.items[j][0],fileSelect.items[j][1]);
													}
												}
											} else {
												startAt.enable();
												textInput.disable();
												fileSelect.disable();
												uploadButton.disable();
												upload.disable();
											}
										}
									},
									{
										type: 'hbox',
										widths: ['50%','50%'],
										children:
										[
										 	{
												type : 'select',
												id: 'fileselect',
												label: 'Angehängte Datei auswählen',
												items : fileItems,
												onLoad : function ()
												{
													this.disable();
												},
												onChange : function () 
												{
													// disable textInput if file is selected
													var dialog = this.getDialog();
													var inputUrl = dialog.getContentElement( 'documentTab', 'documentUrl' );
													if(this.getValue() == 'null'){
														inputUrl.enable();
														inputUrl.setValue('');
														inputUrl.focus();
													} else {
														inputUrl.disable();
														// set file url in textInput
														var cid = getUrlParam('cid');
														var mod = getUrlParam('mod');
														var iid = getUrlParam('iid');
														
														var input = this.getInputElement().$;
														
														if(dialog.getContentElement('documentTab', 'selectbox').getValue() == 'onyx') {
															fileUrl = 'commsy.php?cid=' + cid + '&mod=onyx&fct=showqti&iid=' + this.getValue();
														} else {
															fileUrl = 'commsy.php/' + input.options[input.selectedIndex].text + '?cid=' + cid + '&mod=' + mod + '&fct=getfile&iid=' + this.getValue();
														}
														
														encodeFileUrl = encodeURI(fileUrl);
		//												alert(encodeFileUrl);
														inputUrl.setValue(encodeFileUrl);
													}
												}
											},
											{
												type: 'vbox',
												children:
												[
													{
													    type: 'file',
													    id: 'upload',
													    label: 'neue Datei hochladen',
													    style: 'height:40px',
													    size: 38
													},
													{
													    type: 'fileButton',
													    id: 'uploadButton',
													    filebrowser: 'documentTab:documentUrl',
													    label: 'Hochladen',
													    'for': [ 'documentTab', 'upload' ],
													}
												]
											},	
										]
									},
									{
										id : 'linkText',
										type : 'text',
										label : 'Text',
										onLoad : function () 
										{
											this.disable();
										}
									},
									{
										type : 'hbox',
										widths : [ '70%', '15%', '15%' ],
										children :
										[
											{
												id : 'documentUrl',
												type : 'text',
												label : '"Shortcode für Wordpress" von Slideshare unter "Share" verwenden [slideshare id"..."]',
												validate : function ()
												{
													if ( this.isEnabled() )
													{
														if ( !this.getValue() )
														{
															alert( 'noCode' );
															return false;
														}
													}
												}
											}
										]
									},
									{
										type : 'hbox',
										widths : ['20%', '20%', '20%', '20%', '20%'],
										children :
										[
										 	{
										 		type : 'text',
												id : 'documentWidth',
												width : '60px',
												label : 'Breite',
												'default' : '640',
												validate : function ()
												{
													if ( this.getValue() )
													{
														var width = parseInt ( this.getValue() ) || 0;

														if ( width === 0 )
														{
															alert( 'invalidWidth' );
															return false;	
														}
													}
													else {
														alert( 'noWidth' );
														return false;
													}
												}
											},
											{
												type : 'text',
												id : 'documentHeight',
												width : '60px',
												label : 'Höhe',
												'default' : '360',
												validate : function ()
												{
													if ( this.getValue() )
													{
														var height = parseInt ( this.getValue() ) || 0;

														if ( height === 0 )
														{
															alert( 'invalidHeight' );
															return false;	
														}
													}
													else {
														alert( 'noHeight' );
														return false;
													}
												}
										 	},
										 	{
										 		type : 'select',
												id : 'float',
												label : 'Ausrichtung',
												items : floatItems
										 	},
										 	{
										 		type : 'text',
												id : 'border',
												width : '60px',
												label : 'Rahmen',
												'default' : '',
											},
											{
												type : 'text',
												id : 'marginH',
												width : '60px',
												label : 'H-Abstand',
												'default' : '',
											},
											{
												type : 'text',
												id : 'marginV',
												width : '60px',
												label : 'V-Abstand',
												'default' : '',
											},
										]
									},
									{
										type: 'hbox',
										widths : [ '50px', '50px', '50px' ],
//										style: 'margin-top:px',
										children: 
										[
										 	{
												type : 'text',
												id : 'startAt',
												width : '60px',
												label : 'Start mit Folie',
												'default' : '',
											}

										]
									}
								]
							},
//							{
//								id:	'tab2',
//								label: 'internal Video',
//								title: 'blaaaa',
//								elements: [{
//									type: 'text',
//									label: 'testststst',
//									'default': 'helloworld!'
//								}]
//							}
						],
						onOk: function()
						{
							var content = '';
							var float = this.getValueOf( 'documentTab', 'float');
							
							var style = '',
							tempStyle = '',
							borderWidth = this.getValueOf( 'documentTab', 'border' ),
							horizontalMargin = this.getValueOf( 'documentTab', 'marginH'),
							verticalMargin = this.getValueOf( 'documentTab', 'marginV');
							
							style = 'style="';

							if ( borderWidth !== "" ) {
								tempStyle += 'border-style: solid; border-width:' + borderWidth + 'px;';
							}

							if ( horizontalMargin != "" ) {
								tempStyle += 'margin-top:' + horizontalMargin + 'px;';
								tempStyle += 'margin-bottom:' + horizontalMargin + 'px;';
							}
							
							if ( verticalMargin !== "" ) {
								tempStyle += 'margin-left:' + verticalMargin + 'px;';
								tempStyle += 'margin-right:' + verticalMargin + 'px;';
							}
							
							if(float != 'null' && float == 'right'){
								tempStyle += 'float:right;';
							} else if (float != 'null' && float == 'left') {
								tempStyle += 'float:left;';
							}
							style += tempStyle;
							style += '"';
							
							if(this.getValueOf('documentTab', 'selectbox') == 'slideshare'){
								
//								content = 'http://lecture2go.uni-hamburg.de/';
								
								var width = this.getValueOf( 'documentTab', 'documentWidth' );
								var height = this.getValueOf( 'documentTab', 'documentHeight' );
								var documentUrl = this.getValueOf( 'documentTab', 'documentUrl');
								var startAt = this.getValueOf( 'documentTab', 'startAt');
								var floatValue = '';
								var param = '';
								
								if (startAt !== "") {
									param += '&startSlide='+startAt;
								}
								
								// wordpress shortcode regex
								var wp_regex = /\[slideshare id=\d*&doc=(.*)]/,
									match,
									docId;
								
								if(documentUrl.match(wp_regex)){
									match = documentUrl.match(wp_regex);
									docId = match[1];
								}
								
//								content += '<object width="' + width + '" height="' + height + '>';
//								content += '<param value="http://static.slideshare.net/swf/ssplayer2.swf?doc=' + docId + '" name="movie">';
//								content += '<param value="true" name="allowFullScreen">';
//								content += '<param value="always" name="allowScriptAccess">';
								content += '<embed ' + style + ' width="' + width + '" height="' + height + '" wmode="opaque" allowfullscreen="true" allowscriptaccess="always" type="application/x-shockwave-flash" src="http://static.slideshare.net/swf/ssplayer2.swf?doc=' + docId + '&amp;rel=0' + param +'">';
//								content += '</object>';
								
								
							} else if(this.getValueOf('documentTab', 'selectbox') == 'onyx') {
								
								var cid = getUrlParam('cid');
								
								var dialog = this;
								var linkText = this.getValueOf('documentTab','linkText');
								var link = this.getValueOf('documentTab', 'documentUrl');
								
					            var a = editor.document.createElement( 'a' );
					            a.setAttribute( 'href', link);
					            
//					            a.setAttribute( 'href', 'commsy.php?cid=' + cid + '&mod=onyx&fct=showqti&iid=' + file_id + '');
					            a.setAttribute('target', 'help');
					            a.setAttribute('style', tempStyle);
					            a.setText( linkText );


					            editor.insertElement( a );
								
							}
							

							var instance = this.getParentEditor();
							instance.insertHtml( content );
						}
					};
				});
		
	}
} );

function getUrlParam( param )
{
	param = param.replace(/[\[]/,"\\\[").replace(/[\]]/,"\\\]");

	var regexS = "[\\?&]"+param+"=([^&#]*)";
	var regex = new RegExp( regexS );
	var results = regex.exec( window.location.href );

	if ( results == null )
		return "";
	else
		return results[1];
}

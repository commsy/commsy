/*
Copyright (c) 2003-2012, CKSource - Frederico Knabben. All rights reserved.
For licensing, see LICENSE.html or http://ckeditor.com/license
*/

CKEDITOR.plugins.add( "CommSyAudio",
{
	init: function( editor )
	{
		editor.addCommand( "CommSyAudio", new CKEDITOR.dialogCommand( "CommSyAudio" ) );
		
		editor.ui.addButton( "CommSyAudio",
		{
			label:		"Audio",
			command:	"CommSyAudio",
			icon:		"../../src/commsy/ckeditor/plugins/audio/images/icon.png"
		} );
		
		CKEDITOR.dialog.add( 'CommSyAudio', function ( instance )
				{
					var audio;
					// parse filenames from edit dialog
					var files = document.getElementsByName('file_name');
					var urlDecodeFlag = true;
					
					fileItems = new Array (
							new Array( '<Auswahl>' , 'null')
					);
					
					// fill select with filenames
					var i,fileId;
					for(i = 0; i < files.length; i++){
						fileId = document.getElementsByName('form_data[file_' + i + ']');
						fileItems.push(new Array(files[i].innerHTML, fileId[0].value, files[i].innerHTML.substr(files[i].innerHTML.lastIndexOf('.')+1, 3)));
					}
					
					var SelectBoxItems = new Array(
							new Array( '<Bitte Audiotyp auswählen>', 'null'),
					        new Array( 'MediaPlayer (mp3)', 'mediaplayer' ),
					        new Array( 'wmaPlayer (wma)', 'wmaplayer' )
					);
					
					return {
						title : 'Audio-Eigenschaften',
						minWidth : 500,
						minHeight : 200,
						contents :
							[{
								id : 'audioTab',
								label: 'Audio',
								expand : true,
								elements :
									[{
										type: 'select',
										id: 'selectbox',
										style: 'width=100%',
										label: 'Audiotyp',
										items: SelectBoxItems,
										'default' : 'null',
										onLoad: function ()
										{
											var dialog = this.getDialog();
											var fileSelect = dialog.getContentElement( 'audioTab' , 'fileselect' );
											var j;
											fileSelect.clear();
											fileSelect.add('<Auswahl>', 'null');
											for(j = 0; j < fileItems.length; j++) {
												if(fileItems[j][2] == 'mp3'){
													fileSelect.add(fileSelect.items[j][0],fileSelect.items[j][1]);
												}
											}
										},
										onChange : function ()
										{
											var dialog = this.getDialog();
											var audioUrl = dialog.getContentElement( 'audioTab', 'audioUrl' );
											var fileSelect = dialog.getContentElement( 'audioTab', 'fileselect' );
											audioUrl.enable();
											
											if(this.getValue() == 'mediaplayer'){
												var j;
												fileSelect.clear();
												fileSelect.add('<Auswahl>', 'null');
												for(j = 0; j < fileItems.length; j++) {
													if(fileItems[j][2] == 'mp3'){
														fileSelect.add(fileSelect.items[j][0],fileSelect.items[j][1]);
													}
												}
											} else if(this.getValue() == 'wmaplayer') {
												var j;
												fileSelect.clear();
												fileSelect.add('<Auswahl>', 'null');
												for(j = 0; j < fileItems.length; j++) {
													if(fileItems[j][2] == 'wma'){
														fileSelect.add(fileSelect.items[j][0],fileSelect.items[j][1]);
													}
												}
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
												onChange : function () 
												{
													// disable textInput if file is selected
													var dialog = this.getDialog();
													var inputUrl = dialog.getContentElement( 'audioTab', 'audioUrl' );
													if(this.getValue() == 'null'){
														inputUrl.enable();
														inputUrl.setValue('');
//														urlDecodeFlag = true;
													} else {
														inputUrl.disable();
														// set file url in textInput
														var cid = getUrlParam('cid');
														var mod = getUrlParam('mod');
														var iid = getUrlParam('iid');
														
														var input = this.getInputElement().$;
														
														// build url for embedding
														fileUrl = 'commsy.php/' + input.options[input.selectedIndex].text + '?cid=' + cid + '&mod=' + mod + '&fct=getfile&iid=' + this.getValue();
														
//														encodeFileUrl = encodeURIComponent(fileUrl);
//															alert(encodeFileUrl);
														inputUrl.setValue(fileUrl);
//														urlDecodeFlag = false;
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
													    filebrowser: 'audioTab:audioUrl',
													    label: 'Hochladen',
													    'for': [ 'audioTab', 'upload' ],
													    onClick : function () 
													    {
													    	var dialog = this.getDialog();
															var fileSelect = dialog.getContentElement( 'audioTab' , 'selectbox' );
															if(fileSelect.getValue() == 'mediaplayer'){
																
															} else if (fileSelect.getValue() == 'wmaplayer') {
																
															} else {
																alert('Dateiupload nicht möglich. Bitte wählen Sie einen entsprechenden Audiotyp.');
														    	return false;
															}
													    	
													    }
													}
												]
											},
										]
									},
									{
										type : 'hbox',
										widths : [ '70%' ],
										children :
										[
											{
												id : 'audioUrl',
												type : 'text',
												label : 'Aus URL einfügen',
												onLoad : function ()
												{
													this.disable();
												},
												validate : function ()
												{
													if ( this.isEnabled() )
													{
														if ( !this.getValue() )
														{
															alert( 'Bitte eine URL eingeben' );
															return false;
														}
													}
												}
											}
										]
									},
									{
										id : 'autostart',
										type : 'checkbox',
										'default' : false,
										label : 'Autostart'
									},
									{
										type : 'hbox',
										widths : ['20%','20%','20%','20%','20%'],
										style: 'margin-top:20px;',
										children : 
										[
										 	{
										 		type : 'text',
												id : 'audioWidth',
												width : '60px',
												label : 'Breite',
												'default' : '320',
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
												id : 'audioHeight',
												width : '60px',
												label : 'Höhe',
												'default' : '20',
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
												items : new Array (
															new Array ('<nichts>','null'),
															new Array ('Links','left'),
															new Array ('Rechts','right')
														)
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
									}
								]
							},
//							{
//								id:	'tab2',
//								label: 'internal Video',
//								title: 'blaaaa'
//							}
						],
						onOk: function()
						{
							
							var type = this.getValueOf( 'audioTab', 'selectbox');
							if( type == 'null') {
								alert('Bitte einen Audiotyp auswählen!');
								return false;
							} else {
								var content = '';
								var width = this.getValueOf( 'audioTab', 'audioWidth' );
								var height = this.getValueOf( 'audioTab', 'audioHeight' );
								var audioUrl = this.getValueOf( 'audioTab', 'audioUrl');
								var autostart = this.getValueOf( 'audioTab', 'autostart');
								var float = this.getValueOf( 'audioTab', 'float');
								
								
								
								var floatValue = '';
								
								var style,
								borderWidth = this.getValueOf( 'audioTab', 'border' ),
								horizontalMargin = this.getValueOf( 'audioTab', 'marginH'),
								verticalMargin = this.getValueOf( 'audioTab', 'marginV');
								
								style = 'style="';
								if ( borderWidth !== '' ) {
									style += 'border-style: solid; border-width:' + borderWidth + 'px;';
								}
								
								if ( horizontalMargin != null ) {
									style += 'margin-top:' + horizontalMargin + 'px;';
									style += 'margin-bottom:' + horizontalMargin + 'px;';
								}
								
								if ( verticalMargin != null ) {
									style += 'margin-left:' + verticalMargin + 'px;';
									style += 'margin-right:' + verticalMargin + 'px;';
								}
								
								if(float != 'null' && float == 'right'){
									style += 'float:right;';
								} else if (float != 'null' && float == 'left') {
									style += 'float:left;';
								}
								style += '"';
								
								if(this.getValueOf('audioTab', 'selectbox') == 'mediaplayer'){
									
//									if(urlDecodeFlag){
//										audioUrl = encodeURI(audioUrl);
//									}
									audioUrl = encodeURIComponent(audioUrl);
									content += '<object data="mediaplayer.swf?file=' + audioUrl + '&type=mp3&showstop=true&showdigits=true&shownavigation=true" type="application/x-shockwave-flash" width="' + width + '" height="' + height + '" ' + style + ' commsytype="audio" wmode="opaque">';
//									content += '<param name="movie" value="mediaplayer.swf?file="' + audioUrl + '&type=mp3">';
									content += '<param value="sameDomain" name="allowScriptAccess">';
									content += '<param value="internal" name="allowNetworking">';
									if(this.getValueOf('audioTab', 'autostart')){
										content += '<param value="' + autostart + '" name="autoStart">';
									}
									content += '<embed src="' + audioUrl + '" width="' + width + '" height="' + height + '" allowscriptaccess="always" allowfullscreen="true"></embed>';
									content += '</object>';
									
//									if(this.getValueOf('audioTab', 'autostart')){
//										url = '&amp;autostart=true';
//									} else {
//										url = '&amp;autostart=false';
//									}
//									
//									
//									content += '<embed width="' + width + '" height="20" flashvars="file=' + audioUrl + ''+url+'" wmode="opaque" quality="high" name="mpl" id="mpl" ' + style + ' src="mediaplayer.swf" type="application/x-shockwave-flash">';
									
//									alert(content);
								} else if(this.getValueOf('audioTab', 'selectbox') == 'wmaplayer'){
									//audioUrl = audioUrl + '&SID='+session_id;
									content += '<object width="' + width + '" type="application/x-oleobject" standby="Loading Microsoft Windows Media Player components..." codebase="http://activex.microsoft.com/activex/controls/mplayer/en/nsmp2inf.cab#Version=6,4,5,715" classid="CLSID:22d6f312-b0f6-11d0-94ab-0080c74c7e95" id="MediaPlayer18" ' + style + '>';
									content += '<param value="' + audioUrl + '" name="fileName">';
									if(this.getValueOf('audioTab', 'autostart')){
										content += '<param value="' + autostart + '" name="autoStart">';
									}
									content += '<param value="true" name="showControls">';
									content += '<param value="true" name="showStatusBar">';
									content += '<param value="opaque" name="wmode">';
									content += '<embed width="' + width + '" showstatusbar="1" showcontrols="1" autostart="false" wmode="opaque" name="MediaPlayer18" src="' + audioUrl + '" pluginspage="http://www.microsoft.com/Windows/MediaPlayer/" type="application/x-mplayer2">';
									content += '</object>';
									
								}
								
								var instance = this.getParentEditor();
								instance.insertHtml( content );
							}
							
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

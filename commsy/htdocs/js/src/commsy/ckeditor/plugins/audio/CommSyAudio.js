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
			label:		"CommSy Audio",
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
						fileItems.push(new Array(files[i].innerHTML, fileId[0].value));
					}
					
					var SelectBoxItems = new Array(
					        new Array( 'MediaPlayer (mp3)', 'mediaplayer' ),
					        new Array( 'wmaPlayer (wma)', 'wmaplayer' )
					);
					
					return {
						title : 'CommSy Audio',
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
										label: 'Audio Type',
										items: SelectBoxItems,
									},
									{
										type : 'select',
										id: 'fileselect',
										label: 'Dateiauswahl',
										items : fileItems,
										onChange : function () 
										{
											// disable textInput if file is selected
											var dialog = this.getDialog();
											var inputUrl = dialog.getContentElement( 'audioTab', 'audioUrl' );
											if(this.getValue() == 'null'){
												inputUrl.enable();
												inputUrl.setValue('');
												urlDecodeFlag = true;
											} else {
												inputUrl.disable();
												// set file url in textInput
												var cid = getUrlParam('cid');
												var mod = getUrlParam('mod');
												var iid = getUrlParam('iid');
												
												// build url for embedding
												fileUrl = 'commsy.php/' + this.getValue() + '?cid=' + cid + '&mod=' + mod + '&fct=getfile&iid=' + this.getValue();
												
												encodeFileUrl = encodeURIComponent(fileUrl);
//												alert(encodeFileUrl);
												inputUrl.setValue(encodeFileUrl);
												urlDecodeFlag = false;
											}
										}
									},
									{
										type : 'hbox',
										widths : [ '70%', '15%', '15%' ],
										children :
										[
											{
												id : 'audioUrl',
												type : 'text',
												label : 'Url',
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
											},
											{
												type : 'text',
												id : 'audioWidth',
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
												id : 'audioHeight',
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
										type : 'select',
										id : 'float',
										label : 'Ausrichtung',
										items : new Array (
													new Array ('<nichts>','null'),
													new Array ('Links','left'),
													new Array ('Rechts','right')
												)
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
							var content = '';
							var width = this.getValueOf( 'audioTab', 'audioWidth' );
							var height = this.getValueOf( 'audioTab', 'audioHeight' );
							var audioUrl = this.getValueOf( 'audioTab', 'audioUrl');
							var autostart = this.getValueOf( 'audioTab', 'autostart');
							var float = this.getValueOf( 'audioTab', 'float');
							
							var floatValue = '';
							
							if(float != 'null' && float == 'right'){
								floatValue += 'float:right;';
							} else if (float != 'null' && float == 'left') {
								floatValue += 'float:left;';
							} else {
								floatValue = '';
							}
							
							if(this.getValueOf('audioTab', 'selectbox') == 'mediaplayer'){
								
								if(urlDecodeFlag){
									audioUrl = encodeURI(audioUrl);
								}

								content += '<object data="mediaplayer.swf?file=' + audioUrl + '&type=mp3" type="application/x-shockwave-flash" width="' + width + '" height="' + height + '" style="' + floatValue + '">';
//								content += '<param name="movie" value="mediaplayer.swf?file="' + audioUrl + '&type=mp3">';
								content += '<param value="sameDomain" name="allowScriptAccess">';
								content += '<param value="internal" name="allowNetworking">';
								content += '<embed src="' + audioUrl + '" width="' + width + '" height="' + height + '" allowscriptaccess="always" allowfullscreen="true"></embed>';
								content += '</object>';
								
//								alert(content);
							} else if(this.getValueOf('audioTab', 'selectbox') == 'wmaplayer'){
								
								content += '<object width="' + width + '" type="application/x-oleobject" standby="Loading Microsoft Windows Media Player components..." codebase="http://activex.microsoft.com/activex/controls/mplayer/en/nsmp2inf.cab#Version=6,4,5,715" classid="CLSID:22d6f312-b0f6-11d0-94ab-0080c74c7e95" id="MediaPlayer18" style="' + floatValue + '">';
								content += '<param value="' + audioUrl + '" name="fileName">';
								content += '<param value="false" name="autoStart">';
								content += '<param value="true" name="showControls">';
								content += '<param value="true" name="showStatusBar">';
								content += '<param value="opaque" name="wmode">';
								content += '<embed width="' + width + '" showstatusbar="1" showcontrols="1" autostart="false" wmode="opaque" name="MediaPlayer18" src="' + audioUrl + '" pluginspage="http://www.microsoft.com/Windows/MediaPlayer/" type="application/x-mplayer2">';
								content += '</object>';
								
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

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
//									{
//										type : 'html',
//										html : 'test' + '<hr>'
//									},
									{
										type : 'hbox',
										widths : [ '70%', '15%', '15%' ],
										children :
										[
											{
												id : 'audioUrl',
												type : 'text',
												label : 'Url / Dateiname',
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

								content += '<object data="mediaplayer.swf?file=' + audioUrl + '&type=mp3" type="application/x-shockwave-flash" width="' + width + '" height="' + height + '" style="' + floatValue + '">';
//								content += '<param name="movie" value="mediaplayer.swf?file="' + audioUrl + '&type=mp3">';
								content += '<param value="sameDomain" name="allowScriptAccess">';
								content += '<param value="internal" name="allowNetworking">';
								content += '<embed src="' + audioUrl + '" width="' + width + '" height="' + height + '" allowscriptaccess="always" allowfullscreen="true"></embed>';
								content += '</object>';
								
//								alert(content);
							} else if(this.getValueOf('audioTab', 'selectbox') == 'wmaplayer'){
								
//								var url = 'https://', params = [], startSecs;
//								var width = this.getValueOf( 'audioTab', 'audioWidth' );
//								var height = this.getValueOf( 'audioTab', 'audioHeight' );

							}
							

							var instance = this.getParentEditor();
							instance.insertHtml( content );
						}
					};
				});
		
	}
} );

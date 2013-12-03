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
					        new Array( 'MediaPlayer', 'mediaplayer' ),
					        new Array( 'wmaPlayer', 'wmaplayer' ),
					        new Array( 'Podcampus', 'podcampus' )
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
														else{
//															video = ytVidId(this.getValue());

//															if ( this.getValue().length === 0 ||  video === false)
//															{
//																alert( 'invalid' );
//																return false;
//															}
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
								]
							},
							{
								id:	'tab2',
								label: 'internal Video',
								title: 'blaaaa',
								elements: [{
									type: 'text',
									label: 'testststst',
									'default': 'helloworld!'
								}]
							}
						],
						onOk: function()
						{
							var content = '';
							
							if(this.getValueOf('audioTab', 'selectbox') == 'mediaplayer'){
								
//								content = 'http://lecture2go.uni-hamburg.de/';
								
								var width = this.getValueOf( 'audioTab', 'audioWidth' );
								var height = this.getValueOf( 'audioTab', 'audioHeight' );
								var audioUrl = this.getValueOf( 'audioTab', 'audioUrl');
								
//								content += '<embed width="'+ width +'" height="'+ height +'" type="application/x-shockwave-flash" src="mediaplayer.swf" style="undefined" name="mpl" quality="high" wmode="opaque" flashvars="file='+ audioUrl +'&amp;autostart=true&amp;showstop=true&amp;type=mp3&amp;showdigits=true&amp;shownavigation=true" id="mpl">';
								//content += '<embed id="ply2" width="'+ width +'" height="'+ height +'" flashvars="autostart=false&image=http://lecture2go.uni-hamburg.de/logo/l2g-flash.jpg&bufferlength=2&streamer=rtmp://fms.rrz.uni-hamburg.de:80/vod&file='+ videoUrl +'&backcolor=FFFFFF&frontcolor=000000&lightcolor=000000&screencolor=FFFFFF&id=id1" wmode="opaque" allowscriptaccess="always" allowfullscreen="true" quality="high" bgcolor="FFFFFF" name="ply" style="undefined" src="http://lecture2go.uni-hamburg.de/jw5.0/player-licensed.swf" type="application/x-shockwave-flash">';
								
								content += '<object height="'+ height +'" width="'+ width +'" data="' + audioUrl +'">';
								content += '<param name="allowscriptaccess" value="always"></param>';
								content += '<embed src="' + audioUrl + '" type="audio/mpeg" width="' + width + '" height="' + height + '" allowscriptaccess="always" allowfullscreen="true"></embed>';
								content += '</object>';
								
//								content += '<audio controls>';
//								content += '<source src="' + audioUrl + '" type="audio/mpeg">';
//								content += '<embed height="' + height + '" width="' + width + '" src="' + audioUrl + '">';
//								content += '</audio>';
								
								
								
								alert(content);
							} else if(this.getValueOf('audioTab', 'selectbox') == 'wmaplayer'){
								
								var url = 'https://', params = [], startSecs;
								var width = this.getValueOf( 'audioTab', 'audioWidth' );
								var height = this.getValueOf( 'audioTab', 'audioHeight' );
	
								
								
							} else if(this.getValueOf('audioTab', 'selectbox') == 'podcampus'){
								
							}
							

							var instance = this.getParentEditor();
							instance.insertHtml( content );
						}
					};
				});
		
	}
} );

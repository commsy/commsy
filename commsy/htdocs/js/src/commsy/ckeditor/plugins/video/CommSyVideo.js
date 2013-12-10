/*
Copyright (c) 2003-2012, CKSource - Frederico Knabben. All rights reserved.
For licensing, see LICENSE.html or http://ckeditor.com/license
*/

CKEDITOR.plugins.add( "CommSyVideo",
{
	init: function( editor )
	{
		editor.addCommand( "CommSyVideo", new CKEDITOR.dialogCommand( "CommSyVideo" ) );
		
		editor.ui.addButton( "CommSyVideo",
		{
			label:		"CommSy Videos",
			command:	"CommSyVideo",
			icon:		"../../src/commsy/ckeditor/plugins/video/images/icon.png"
		} );
		
		CKEDITOR.dialog.add( 'CommSyVideo', function ( instance )
				{
					var video;
					
					var SelectBoxItems = new Array(
					        new Array( 'Youtube', 'youtube' ),
					        new Array( 'MediaPlayer', 'mediaplayer' ),
					        new Array( 'Quicktime', 'quicktime' ),
//					        new Array( 'GoogleVideo', 'googlevideo' ),
					        new Array( 'Lecture2Go', 'lecture2go' ),
					        new Array( 'Podcampus', 'podcampus' )
					);
					
					return {
						title : 'CommSy Video',
						minWidth : 500,
						minHeight : 200,
						contents :
							[{
								id : 'videoTab',
								label: 'Video',
								expand : true,
								elements :
									[{
										type: 'select',
										id: 'selectbox',
										style: 'width=100%',
										label: 'Video Type',
										items: SelectBoxItems,
										onChange: function ()
										{
											var dialog = this.getDialog();
											var chkRelated = dialog.getContentElement( 'videoTab', 'chkRelated' );
											if(this.getValue() == 'youtube'){
												chkRelated.enable();
											} else {
												chkRelated.disable();
											}
										}
									},
									{
										type : 'hbox',
										widths : [ '70%', '15%', '15%' ],
										children :
										[
											{
												id : 'videoUrl',
												type : 'text',
												label : 'Url zum Video',
												validate : function ()
												{
													if ( this.isEnabled() )
													{
														if ( !this.getValue() )
														{
															alert( 'Bitte eine Url hinzufügen' );
															return false;
														}
													}
												}
											},
											{
												type : 'text',
												id : 'videoWidth',
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
												id : 'videoHeight',
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
										type : 'hbox',
										widths : [ '55%', '45%' ],
										children :
										[
											{
												id : 'chkRelated',
												type : 'checkbox',
												'default' : true,
												label : 'Video Vorschläge'
											}
										]
									},
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
							var content = '',
							width = this.getValueOf( 'videoTab', 'videoWidth' ),
							height = this.getValueOf( 'videoTab', 'videoHeight' ),
							videoUrl = this.getValueOf( 'videoTab', 'videoUrl');
							
							if(this.getValueOf('videoTab', 'selectbox') == 'lecture2go'){
								
//								content = 'http://lecture2go.uni-hamburg.de/';
								

								content += '<embed id="ply2" width="'+ width +'" height="'+ height +'" flashvars="autostart=false&image=http://lecture2go.uni-hamburg.de/logo/l2g-flash.jpg&bufferlength=2&streamer=rtmp://fms.rrz.uni-hamburg.de:80/vod&file='+ videoUrl +'&backcolor=FFFFFF&frontcolor=000000&lightcolor=000000&screencolor=FFFFFF&id=id1" wmode="opaque" allowscriptaccess="always" allowfullscreen="true" quality="high" bgcolor="FFFFFF" name="ply" style="undefined" src="http://lecture2go.uni-hamburg.de/jw5.0/player-licensed.swf" type="application/x-shockwave-flash">';
								
								
							} else if(this.getValueOf('videoTab', 'selectbox') == 'youtube'){
								
								var url = 'https://', params = [], startSecs;
								var width = this.getValueOf( 'videoTab', 'videoWidth' );
								var height = this.getValueOf( 'videoTab', 'videoHeight' );
	
								
								url += 'www.youtube.com/';
								
	
								url += 'embed/' + video;
	
								if ( this.getContentElement( 'videoTab', 'chkRelated' ).getValue() === false )
								{
									params.push('rel=0');
								}
	
	
								url = url.replace('embed/', 'v/');
								url = url.replace(/&/g, '&amp;');
	
							
								url += '?';
								
	
								url += 'hl=pt_BR&amp;version=3';
								
								if ( this.getContentElement( 'videoTab', 'chkRelated' ).getValue() === false )
								{
									url += '&amp;rel=0';
								}
	
								content = '<object width="' + width + '" height="' + height + '">';
								content += '<param name="movie" value="' + url + '"></param>';
								content += '<param name="allowFullScreen" value="true"></param>';
								content += '<param name="allowscriptaccess" value="always"></param>';
								content += '<embed src="' + url + '" type="application/x-shockwave-flash" ';
								content += 'width="' + width + '" height="' + height + '" allowscriptaccess="always" ';
								content += 'allowfullscreen="true"></embed>';
								content += '</object>';
								
							} else if(this.getValueOf('videoTab', 'selectbox') == 'podcampus'){

								if(videoUrl.substr((videoUrl.length - 4),4) != '.swf'){
									videoUrl += '.swf';
								}
								content += '<object width="' + width + '" height="' + height + '">';
								content += '<param value="' + videoUrl + '" name="movie">';
								content += '<embed width="' + width + '" height="' + height + '" allowfullscreen="true" allowscriptaccess="always" type="application/x-shockwave-flash" src="' + videoUrl + '">';
								content += '</object>';
								
							} else if(this.getValueOf('videoTab', 'selectbox') == 'quicktime'){
								
								content += '<object width="400" codebase="http://www.apple.com/qtactivex/qtplugin.cab" classid="clsid:02BF25D5-8C17-4B23-BC80-D3488ABDDC6B" type="video/quicktime"><param value="' + videoUrl + '" name="src">';
								content += '<param value="true" name="controller">';
								content += '<param value="high" name="quality">';
								content += '<param value="tofit" name="scale">';
								content += '<param value="#000000" name="bgcolor">';
								content += '<param value="opaque" name="wmode">';
								content += '<param value="true" name="autoplay">';
								content += '<param value="false" name="loop">';
								content += '<param value="true" name="devicefont">';
								content += '<param value="mov" name="class">';
								content += '<embed width="400" pluginspage="http://www.apple.com/quicktime/download/" class="mov" type="video/quicktime" devicefont="true" loop="false" autoplay="true" wmode="opaque" bgcolor="#000000" controller="true" scale="tofit" quality="high" src="' + videoUrl + '">';
								content += '</object>';
								
							} else if(this.getValueOf('videoTab', 'selectbox') == 'mediaplayer'){
							
								content += '<object width="400" type="application/x-oleobject" standby="Loading Microsoft Windows Media Player components..." codebase="http://activex.microsoft.com/activex/controls/mplayer/en/nsmp2inf.cab#Version=6,4,5,715" classid="CLSID:22d6f312-b0f6-11d0-94ab-0080c74c7e95" id="MediaPlayer18">';
								content += '<param value="' + videoUrl + '" name="fileName">';
								content += '<param value="true" name="autoStart">';
								content += '<param value="true" name="showControls">';
								content += '<param value="true" name="showStatusBar">';
								content += '<param value="opaque" name="wmode">';
								content += '<embed width="400" showstatusbar="1" showcontrols="1" autostart="true" wmode="opaque" name="MediaPlayer18" src="' + videoUrl + '" pluginspage="http://www.microsoft.com/Windows/MediaPlayer/" type="application/x-mplayer2">';
								content += '</object>';
								
							}
							var instance = this.getParentEditor();
							instance.insertHtml( content );
						}
					};
				});
		
	}
} );


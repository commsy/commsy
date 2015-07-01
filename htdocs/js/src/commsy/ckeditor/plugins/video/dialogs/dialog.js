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
											var videoHelper = dialog.getContentElement( 'videoTab', 'videoUrl' );
											if(this.getValue() == 'youtube'){
												videoHelper.setValue('http://www.youtube.com/watch?v=[VideoID]');
											} else if(this.getValue() == 'lecture2go'){
												videoHelper.setValue('rtmp://fms.rrz.uni-hamburg.de:[port]/vod&file=mp4:[hash]/[videoDatei].mp4');
											} else if(this.getValue() == 'mediaplayer'){
												videoHelper.setValue('http://www.youtube.com/watch?v=[VideoID]');
											} else if(this.getValue() == 'quicktime'){
												videoHelper.setValue('http://www.youtube.com/watch?v=[VideoID]');
											} else if(this.getValue() == 'podcampus'){
												videoHelper.setValue('http://www.youtube.com/watch?v=[VideoID]');
											}
											
										}
									},
//									{
//										id	 : 'videoHelper',
//										type : 'hbox',
////										html : 'rtmp://fms.rrz.uni-hamburg.de:80/vod&file=mp4:37l2gl2go018/01-01.001_schaar_2013-10-16_16-30.mp4' + '<hr>'
//										label : 'http://www.youtube.com/watch?v=[VideoID]'
//									},
									{
										type : 'hbox',
										widths : [ '70%', '15%', '15%' ],
										children :
										[
											{
												id : 'videoUrl',
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
															video = ytVidId(this.getValue());

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
							}
						],
						onOk: function()
						{
							var content = '';
							var videoUrl = this.getValueOf( 'videoTab', 'videoUrl');
							var width = this.getValueOf( 'videoTab', 'videoWidth' );
							var height = this.getValueOf( 'videoTab', 'videoHeight' );
							
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
								
								content += '<object width="' + width + '" height="' + height + '">';
								content += '<param value="' + videoUrl + '" name="movie">';
								content += '<embed width="' + width + '" height="' + height + '" allowfullscreen="true" allowscriptaccess="always" type="application/x-shockwave-flash" src="' + videoUrl + '">';
								content += '</object>';
								
//								content += '<embed src="' + videoUrl + '" width="' + width + '" height="' + height + '" type="application/x-shockwave-flash" allowscriptaccess="always" allowfullscreen="true" />';
								
							} else if(this.getValueOf('videoTab', 'selectbox') == 'quicktime'){
								
							} else if(this.getValueOf('videoTab', 'selectbox') == 'movieplayer'){
								
							}
							
							
							

							var instance = this.getParentEditor();
							instance.insertHtml( content );
						}
					};
				});
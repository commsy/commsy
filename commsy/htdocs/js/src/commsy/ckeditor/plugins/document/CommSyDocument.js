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
			label:		"CommSy document",
			command:	"CommSyDocument",
			icon:		"../../src/commsy/ckeditor/plugins/audio/images/icon.png"
		} );
		
		CKEDITOR.dialog.add( 'CommSyDocument', function ( instance )
				{
					var audio;
					
					var SelectBoxItems = new Array(
					        new Array( 'Slideshare', 'slideshare' )
//					        new Array( 'wmaPlayer', 'wmaplayer' )
					);
					
					var floatItems = new Array (
							new Array ('<nichts>','null'),
							new Array ('Links','left'),
							new Array ('Rechts','right')
					);
					
					return {
						title : 'CommSy document',
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
										label: 'Document Type',
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
												id : 'documentUrl',
												type : 'text',
												label : 'DOC-ID (Bsp: quicktour-1209540124077378-8)',
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
											}
										]
									},
									{
										type : 'select',
										id : 'float',
										label : 'Ausrichtung',
										items : floatItems
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
							
							if(this.getValueOf('documentTab', 'selectbox') == 'slideshare'){
								
//								content = 'http://lecture2go.uni-hamburg.de/';
								
								var width = this.getValueOf( 'documentTab', 'documentWidth' );
								var height = this.getValueOf( 'documentTab', 'documentHeight' );
								var documentUrl = this.getValueOf( 'documentTab', 'documentUrl');
								var floatValue = '';
								
								if(float != 'null' && float == 'right'){
									floatValue += 'float:right;';
								} else if (float != 'null' && float == 'left') {
									floatValue += 'float:left;';
								} else {
									floatValue = '';
								}
								content += '<object width="' + width + '" height="' + height + '" style="margin:0px;' + floatValue + '">';
								content += '<param value="http://static.slideshare.net/swf/ssplayer2.swf?doc=' + documentUrl + '&amp;rel=0&amp;stripped_title=building-a-better-debt-lead" name="movie">';
								content += '<param value="true" name="allowFullScreen">';
								content += '<param value="always" name="allowScriptAccess">';
								content += '<embed width="' + width + '" height="' + height + '" wmode="opaque" allowfullscreen="true" allowscriptaccess="always" type="application/x-shockwave-flash" src="http://static.slideshare.net/swf/ssplayer2.swf?doc=' + documentUrl + '&amp;rel=0">';
								content += '</object>';
								
								
//								content += '<object data="' + documentUrl + '" type="application/x-shockwave-flash" width="200" height="300">';
//								content += '<embed src="' + documentUrl + '" width="' + width + '" height="' + height + '" allowscriptaccess="always" allowfullscreen="true"></embed>';
//								content += '</object>';
								
//								alert(content);
							}
							

							var instance = this.getParentEditor();
							instance.insertHtml( content );
						}
					};
				});
		
	}
} );

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
			label:		"CommSy Dokumente",
			command:	"CommSyDocument",
			icon:		"../../src/commsy/ckeditor/plugins/audio/images/icon.png"
		} );
		
		CKEDITOR.dialog.add( 'CommSyDocument', function ( instance )
				{
					var audio;
					
					var SelectBoxItems = new Array(
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
						fileItems.push(new Array(files[i].innerHTML, fileId[0].value));
					}
					
					var floatItems = new Array (
							new Array ('<nichts>','null'),
							new Array ('Links','left'),
							new Array ('Rechts','right')
					);
					
					return {
						title : 'CommSy Dokumente',
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
										onChange : function ()
										{
											// show input if onyx is selected
											var dialog = this.getDialog();
											var textInput = dialog.getContentElement('documentTab', 'linkText');
											var elementInputText = textInput.getElement();
											if(this.getValue() == 'onyx'){
												elementInputText.show();
											} else {
												elementInputText.hide();
											}
										}
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
										id : 'linkText',
										type : 'text',
										label : 'Text',
										onLoad : function () 
										{
											var textinput = this.getElement();
											textinput.hide();
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
							} else if(this.getValueOf('documentTab', 'selectbox') == 'onyx') {
								
								var cid = getUrlParam('cid');
								
								var dialog = this;
								var linkText = this.getValueOf('documentTab','linkText');
								var link = this.getValueOf('documentTab', 'documentUrl');
								
					            var a = editor.document.createElement( 'a' );
					            a.setAttribute( 'href', link);
					            
//					            a.setAttribute( 'href', 'commsy.php?cid=' + cid + '&mod=onyx&fct=showqti&iid=' + file_id + '');
					            a.setAttribute('target', 'help');
					            
					            a.setText( linkText );


					            editor.insertElement( a );
								
//								
//								var id = 75;
//								
//								target = 'target="help"';
//			                    onclick = 'onclick="window.open(href, target, \'toolbar=no, location=no, directories=no, status=no, menubar=no, scrollbars=yes, resizable=yes, dependent=yes, copyhistory=yes, width=900, height=600\');"';
//								
////								$content = '<a href="' + $c_single_entry_point + '?cid=' + $this->_environment->getCurrentContextID() + '&amp;mod=' + $this->_identifier + '&amp;fct=showqti&amp;iid=' + $id + '" ' + $target + ' ' + $onclick + '>' + $name + '</a>';
//								$content = '<a href="commsy.php?cid=' + cid + '&amp;mod=onyx&amp;fct=showqti&amp;iid=' + id + '" ' + target + ' ' + onclick + '>';
//								$content = 'Test';
//								$content = '</a>';
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

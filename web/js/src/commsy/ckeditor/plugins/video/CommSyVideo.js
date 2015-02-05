

CKEDITOR.plugins.add( "CommSyVideo",
{
	afterInit : function( editor )
    {
        function createFakeElement( editor, realElement )
        {
            return editor.createFakeParserElement( realElement, 'cke_video', 'video', true );
        }

        var dataProcessor = editor.dataProcessor,
                dataFilter = dataProcessor && dataProcessor.dataFilter;
            if ( dataFilter )
            {
                dataFilter.addRules(
                    {
                        elements :
                        {
                            'div' : function( element )
                            {
                                //alert("here");
                                var attributes = element.attributes;
                                if( attributes.class == 'commsyPlayer' ){
                                    //alert("here");
                                    
                                    return createFakeElement( editor, element );
                                }
                                return null;

                            }
                        }
                    },
                    5);
        	}
    },
    onLoad: function() {
		CKEDITOR.addCss( 'img.cke_video' +
			'{' +
				'background-image: url(' + CKEDITOR.getUrl( this.path + 'images/placeholder.png' ) + ');' +
				'background-position: center center;' +
				'background-repeat: no-repeat;' +
				'border: 1px solid #a9a9a9;' +
				'width: 640px;' +
				'height: 360px;' +
			'}'
			);

	},
	init: function( editor )
	{
		editor.addCommand( "CommSyVideo", new CKEDITOR.dialogCommand( "CommSyVideo" ) );
		
		editor.ui.addButton( "CommSyVideo",
		{
			label:		"Video",
			command:	"CommSyVideo",
			icon:		"../../src/commsy/ckeditor/plugins/video/images/icon.png"
		} );

		if ( editor.contextMenu ) {
            editor.addMenuGroup( 'abbrGroup' );
            editor.addMenuItem( 'abbrItem', {
            label: 		'CommSy Video',
            icon:		"../../src/commsy/ckeditor/plugins/video/images/icon.png",
            command: 	'CommSyVideo',
            group: 		'abbrGroup'
        });

            editor.contextMenu.addListener( function( element ) {
            	if ( element.getAscendant( 'img', true )) {
            		if ( element.getAscendant( 'img', true ).getAttribute('class') == "cke_video") {
                    	return { abbrItem: CKEDITOR.TRISTATE_OFF };
                	}
            	}
                
            });
        }
		
		CKEDITOR.dialog.add( 'CommSyVideo', function ( instance )
				{
					element: ""

					var regexGetSize = /^\s*(\d+)((px)|\%)?\s*$/i,
					regexGetSizeOrEmpty = /(^\s*(\d+)((px)|\%)?\s*$)|^$/i,
					video,
					urlDecodeFlag = false,
					// parse filenames from edit dialog
					files = document.getElementsByName('file_name');
					
					fileItems = new Array (
							new Array( '<Auswahl>' , 'null', 'null')
					);
					
					// fill select with filenames and file extension
					var i,fileId;
					for(i = 0; i < files.length; i++){
						fileId = document.getElementsByName('form_data[file_' + i + ']');
						fileItems.push(new Array(files[i].innerHTML, fileId[0].value, files[i].innerHTML.substr(files[i].innerHTML.lastIndexOf('.')+1, 3)));
					}
					
					var SelectBoxItems = new Array(
							new Array( '<Bitte Videotyp auswählen>', 'null'),
							new Array( 'CommSy Video', 'projekktor'),
							new Array( 'Lecture2Go', 'lecture2go' ),
					        //new Array( 'MediaPlayer (wma, wmv, avi)', 'mediaplayer' ),
					        new Array( 'Podcampus', 'podcampus' ),
					        //new Array( 'Quicktime (mov, wav, mpeg, mp4)', 'quicktime' ),
					        new Array( 'Vimeo', 'vimeo' ),
					        new Array( 'Youtube', 'youtube' )
					        
					);
					
					var numbering = function( id ) {
						return CKEDITOR.tools.getNextId() + '_' + id;
					},
					btnLockSizesId = numbering( 'btnLockSizes' ),
					btnResetSizeId = numbering( 'btnResetSize' );
//					imagePreviewLoaderId = numbering( 'ImagePreviewLoader' ),
//					previewLinkId = numbering( 'previewLink' ),
//					previewImageId = numbering( 'previewImage' );
					
					
					var onSizeChange = function() {
						var value = this.getValue(),
							// This = input element.
							dialog = this.getDialog(),
							aMatch = value.match( regexGetSize ); // Check value
						if ( aMatch ) {
							if ( aMatch[ 2 ] == '%' ) // % is allowed - > unlock ratio.
							switchLockRatio( dialog, false ); // Unlock.
							value = aMatch[ 1 ];
						}

						// Only if ratio is locked
						if ( dialog.lockRatio ) {
//							var oImageOriginal = dialog.originalElement;
							if ( this.id == 'videoHeight' ) {
								if ( value && value != '0' )
									value = Math.round( 640 * ( value / 360 ) );
								if ( !isNaN( value ) )
									dialog.setValueOf( 'videoTab', 'videoWidth', value );
							} else //this.id = txtWidth.
							{
								if ( value && value != '0' )
									value = Math.round( 360 * ( value / 640 ) );
								if ( !isNaN( value ) )
									dialog.setValueOf( 'videoTab', 'videoHeight', value );
							}
						}
//						updatePreview( dialog );
					};
					
					var switchLockRatio = function( dialog, value ) {
						if ( !dialog.getContentElement( 'videoTab', 'ratioLock' ) )
							return null;

						// Check image ratio and original image ratio, but respecting user's preference.
						if ( value == 'check' ) {
							if ( !dialog.userlockRatio) {
								var width = dialog.getValueOf( 'videoTab', 'videoWidth' ),
									height = dialog.getValueOf( 'videoTab', 'videoHeight' ),
									originalRatio = 640 * 1000 / 360,
									thisRatio = width * 1000 / height;
								dialog.lockRatio = false; // Default: unlock ratio

								if ( !width && !height )
									dialog.lockRatio = true;
								else if ( !isNaN( originalRatio ) && !isNaN( thisRatio ) ) {
									if ( Math.round( originalRatio ) == Math.round( thisRatio ) )
										dialog.lockRatio = true;
								}
							}
						} else if ( value != undefined )
							dialog.lockRatio = value;
						else {
							dialog.userlockRatio = 1;
							dialog.lockRatio = !dialog.lockRatio;
						}

						var ratioButton = CKEDITOR.document.getById( btnLockSizesId );
						if ( dialog.lockRatio )
							ratioButton.removeClass( 'cke_btn_unlocked' );
						else
							ratioButton.addClass( 'cke_btn_unlocked' );

						ratioButton.setAttribute( 'aria-checked', dialog.lockRatio );

						// Ratio button hc presentation - WHITE SQUARE / BLACK SQUARE
						if ( CKEDITOR.env.hc ) {
							var icon = ratioButton.getChild( 0 );
							icon.setHtml( dialog.lockRatio ? CKEDITOR.env.ie ? '\u25A0' : '\u25A3' : CKEDITOR.env.ie ? '\u25A1' : '\u25A2' );
						}

						return dialog.lockRatio;
					};
					
					var resetSize = function( dialog ) {
							var widthField = dialog.getContentElement( 'videoTab', 'videoWidth' ),
								heightField = dialog.getContentElement( 'videoTab', 'videoHeight' );
							widthField && widthField.setValue( '640' );
							heightField && heightField.setValue( '360' );
//						updatePreview( dialog );
					};
					
					return {
						title : 'Video-Eigenschaften',
						minWidth : 500,
						minHeight : 200,
						onShow: function ()
						{
							this.lockRatio = true;
						},
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
										label: 'Videotyp',
										items: SelectBoxItems,
										'default' : 'null',
										onLoad: function ()
										{
											var dialog = this.getDialog();
											var chkRelated = dialog.getContentElement( 'videoTab', 'chkRelated' );
											var dataSecurityBox = dialog.getContentElement( 'videoTab', 'dataSecurity');
											var startAt = dialog.getContentElement( 'videoTab', 'startAt');
											var urlInput = dialog.getContentElement( 'videoTab', 'videoUrl');
											
											//var uploadButton = dialog.getContentElement( 'videoTab', 'uploadButton');
											var upload = dialog.getContentElement( 'videoTab', 'upload');
											
											//uploadButton.disable();
											upload.disable();
											
											urlInput.setLabel('Aus URL einfügen');
											chkRelated.disable();
											dataSecurityBox.disable();
											startAt.disable();
										},
										onChange: function ()
										{
											var dialog = this.getDialog();
											var chkRelated = dialog.getContentElement( 'videoTab', 'chkRelated' );
											var urlInput = dialog.getContentElement( 'videoTab', 'videoUrl');
											var fileSelect = dialog.getContentElement( 'videoTab', 'fileselect');
											var dataSecurityBox = dialog.getContentElement( 'videoTab', 'dataSecurity');
											var startAt = dialog.getContentElement( 'videoTab', 'startAt');
											//var uploadButton = dialog.getContentElement( 'videoTab', 'uploadButton');
											var upload = dialog.getContentElement( 'videoTab', 'upload');
											
											
											// youtube video offer
											if(this.getValue() == 'youtube') {
												chkRelated.enable();
												dataSecurityBox.enable();
												startAt.enable();
											} else {
												chkRelated.disable();
												dataSecurityBox.disable();
												startAt.disable();
											}
											// set url info
											if(this.getValue() == 'lecture2go') {
												urlInput.setLabel('Einbettungscode von Lecture2Go unter "Teilen" verwenden <iframe src="...">');
											} else if (this.getValue() == 'podcampus') {
												urlInput.setLabel('Aus URL einfügen (http://www.podcampus.de/nodes/XXYZ)');
											} else {
												urlInput.setLabel('Aus URL einfügen');
											}
											
											// disable/enable inputUrl or select file
											if(this.getValue() == 'lecture2go') {
												urlInput.enable();
												fileSelect.disable();
												//uploadButton.disable();
												upload.disable();
											} else if (this.getValue() == 'mediaplayer') {
												urlInput.disable();
												fileSelect.enable();
												//uploadButton.enable();
												upload.enable();
												// only show files wma wmv avi
												var j;
												fileSelect.clear();
												fileSelect.add('<Auswahl>', 'null');
												for(j = 0; j < fileItems.length; j++) {
													if(fileItems[j][2] == 'avi' ||  fileItems[j][2] == 'wma' || fileItems[j][2] == 'wmv'){
														fileSelect.add(fileSelect.items[j][0],fileSelect.items[j][1]);
													}
												}
												
											} else if (this.getValue() == 'podcampus') {
												urlInput.enable();
												fileSelect.disable();
												//uploadButton.disable();
												upload.disable();
											} else if (this.getValue() == 'quicktime') {
												urlInput.disable();
												fileSelect.enable();
												//uploadButton.enable();
												upload.enable();
												// only show files mov wav mpeg mp4
												var j;
												fileSelect.clear();
												fileSelect.add('<Auswahl>', 'null');
												for(j = 0; j < fileItems.length; j++) {
													if(fileItems[j][2] == 'mov' ||  fileItems[j][2] == 'wav' || fileItems[j][2] == 'mpeg' || fileItems[j][2] == 'mp4'){
														fileSelect.add(fileSelect.items[j][0],fileSelect.items[j][1]);
													}
												}
											} else if (this.getValue() == 'vimeo') {
												urlInput.enable();
												fileSelect.disable();
												//uploadButton.disable();
												upload.disable();
											} else if (this.getValue() == 'youtube') {
												urlInput.enable();
												fileSelect.disable();
												//uploadButton.disable();
												upload.disable();
											} else if (this.getValue() == 'projekktor') {
												urlInput.disable();
												fileSelect.enable();
												//uploadButton.enable();
												upload.enable();
											}
										}
									},
									{
										type : 'hbox',
										widths : ['50%','50%'],
										children : 
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
													var inputUrl = dialog.getContentElement( 'videoTab', 'videoUrl' );
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
											//			alert(input.options[input.selectedIndex].text);
														
														fileUrl = 'commsy.php/' + input.options[input.selectedIndex].text + '?cid=' + cid + '&mod=' + mod + '&fct=getfile&iid=' + this.getValue();
														
														encodeFileUrl = encodeURI(fileUrl);
											//			alert(encodeFileUrl);
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
													    filebrowser: 'videoTab:videoUrl',
													    label: 'Hochladen',
													    'for': [ 'videoTab', 'upload' ],
													    onClick : function () 
													    {
													    	var dialog = this.getDialog();
															var fileSelect = dialog.getContentElement( 'videoTab' , 'selectbox' );
															if(fileSelect.getValue() == 'youtube' || 
																	fileSelect.getValue() == 'vimeo' ||
																	fileSelect.getValue() == 'podcampus' ||
																	fileSelect.getValue() == 'lecture2go'){
																
																alert('Dateiupload nicht möglich. Bitte wählen Sie einen entsprechenden Videotyp.');
														    	return false;
															}
													    	
													    }
													}
												]
											},
											{
									            type: 'text',
									            id: 'videoType',
									            style: 'display:none;',
									            size: 38
									        }
										]
									},
									{
										type : 'hbox',
										widths : [ '70%' ],
										children :
										[
											{
												id : 'videoUrl',
												type : 'text',
												label : 'Aus URL einfügen',
												setup: function (element) {
													if(element){
														if(element.getName() == 'div' && element.getAttribute('class') == 'commsyPlayer'){
															this.setValue(element.findOne('video').findOne('source').getAttribute('src'));
														}
													}
													
													
												},
												commit: function (element) {

													if(element) {
														var url;
														url = this.getValue();
														if(element.getName() == 'div' && element.getAttribute('class') == 'commsyPlayer' && url){
															element.findOne('video').findOne('source').setAttribute('src', url);
															element.findOne('video').findOne('source').setAttribute('data-cke-saved-src', url);
														}
													}
													
													
												},
												validate : function ()
												{
													if ( this.isEnabled() )
													{
														if ( !this.getValue() )
														{
															alert( 'Bitte eine URL hinzufügen' );
															return false;
														} else {
															video = ytVidId(this.getValue());
														}
													}
												}
											},
										]
									},
									{
										type : 'hbox',
										widths : [ '55%', '45%' ],
										children :
										[
											{
												id : 'autostart',
												type : 'checkbox',
												'default' : false,
												label : 'Autostart'
											},
											{
												id : 'chkRelated',
												type : 'checkbox',
												'default' : true,
												label : 'Video Vorschläge'
											},
											{
												id : 'dataSecurity',
												type : 'checkbox',
												'default' : false,
												label : 'Datenschutzmodus'
											},
											{
												id : 'helpds',
												type : 'html',
												html : '<a href="#" onClick="(document.getElementById(\'ck_info_box\').style.display == \'none\') ? document.getElementById(\'ck_info_box\').style.display=\'block\' : document.getElementById(\'ck_info_box\').style.display=\'none\'">[?]</a><div id="ck_info_box" style="position:absolute; display: none; background: #FFFFFF; border: 1px solid; padding:5px;-moz-box-shadow:1px 1px 6px #292929;-webkit-box-shadow:1px 1px 6px #292929;-khtml-box-shadow:1px 1px 6px #292929;box-shadow:1px 1px 6px #292929;">Mit der Aktivierung dieser Option, <br\>speichert Youtube nur beim Abspielen des Videos Informationen</div>'
											}
										]
									},
									{
										type : 'hbox',
										widths : [ '50px', '50px', '50px' ],
										style: 'margin-top:20px',
										children :
										[
											{
												type : 'text',
												id : 'videoWidth',
												width : '60px',
												label : 'Breite',
												'default' : '640',
												onKeyUp: onSizeChange,
												commit: function(element){
													if(element) {
														element.findOne('video').setAttribute('width', this.getValue())
													}
												},
												setup: function(element) {
													if(element) {
														var width = element.findOne('.projekktor').getAttribute('width')
														if(width){
															this.setValue(width);
														}
													}
												},
												validate: function() {
													var aMatch = this.getValue().match( regexGetSizeOrEmpty ),
														isValid = !!( aMatch && parseInt( aMatch[ 1 ], 10 ) !== 0 );
													if ( !isValid )
														alert( 'invalid width' );
													return isValid;
												}
											},
											{
												type : 'text',
												id : 'videoHeight',
												width : '60px',
												label : 'Höhe',
												'default' : '360',
												onKeyUp: onSizeChange,
//												onChange: function() {
//													commitInternally.call( this, 'advanced:txtdlgGenStyle' );
//												},
												commit: function(element){
													if(element) {
														element.findOne('video').setAttribute('height', this.getValue())
													}
												},
												setup: function(element) {
													if(element) {
														var height = element.findOne('.projekktor').getAttribute('height')
														if(height){
															this.setValue(height);
														}
													}
												},
												validate: function() {
													var aMatch = this.getValue().match( regexGetSizeOrEmpty ),
														isValid = !!( aMatch && parseInt( aMatch[ 1 ], 10 ) !== 0 );
													if ( !isValid )
														alert( 'invalid height' );
													return isValid;
												},
											},
											{
												id: 'ratioLock',
												type: 'html',
												style: 'margin-top:20px;width:40px;height:40px;',
												onLoad: function() {
													// Activate Reset button
													var resetButton = CKEDITOR.document.getById( btnResetSizeId ),
														ratioButton = CKEDITOR.document.getById( btnLockSizesId );
													if ( resetButton ) {
														resetButton.on( 'click', function( evt ) {
															resetSize( this );
															evt.data && evt.data.preventDefault();
														}, this.getDialog() );
														resetButton.on( 'mouseover', function() {
															this.addClass( 'cke_btn_over' );
														}, resetButton );
														resetButton.on( 'mouseout', function() {
															this.removeClass( 'cke_btn_over' );
														}, resetButton );
													}
													// Activate (Un)LockRatio button
													if ( ratioButton ) {
														ratioButton.on( 'click', function( evt ) {
															var locked = switchLockRatio( this ),
//																oImageOriginal = this.originalElement,
																width = this.getValueOf( 'videoTab', 'videoWidth' );
			
															if ( width ) {
																var height = 360 / 640 * width;
																if ( !isNaN( height ) ) {
																	this.setValueOf( 'videoTab', 'videoHeight', Math.round( height ) );
//																	updatePreview( this );
																}
															}
															evt.data && evt.data.preventDefault();
														}, this.getDialog() );
														ratioButton.on( 'mouseover', function() {
															this.addClass( 'cke_btn_over' );
														}, ratioButton );
														ratioButton.on( 'mouseout', function() {
															this.removeClass( 'cke_btn_over' );
														}, ratioButton );
													}
												},
												html: '<div>' +
													'<a href="javascript:void(0)" tabindex="-1" title="' + editor.lang.image.lockRatio +
													'" class="cke_btn_locked" id="' + btnLockSizesId + '" role="checkbox"><span class="cke_icon"></span><span class="cke_label">' + editor.lang.image.lockRatio + '</span></a>' +
													'<a href="javascript:void(0)" tabindex="-1" title="' + editor.lang.image.resetSize +
													'" class="cke_btn_reset" id="' + btnResetSizeId + '" role="button"><span class="cke_label">' + editor.lang.image.resetSize + '</span></a>' +
													'</div>'
											},
											{
												type : 'select',
												id : 'float',
												label : 'Ausrichtung',
												items : new Array (
															new Array ('<nichts>','null'),
															new Array ('Links','left'),
															new Array ('Rechts','right')
														),
												commit: function(element){
													if(element) {
														if(this.getValue() != 'null'){
															element.setStyle('float', this.getValue());
														} else {
															element.setStyle('float', '');
														}
													}
												},
												setup: function(element) {
													if(element){
														var value = element.getStyle('float');
														if(value == 'right'){
															this.setValue(value);
														} else if(value == 'left'){
															this.setValue(value);
														}
													}
												}
											},
											{
												type : 'text',
												id : 'border',
												width : '60px',
												label : 'Rahmen',
												'default' : '',
												commit: function(element){
													if(element) {
														element.setStyle('border-width', this.getValue()+'px');
													}
												},
												setup: function(element) {
													if(element){
														var value = element.getStyle('border-width').replace('px','');
														if(value){
															this.setValue(value);
														}
													}
												}
											},
											{
												type : 'text',
												id : 'marginH',
												width : '60px',
												label : 'H-Abstand',
												'default' : '',
												commit: function(element){
													if(element) {
														element.setStyle('margin-left', this.getValue()+'px');
														element.setStyle('margin-right', this.getValue()+'px');
													}
												},
												setup: function(element) {
													if(element){
														var value = element.getStyle('margin-left').replace('px','');
														if(value){
															this.setValue(value);
														}
													}
												}
											},
											{
												type : 'text',
												id : 'marginV',
												width : '60px',
												label : 'V-Abstand',
												'default' : '',
												commit: function(element){
													if(element) {
														element.setStyle('margin-top', this.getValue()+'px');
														element.setStyle('margin-bottom', this.getValue()+'px');
													}
												},
												setup: function(element) {
													if(element){
														var value = element.getStyle('margin-top').replace('px','');
														if(value){
															this.setValue(value);
														}
													}
												}
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
												label : 'Startpunkt in Sekunden (Funktioniert nur bei Youtube Videos)',
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
//							}
						],
						onShow: function() {
							// set all values for input fields
							// after all remove element in editor
							var newElement;
		                    var selection = editor.getSelection(),
		                        element = selection.getStartElement();
		                    if ( element )
		                        element = element.getAscendant( 'img', true );

		                    if ( !element || element.getName() != 'img' || element.getAttribute('class') != 'cke_video' || !element.data( 'cke-realelement' ) ) {
		                        //element = editor.document.createElement( 'video' );
		                        newElement = null;
		                        this.insertMode = true;
		                    }
		                    else {
		                    	newElement = CKEDITOR.dom.element.createFromHtml(decodeURIComponent(element.getAttribute('data-cke-realelement')));
		                        this.insertMode = false;
		                    }

		                    this.element = newElement;

		                    if ( !this.insertMode )
		                        this.setupContent( this.element );

		                },
						onOk: function()
						{

							var content = '',
							width = this.getValueOf( 'videoTab', 'videoWidth' ),
							height = this.getValueOf( 'videoTab', 'videoHeight' ),
							videoUrl = this.getValueOf( 'videoTab', 'videoUrl');
							
							var autostart = this.getValueOf( 'videoTab', 'autostart');
							

							// #########################################################
							// define styles
							var float = this.getValueOf( 'videoTab', 'float');
							var floatValue = '';
							
							var style,
							borderWidth = this.getValueOf( 'videoTab', 'border' ),
							horizontalMargin = this.getValueOf( 'videoTab', 'marginH'),
							verticalMargin = this.getValueOf( 'videoTab', 'marginV');
							
							style = 'style="';
							if ( borderWidth !== "" ) {
								style += 'border-style: solid; border-width:' + borderWidth + 'px;';
							}
							
							if ( horizontalMargin !== "" ) {
								style += 'margin-top:' + horizontalMargin + 'px;';
								style += 'margin-bottom:' + horizontalMargin + 'px;';
							}
							
							if ( verticalMargin !== "" ) {
								style += 'margin-left:' + verticalMargin + 'px;';
								style += 'margin-right:' + verticalMargin + 'px;';
							}
							
							if(float != 'null' && float == 'right'){
								style += 'float:right;';
							} else if (float != 'null' && float == 'left') {
								style += 'float:left;';
							}
							style += '"';
							// define styles end
							// ##########################################################
							

							if(this.getValueOf('videoTab', 'selectbox') == 'lecture2go'){
								
//								content = 'http://lecture2go.uni-hamburg.de/';
								
								// use regex if iframe tag is set
								var regEx = '(mp4:).*mp4';
								var match = videoUrl.match(regEx);
								
								var videoUrlRegEx = match[0];
								
								content += '<embed ' + style + ' id="ply2" width="'+ width +'" height="'+ height +'" flashvars="autostart=false&image=https://lecture2go.uni-hamburg.de/logo/l2g-flash.jpg&bufferlength=2&streamer=rtmps://fms.rrz.uni-hamburg.de:443/vod&file='+ videoUrlRegEx +'&backcolor=FFFFFF&frontcolor=000000&lightcolor=000000&screencolor=FFFFFF&id=id1" wmode="opaque" allowscriptaccess="always" allowfullscreen="true" quality="high" bgcolor="FFFFFF" name="ply" style="' + floatValue + '" src="https://lecture2go.uni-hamburg.de/jw5.0/player-licensed.swf" type="application/x-shockwave-flash">';
//								alert(content);
								
//								content += '<object type="application/x-shockwave-flash" data="http://lecture2go.uni-hamburg.de/jw5.0/player-licensed.swf" width="'+ width +'" height="'+ height +'" id="VideoPlayback">';
//								content += '<param name="movie" value="http://lecture2go.uni-hamburg.de/jw5.0/player-licensed.swf" />';
//								content += '<param name="allowScriptAcess" value="sameDomain" />';
//								content += '<param name="quality" value="best" />';
//								content += '<param name="bgcolor" value="#FFFFFF" />';
//								content += '<param name="scale" value="noScale" />';
//								content += '<param name="salign" value="TL" />';
//								content += '<param name="flashvars" value="autostart=false&image=http://lecture2go.uni-hamburg.de/logo/l2g-flash.jpg&bufferlength=2&streamer=rtmp://fms.rrz.uni-hamburg.de:80/vod&file='+ videoUrl +'&backcolor=FFFFFF&frontcolor=000000&lightcolor=000000&screencolor=FFFFFF&id=id1 />';
//								content += '</object>';
								
								
							} else if(this.getValueOf('videoTab', 'selectbox') == 'youtube'){
								
								var url = 'https://', params = [], startSecs;
								var width = this.getValueOf( 'videoTab', 'videoWidth' );
								var height = this.getValueOf( 'videoTab', 'videoHeight' );
								var dataSecurity = this.getValueOf( 'videoTab', 'dataSecurity');
								var startAt = this.getValueOf( 'videoTab', 'startAt');
								
								if(dataSecurity){
									url += 'www.youtube-nocookie.com/';
								} else {
									url += 'www.youtube.com/';
								}
								
								
	
								url += 'embed/' + video;
	
//								if ( this.getContentElement( 'videoTab', 'chkRelated' ).getValue() === false )
//								{
//									params.push('rel=0');
//								}
								url = url.replace('embed/', 'v/');
								url = url.replace(/&/g, '&amp;');
	
							
								url += '?';
								
	
								url += 'hl=pt_BR&amp;version=3';

								if(this.getValueOf('videoTab', 'autostart')){
									url += '&autoplay=1';
								}
								
								if(startAt !== ""){
									url += '&start='+startAt;
								}
								
								
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
								content += 'allowfullscreen="true" ' + style + '></embed>';
								content += '</object>';
								
							} else if(this.getValueOf('videoTab', 'selectbox') == 'podcampus'){

								if(videoUrl.substr((videoUrl.length - 4),4) != '.swf'){
									videoUrl += '.swf';
								}
								content += '<object width="' + width + '" height="' + height + '" ' + style + '>';
								content += '<param value="' + videoUrl + '" name="movie">';
								content += '<embed width="' + width + '" height="' + height + '" allowfullscreen="true" allowscriptaccess="always" type="application/x-shockwave-flash" src="' + videoUrl + '">';
								content += '</object>';
								
							} else if(this.getValueOf('videoTab', 'selectbox') == 'vimeo'){
								
								var vimeo_regex = /vimeo.com\/(.*)/,
									url,
									vimeo_id = videoUrl.match(vimeo_regex)[1];
								
								if(this.getValueOf('videoTab', 'autostart')){
									url += '&amp;autoplay=1';
								}
								
								content += '<object width="' + width + '" height="' + height + '" ' + style + '>';
								content += '<param name="allowfullscreen" value="true" />';
								content += '<param name="allowscriptaccess" value="always" />';
								content += '<param name="movie" value="http://vimeo.com/moogaloop.swf?clip_id=' + vimeo_id + '&amp;force_embed=1&amp;server=vimeo.com&amp;show_title=1&amp;show_byline=1&amp;show_portrait=1&amp;color=00adef&amp;fullscreen=1&amp;loop=0' + url + '" />';
								content += '<embed src="http://vimeo.com/moogaloop.swf?clip_id=' + vimeo_id + '&amp;force_embed=1&amp;server=vimeo.com&amp;show_title=1&amp;show_byline=1&amp;show_portrait=1&amp;color=00adef&amp;fullscreen=1&amp;loop=0' + url + '" type="application/x-shockwave-flash" allowfullscreen="true" allowscriptaccess="always" width="' + width + '" height="' + height + '" ' + style + '>';
								content += '</embed></object>';
								content += '';
								
								
							} else if(this.getValueOf('videoTab', 'selectbox') == 'projekktor'){

								var fileType = this.getValueOf( 'videoTab', 'videoType' );

								if(!fileType){
									var regEx = /\/.*\.(.{3,})\?/;
									var match = videoUrl.match(regEx);
									//
									switch (match[1]) {
										case "avi":
											fileType = 'video/x-msvideo';
											break;
										case "wmv":
											fileType = 'video/x-ms-wmv';
											break;
										case "mov":
											fileType = 'video/quicktime';
											break;
										case "mp4":
											fileType = 'video/mp4';
											break;
										case "mpeg":
											fileType = 'video/mpeg';
											break;
										// case "mp3":
										// 	fileType = 'audio/mpeg'
										// 	break;
										// case "wma":
										// 	fileType = 'x-ms-wma';
										// 	break;
									}
								}

								content += '<div class="commsyPlayer" ' + style + ' width="' + width + '" height="' + height + '">&nbsp;';
								content += '<video width="' + width + '" height="' + height + '" class="projekktor">';
								content += '<source src="' + videoUrl + '" type="' + fileType + '"/>';
								content += '</video>';
								content += '</div>';
							}
							this.commitContent(this.element);

							var instance = this.getParentEditor();
							if(this.insertMode){
								instance.insertHtml( content );
							} else {
								//instance.insertElement( this.element );
								instance.insertHtml( this.element.getOuterHtml() );
							}
							
						}
					};
				});
		
	}
} );

/**
 * JavaScript function to match (and return) the video Id 
 * of any valid Youtube Url, given as input string.
 * @author: Stephan Schmitz <eyecatchup@gmail.com>
 * @url: http://stackoverflow.com/a/10315969/624466
 */
function ytVidId( url )
{
	var p = /^(?:https?:\/\/)?(?:www\.)?(?:youtu\.be\/|youtube\.com\/(?:embed\/|v\/|watch\?v=|watch\?.+&v=))((\w|-){11})(?:\S+)?$/;
	return ( url.match( p ) ) ? RegExp.$1 : false;
}

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



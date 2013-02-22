/*
Copyright (c) 2003-2012, CKSource - Frederico Knabben. All rights reserved.
For licensing, see LICENSE.html or http://ckeditor.com/license
*/

CKEDITOR.plugins.add( "CommSyAbout",
{
	init: function( editor )
	{
		editor.addCommand( "CommSyAbout", new CKEDITOR.dialogCommand( "CommSyAbout" ) );
		
		editor.ui.addButton( "CommSyAbout",
		{
			label:		"CommSy Formatierungsmöglichkeiten",
			command:	"CommSyAbout",
			icon:		"../../src/commsy/ckeditor/plugins/about/icon.png"
		} );
		
		CKEDITOR.dialog.add( "CommSyAbout", function( api )
		{
			return {
				title : 'CommSy Formatierungsmöglichkeiten',
				minWidth : 950,
				minHeight : 550,
				contents : [
					{
						id : 'tab1',
						label : 'CommSy Formatierungsmöglichkeiten',
						title : 'CommSy Formatierungsmöglichkeiten',
						expand : false,
						padding : 0,
						elements :
						[
							{
								type : 'html',
								html :
									'<table>'+
									'<tr>'+
									'<td>'+
									'<span style="font-weight: bold;">Listen:</span> <span style="font-weight: normal;"># für numerierte, - für unnumerierte Listen</span><br>'+
									'<span style="font-weight: bold;">Auszeichnungen:</span> <span style="font-weight: normal;">*text* wird zu </span> <span style="font-weight: bold;">text</span>, <span style="font-weight: normal;">_text_ wird zu </span><span style="font-weight: normal; font-style: italic;">text</span><br>'+
									'<span style="font-weight: bold;">Überschriften: </span><span style="font-weight: normal;">!Text,!!Text oder !!!Text erzeugt Überschiften</span><br>'+
									'<span style="font-weight: bold;">Trennlinie: </span><span style="font-weight: normal;">--- für eine horizontale Linie</span>'+
									'<br><hr><br><div class="bold" style="padding-bottom: 5px;">Einbettung von Medien:</div><ol style="font-weight: normal;"><li>Suchen Sie bitte die zum Medium passende Zeile (siehe unten),</li><li> kopieren diese,</li><li>fügen die Zeile in den Beitrag ein und</li><li>ersetzen die GROSSBUCHSTABEN mit eigenen Angaben.</li></ol>'+

									'<span style="font-weight: bold;">Links:</span>'+
									'<ul style="font-weight: normal; padding-left: 15px; margin: 0px;">'+
									'    <li>(:link ZIEL text=TEXT:) für Hyperlinks</li>'+
									'    <li>(:item NUMMER text=TEXT:) für Verweise auf Einträge in CommSy</li>'+
									'    <li>(:file DATEINAME text=TEXT:) für hochgeladene Dateien</li>'+
									'    <li>Argumente:'+
									'    <ul>'+
									'        <li>newwin: öffnet beim Klickens ein neues Browserfenster</li>'+
									'        <li>text=TEXT: Beschriftung des Links</li>'+
									'        <li>text=\'LANGER TEXT\': mehr als ein Wort Beschriftung</li>'+
									'    </ul>'+
									'    </li>'+
									'    <li>Bsp: (:file datei.pdf text=\'Meine Datei\' newwin:)</li>'+
									'</ul><br><span style="font-weight: bold;">Bilder:</span>'+
									'<ul style="font-weight: normal; padding-left: 15px; margin: 0px;">'+
									'    <li>(:image BILDNAME alt=TEXT rfloat width=ZAHL:)</li>'+
									'    <li>Argumente:'+
									'    <ul>'+
									'        <li>lfloat bzw. rfloat: Textumfluss von rechts bzw. links</li>'+
									'        <li>alt=TEXT: Beschriftung des Links</li>'+
									'        <li>alt=\'LANGER TEXT\': mehr als ein Wort Beschriftung</li>'+
									'        <li>width=ZAHL bzw. height=ZAHL: Breite bzw. Höhe in px</li>'+
									'        <li>http://www.url.de/IMAGE: für Bilder direkt aus dem Internet</li>'+
									'    </ul>'+
									'    </li>'+
									'    <li>Bsp: (:image bild.jpg width=150 alt=Text lfloat:)</li>'+
									'</td>'+
									'<td>'+
									'</ul><br><span style="font-weight: bold;">Videos:</span>'+
									'<ul style="font-weight: normal; padding-left: 15px; margin: 0px;">'+
									'    <li>(:wmplayer DATEINAME play=true width=ZAHL:) für wma, wmv, avi, ...</li>'+
									'    <li>(:quicktime DATEINAME play=true width=ZAHL:) für mov, wav, mpeg, mp4, ...</li>'+
									'    <li>(:flash DATEINAME play=true width=ZAHL:) für swf und flv</li>'+
									'    <li>(:youtube FILM-ID:) oder (:wmplayer FILM-ID:) für Videos auf youtube</li>'+
									'    <li>(:googlevideo FILM-ID:) für Videos bei google</li>'+
									'    <li>(:lecture2go DATEINAME:) für Veranstaltungsaufzeichnungen von Lecture2Go.<br> Achtung: Der DATEINAME muss immer auch einen Ordner enthalten.<br>Ein Server (server=rtmp://fms.rrz.uni-hamburg.de:ZAHL/vod) muss nur bei alten Filmen angegeben werden.</li>'+
									'<li>(:podcampus FILM-ID:) für Filme auf <a href="http://www.podcampus.de" target="_blank">www.podcampus.de</a></li>'+
									'    <li>Argumente:'+
									'    <ul>'+
									'        <li>lfloat bzw. rfloat: Textumfluss von rechts bzw. links</li>'+
									'        <li>width=ZAHL bzw. height=ZAHL: Breite bzw. Höhe in px</li>'+
									'        <li>play=true/false: automatischer Start (Standard ist false)</li>'+
									'        <li>http://www.url.de/DATEINAME: für Videos direkt aus dem Internet</li>'+
									'        <li>FILM-ID: ID aus der URL (z.B. http://de.youtube.com/watch?v=FILM-ID)</li>'+
									'    </ul>'+
									'    </li>'+
									'    <li>Bsp: (:quicktime video.mpeg width=150 play=true:)</li>'+
									'    <li>Bsp: (:youtube pQHX-SjgQvQ play=false:)</li>'+
									'</ul><br><span style="font-weight: bold;">Musik:</span>'+
									'<ul style="font-weight: normal; padding-left: 15px; margin: 0px;">'+
									'<li>(:podcampus AUDIO-ID:) für Audio-Pods auf <a href="http://www.podcampus.de" target="_blank">www.podcampus.de</a></li>'+
									'    <li>(:wmplayer DATEINAME play=true width=ZAHL:) für wma-Dateien</li>'+
									'    <li>(:mp3 DATEINAME.mp3:) für mp3-Dateien</li>'+
									'    <li>Argumente:'+
									'    <ul>'+
									'        <li>lfloat bzw. rfloat: Textumfluss von rechts bzw. links</li>'+
									'        <li>width=ZAHL bzw. height=ZAHL: Breite bzw. Höhe in px</li>'+
									'        <li>play=true/false: automatischer Start (Standard ist false)</li>'+
									'        <li>http://www.url.de/DATEINAME.mp3: für Musik direkt aus dem Internet</li>'+
									'    </ul>'+
									'    </li>'+
									'    <li>Bsp: (:mp3 mymusic.mp3 width=150 play=true:)</li>'+
									'</ul><br><span style="font-weight: bold;">Dokumente:</span>'+
									'<ul style="font-weight: normal; padding-left: 15px; margin: 0px;">'+
									'    <li>(:slideshare DOC-ID:) für Slideshare-Präsentationen</li>'+
									'    <li>Argumente:'+
									'    <ul>'+
									'        <li>DOC-ID: slideshare.net "doc="-Angabe für die Präsentation (siehe WordPress-Quelltext unten)</li>'+
									'    </ul>'+
									'    </li>'+
									'    <li>Bsp: (:slideshare quicktour-1209540124077378-8:)<br>oder:<br></li>'+
									'    <li>[slideshare id=380816&amp;doc=quicktour-1209540124077378-8] WordPress-Quelltext von slideshare.net</li>'+
									'</ul>'+
									'</td>'+
									'</tr>'+
									'</table>'
							}
						]
					}
				],
				buttons : []
			};
		} );
	}
} );
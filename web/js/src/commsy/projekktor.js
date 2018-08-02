define(["dojo/cookie"], function(cookie) {
    var options = {
                poster: 'js/3rdParty/projekktor-1.3.09/media/intro.png',
                playerFlashMP4: 'js/3rdParty/projekktor-1.3.09/swf/StrobeMediaPlayback/StrobeMediaPlayback.swf', // paths to StrobeMediaPlayback.swf on your serwer
                playerFlashMP3: 'js/3rdParty/projekktor-1.3.09/swf/StrobeMediaPlayback/StrobeMediaPlayback.swf',      
                useYTIframeAPI: false,
                platforms: ['browser', 'android', 'ios', 'native', 'vlc', 'flash'],
                ratio: 16/9,
                controls: true
                };
        return  {
               
                setupProjekktor: function() {
                    var self = this;

                    //projekktor
                    projekktor('video', 
                        options,
                    function(player) {
                            player.addListener('state', function(val, ref) {
                            if($('.alt_play_method')){
                                $('.alt_play_method').click(function(){
                                    self.clickAlternativeMethod(player);
                                });
                            }

                            });
                    }); 
                },
                clickAlternativeMethod: function(player) {
                    // get player height width from projekktor
                    var height = $('.commsyPlayer .projekktor').height();
                    var width = $('.commsyPlayer .projekktor').width();

                    // $('.commsyPlayer .projekktor').replaceWith('Alte Abspielmethode');
                    
                    var commsyPlayerDiv = $('#' + player.getId() + '_media').parent().parent().parent();
                    $('#' + player.getId() + '_media').parent().parent().replaceWith('Alte Abspielmethode');


                    var videoFile = player.getItem().file[0];

                    var OSName="Unknown OS";
                    if (navigator.appVersion.indexOf("Win")!=-1) OSName="Windows";
                    if (navigator.appVersion.indexOf("Mac")!=-1) OSName="MacOS";
                    if (navigator.appVersion.indexOf("X11")!=-1) OSName="UNIX";
                    if (navigator.appVersion.indexOf("Linux")!=-1) OSName="Linux";

                    // try to get file extension
                    var regEx = /\/.*\.(.{3,})\?/;
                    var match = videoFile.src.match(regEx);

                    var videoUrl = videoFile.src;

                    var SID = "&SID=" + cookie("SID");
                    
                    var content = '';

                    if(match[1] == "mov" || match[1] == "mpeg" || match[1] == "mp4" || match[1] == "wav"){

                        content += '<object width="' + width + '" heigth="'+ height +'" codebase="http://www.apple.com/qtactivex/qtplugin.cab" classid="clsid:02BF25D5-8C17-4B23-BC80-D3488ABDDC6B" type="video/quicktime"><param value="' + videoUrl + SID + '" name="src">';
                        content += '<param value="true" name="controller">';
                        content += '<param value="high" name="quality">';
                        content += '<param value="tofit" name="scale">';
                        content += '<param value="#000000" name="bgcolor">';
                        content += '<param value="opaque" name="wmode">';
                        content += '<param value="true" name="autoplay">';
                        content += '<param value="false" name="loop">';
                        content += '<param value="true" name="devicefont">';
                        content += '<param value="mov" name="class">';
                        content += '<embed width="' + width + '" height="' + height + '" pluginspage="http://www.apple.com/quicktime/download/" class="mov" type="video/quicktime" devicefont="true" loop="false" wmode="opaque" bgcolor="#000000" controller="true" scale="tofit" quality="high" src="' + videoUrl + SID + '" controller="true">';
                        content += '</object>';

                    } else if(match[1] == "avi" || match[1] == "wmv" || match[1] == "wma") {
                        content += '<object height="' + height + '" width="' + width + '" type="application/x-oleobject" standby="Loading Microsoft Windows Media Player components..." codebase="http://activex.microsoft.com/activex/controls/mplayer/en/nsmp2inf.cab#Version=6,4,5,715" classid="CLSID:22d6f312-b0f6-11d0-94ab-0080c74c7e95" id="MediaPlayer18">';
                        content += '<param value="' + videoUrl + SID + '" name="fileName">';
                        content += '<param value="true" name="autoStart">';
                        content += '<param value="true" name="showControls">';
                        content += '<param value="true" name="showStatusBar">';
                        content += '<param value="opaque" name="wmode">';
                        content += '<embed width="' + width + '" height="' + height + '" showstatusbar="1" showcontrols="1" autostart="true" wmode="opaque" name="MediaPlayer18" src="' + videoUrl + SID +'" pluginspage="http://www.microsoft.com/Windows/MediaPlayer/" type="application/x-mplayer2">';
                        content += '</object>';
                    }

                    commsyPlayerDiv.append(content);
                    
                }
        
        };
});

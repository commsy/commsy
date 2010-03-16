jQuery(document).ready(function() {
   jQuery("select[id='submit_form']").each(function(i) {
      jQuery(this).change(function () {
         jQuery(this).parents("form").map(function () {
            this.submit();
         });
      });
   });

   jQuery("input[id='submit_form'][type='checkbox']").each(function(i) {
      jQuery(this).change(function () {
         jQuery(this).parents("form").map(function () {
            this.submit();
         });
      });
   });

   jQuery("a[id='submit_form']").each(function(i) {
      jQuery(this).click(function () {
         jQuery(this).parents("form").map(function () {
            this.submit();
         });
      });
   });
});

function resetSearchText(id){
   jQuery('#' + id).val("");
}

function handleWidth_new(id,max_width,link_name){
   var div = jQuery('#' + id);
   var inner_div = jQuery('#' + 'inner_'+id);
   var width = inner_div.scrollWidth;
   var height = inner_div.scrollHeight;

   if (width > max_width){
      inner_div.style.width = max_width+'px';
      if (navigator.userAgent.indexOf("MSIE") != -1){
         inner_div.style.height = (height+50)+'px';
      }
   }
}

var xpPanel_slideActive = true;	// Slide down/up active?
var xpPanel_slideSpeed = 30;	// Speed of slide
var xpPanel_onlyOneExpandedPane = false;	// Only one pane expanded at a time ?
var commsy_pane;
var commsy_panel_index;
var savedActivePane = new Array();
var savedActiveSub = new Array();
var xpPanel_currentDirection = new Array();
var cookieNames = new Array();
var speedArray = new Array();
var currentlyExpandedPane = false;

function initTextFormatingInformation(item_id,is_shown){
   if (is_shown == false){
      jQuery('#creator_information'+item_id).hide();
   }else{
      jQuery('#toggle'+item_id).attr('src', jQuery('#toggle'+item_id).attr('src').replace('more','less'));
   }
   jQuery('#toggle'+item_id).click(function(){
      if(jQuery('#toggle'+item_id).attr('src').toLowerCase().indexOf('less') >= 0){
         jQuery('#creator_information'+item_id).slideUp(200);
         jQuery('#toggle'+item_id).attr('src', jQuery('#toggle'+item_id).attr('src').replace('less','more'));
      } else {
         jQuery('#creator_information'+item_id).slideDown(200);
         jQuery('#toggle'+item_id).attr('src', jQuery('#toggle'+item_id).attr('src').replace('more','less'));
      }
   });
   jQuery('#toggle'+item_id).mouseover(function(){
      jQuery('#toggle'+item_id).attr('src', jQuery('#toggle'+item_id).attr('src').replace('.gif','_over.gif'));
   });
   jQuery('#toggle'+item_id).mouseout(function(){
      jQuery('#toggle'+item_id).attr('src', jQuery('#toggle'+item_id).attr('src').replace('_over.gif','.gif'));
   });
}

function initCreatorInformations(item_id,is_shown){
   if (is_shown == false){
      jQuery('#creator_information'+item_id).hide();
   }else{
      jQuery('#toggle'+item_id).attr('src', jQuery('#toggle'+item_id).attr('src').replace('more','less'));
   }
   jQuery('#toggle'+item_id).click(function(){
      if(jQuery('#toggle'+item_id).attr('src').toLowerCase().indexOf('less') >= 0){
         jQuery('#creator_information'+item_id).slideUp(200);
         jQuery('#toggle'+item_id).attr('src', jQuery('#toggle'+item_id).attr('src').replace('less','more'));
      } else {
         jQuery('#creator_information'+item_id).slideDown(200);
         jQuery('#toggle'+item_id).attr('src', jQuery('#toggle'+item_id).attr('src').replace('more','less'));
      }
   });
   jQuery('#toggle'+item_id).mouseover(function(){
      jQuery('#toggle'+item_id).attr('src', jQuery('#toggle'+item_id).attr('src').replace('.gif','_over.gif'));
   });
   jQuery('#toggle'+item_id).mouseout(function(){
      jQuery('#toggle'+item_id).attr('src', jQuery('#toggle'+item_id).attr('src').replace('_over.gif','.gif'));
   });
}

function preInitCommSyPanels(panelTitles,panelDesc,panelDisplayed,cookieArray,sizeArray){
   jQuery(document).ready(function() {
      initCommSyPanels(panelTitles,panelDesc,panelDisplayed,cookieArray,sizeArray,Array(),null,null);
   });
}

function initCommSyPanels(panelTitles,panelDesc,panelDisplayed,cookieArray,sizeArray,modArray,contextID,modify){
   var divs = jQuery('#commsy_panels').find('div');
   commsy_panel_index=0;
   for(var no=0;no<sizeArray.length;no++){
      if (sizeArray[no] < 31) {
         speedArray[no]=xpPanel_slideSpeed;
      } else if (sizeArray[no] < 61) {
         speedArray[no]=xpPanel_slideSpeed*2;
      } else {
         speedArray[no]=sizeArray[no];
      }
   }
   for(var no=0;no<divs.length;no++){
      if(divs[no].className == 'commsy_panel'){
         var temp_div = jQuery(divs[no]);
         var outerContentDiv = jQuery('<div></div>');
         var contentDiv = temp_div.children('div:first');
         outerContentDiv.append(contentDiv);
         outerContentDiv.attr('id', 'paneContent' + commsy_panel_index);
         outerContentDiv.attr('class', 'panelContent');

         var topBar = jQuery('<div></div>');
         topBar.attr('id', 'topBar' + commsy_panel_index);
         topBar.onselectstart = cancelXpWidgetEvent;

         var info = jQuery('<div></div>');
         info.attr('id', 'info' + commsy_panel_index);
         info.css('float', 'left');

         var span = jQuery('<span></span>');
         span.attr('id', 'span' + commsy_panel_index);
         span.html(panelTitles[commsy_panel_index].replace(/&COMMSYDHTMLTAG&/g,'</'));
         span.css('line-height', '20px');
         span.css('vertical-align', 'bottom');
         info.append(span);

         var span2 = jQuery('<span></span>');
         span2.attr('id', 'spanKlick' + commsy_panel_index);
         if(panelDesc[commsy_panel_index] == ''){
            span2.html('&nbsp;');
         } else {
            span2.html(panelDesc[commsy_panel_index]);
         }
         span2.attr('class', 'small');
         span2.css('line-height', '20px');
         span2.css('vertical-align', 'bottom');
         info.append(span2);

         topBar.css('position', 'relative');
         topBar.append(info);

         var klick = jQuery('<div></div>');
         klick.attr('id', 'klick' + commsy_panel_index);
         klick.css('height', '100%');

         if((contentDiv.attr('id') == 'homeheader') && (modArray[commsy_panel_index] != 'user')){
        	//var dropdownItem = jQuery('<span><img src="images/commsyicons/16x16/browse_right2.png" data-popupmenu="popmenu'+no+'" /><ul id="popmenu'+no+'" class="jqpopupmenu"><li><a href="#">Aktion 1</a></li><li><a href="#">Aktion 2</a></li><li><a href="#">Aktion 3</a></li></ul></span>');
            //dropdownItem.css('width', '18px');
        	//dropdownItem.css('padding-left', '0px');
        	//dropdownItem.css('padding-top', '6px');
        	//dropdownItem.css('padding-right', '3px');
            //dropdownItem.css('float', 'right');
            //topBar.append(dropdownItem);
            var newItem = jQuery('<span></span>');
            if(modify == 1){
               if(session_id){
                 if(modArray[commsy_panel_index] == 'room'){
                    if(is_community_room){
                       modArray[commsy_panel_index] = 'project';
                    }
                 }
                 if (navigator.userAgent.indexOf("MSIE 6.0") == -1){
                     newItem.html('<a href="commsy.php?cid=' + contextID + '&mod=' + modArray[commsy_panel_index] + '&fct=edit&iid=NEW&SID=' + session_id + '" title="' + new_action_message + '"><img src="images/commsyicons/16x16/new_home_big.png"/></a>');
                 } else {
                   newItem.html('<a href="commsy.php?cid=' + contextID + '&mod=' + modArray[commsy_panel_index] + '&fct=edit&iid=NEW&SID=' + session_id + '" title="' + new_action_message + '"><img src="images/commsyicons_msie6/16x16/new_home_big.gif"/></a>');
                 }
               } else {
                 if(modArray[commsy_panel_index] == 'room'){
                    if(is_community_room){
                        modArray[commsy_panel_index] = 'project';
                     }
                  }
                 if (navigator.userAgent.indexOf("MSIE 6.0") == -1){
                    newItem.html('<a href="commsy.php?cid=' + contextID + '&mod=' + modArray[commsy_panel_index] + '&fct=edit&iid=NEW" title="' + new_action_message + '"><img src="images/commsyicons/16x16/new_home_big.png"/></a>');
                 } else {
                   newItem.html('<a href="commsy.php?cid=' + contextID + '&mod=' + modArray[commsy_panel_index] + '&fct=edit&iid=NEW" title="' + new_action_message + '"><img src="images/commsyicons_msie6/16x16/new_home_big.gif"/></a>');
                 }
               }
            } else {
               if (navigator.userAgent.indexOf("MSIE 6.0") == -1){
                  newItem.html('<img src="images/commsyicons/16x16/new_home_big_gray.png" style="cursor:default;" alt="' + new_action_message + '" title="' + new_action_message + '"/>');
               } else {
                 newItem.html('<img src="images/commsyicons_msie6/16x16/new_home_big_gray.gif" style="cursor:default;" alt="' + new_action_message + '" title="' + new_action_message + '"/>');
               }
            }
            newItem.css('width', '18px');
            newItem.css('float', 'right');
            topBar.append(newItem);
         } else {
            if((modArray[commsy_panel_index] != 'user')){
               var img = jQuery('<img/>');
               img.attr('id', 'showHideButton' + commsy_panel_index);
               img.attr('src', 'images/arrow_up.gif');
               img.css('float', 'right');
               klick.append(img);
            }
         }

         topBar.append(klick);

         if(cookieArray[commsy_panel_index]){
            cookieValue = Get_Cookie(cookieArray[commsy_panel_index]);
            if(cookieValue ==1){
               panelDisplayed[commsy_panel_index] = true;
            }else{
               panelDisplayed[commsy_panel_index] = false;
            }
         }

         if(!panelDisplayed[commsy_panel_index]){
            outerContentDiv.hide();
            if (navigator.userAgent.indexOf("MSIE 6.0") == -1){
               contentDiv.css('top', '0px');
            }
            if(contentDiv.attr('id') != 'homeheader'){
               img.attr('src', 'images/arrow_down.gif');
            }
            klick.attr('id', klick.attr('id') + 'down');
            span.attr('id', span.attr('id') + 'down');
            span2.attr('id', span2.attr('id') + 'down');
         } else {
            klick.attr('id', klick.attr('id') + 'up');
            span.attr('id', span.attr('id') + 'up');
            span2.attr('id', span2.attr('id') + 'up');
         }

         topBar.attr('class', 'topBar');
         temp_div.append(topBar);
         temp_div.append(outerContentDiv);
         commsy_panel_index++;

         var childrenSpan = span.children();
         var hasLink = false;
         for(var index=0; index<childrenSpan.length; index++) {
            if(childrenSpan[index].tagName == 'A'){
               hasLink = true;
            }
         }
         if(!hasLink){
            span.click(showHidePaneContentTopBar);
            span.mouseover(mouseoverTopbarBar);
            span.mouseout(mouseoutTopbarBar);
         }

         span2.click(showHidePaneContentTopBar);
         span2.mouseover(mouseoverTopbarBar);
         span2.mouseout(mouseoutTopbarBar);

         klick.click(showHidePaneContentTopBar);
         klick.mouseover(mouseoverTopbarBar);
         klick.mouseout(mouseoutTopbarBar);
      }
   }
}

function showHidePaneContentTopBar(e,inputObj){
   if(!inputObj){
      inputObj = this;
   }
   var numericId = inputObj.id.replace(/[^0-9]/g,'');
   if(jQuery('#showHideButton' + numericId).length > 0){
      var img = jQuery('#showHideButton' + numericId);
   }
   var obj = jQuery('#paneContent' + numericId);
   if(inputObj.id.toLowerCase().indexOf('up')>=0){
      currentlyExpandedPane = false;
      jQuery('#klick' + numericId + 'up').attr('id', jQuery('#klick' + numericId + 'up').attr('id').replace('up','down'));
      jQuery('#span' + numericId + 'up').attr('id', jQuery('#span' + numericId + 'up').attr('id').replace('up','down'));
      jQuery('#spanKlick' + numericId + 'up').attr('id', jQuery('#spanKlick' + numericId + 'up').attr('id').replace('up','down'));
      if(jQuery('#showHideButton' + numericId).length > 0){
         img.attr('src', img.attr('src').replace('up','down'));
      }
      if (navigator.userAgent.indexOf("MSIE") == -1){
        obj.slideUp(200);
      } else {
         if(navigator.userAgent.indexOf("MSIE 6") != -1){
           obj.slideUp(200);
        } else {
           obj.animate({height: "0%", opacity: "0"}, 200);
        }
     }
      if(cookieNames[numericId]){
         Set_Cookie(cookieNames[numericId],'0',100000);
      }
   } else {
      if(this){
         currentlyExpandedPane = this;
      }else{
         currentlyExpandedPane = false;
      }
      jQuery('#klick' + numericId + 'down').attr('id', jQuery('#klick' + numericId + 'down').attr('id').replace('down','up'));
      jQuery('#span' + numericId + 'down').attr('id', jQuery('#span' + numericId + 'down').attr('id').replace('down','up'));
      jQuery('#spanKlick' + numericId + 'down').attr('id', jQuery('#spanKlick' + numericId + 'down').attr('id').replace('down','up'));
      if(jQuery('#showHideButton' + numericId).length > 0){
         img.attr('src', img.attr('src').replace('down','up'));
      }
      if (navigator.userAgent.indexOf("MSIE") == -1){
         obj.slideDown(200);
      } else {
         obj.animate({height: "100%", opacity: 1}, 200);
      }
      if(cookieNames[numericId]){
         Set_Cookie(cookieNames[numericId],'1',100000);
      }
   }
   return true;
}

function mouseoverTopbarBar(){
   var numericId = this.id.replace(/[^0-9]/g,'');
   if(jQuery('#showHideButton' + numericId).length > 0){
      jQuery('#showHideButton' + numericId).attr('src', jQuery('#showHideButton' + numericId).attr('src').replace('.gif','_over.gif'));
   }
   document.body.style.cursor = "pointer";
}

function mouseoutTopbarBar(){
   var numericId = this.id.replace(/[^0-9]/g,'');
   if(jQuery('#showHideButton' + numericId).length > 0){
      jQuery('#showHideButton' + numericId).attr('src', jQuery('#showHideButton' + numericId).attr('src').replace('_over.gif','.gif'));
   }
   document.body.style.cursor = "default";
}

function slidePane(slideValue,id,name){
   if(slideValue!=xpPanel_currentDirection[id]){
      return false;
   }
   var activePane = jQuery('#' + id);
   if(activePane==savedActivePane){
      var subDiv = savedActiveSub;
   }else{
      var subDiv = activePane.children('div:first');
   }
   savedActivePane = activePane;
   savedActiveSub = subDiv;

   var height = activePane.height();
   var innerHeight = subDiv.height();
   height+=slideValue;
   if(height<0){
      height=0;
   }
   if(height>innerHeight){
      height = innerHeight;
   }
   if(document.all){
      activePane.css('filter', 'alpha(opacity=' + Math.round((height / subDiv.height())*100) + ')');
   }else{
      var opacity = (height / subDiv.height());
      if(opacity==0){
         opacity=0.01;
      }
      if(opacity==1){
         opacity = 0.99;
      }
      activePane.css('opacity', opacity);
   }

   if(slideValue<0){
      activePane.css('height', height + 'px');
      subDiv.css('top', height - subDiv.height() + 'px');
      if(height>0){
         setTimeout('slidePane(' + slideValue + ',"' + id + '")',10);
      }else{
         if(document.all){
            activePane.css('display', 'none');
         }
      }
   }else{
      subDiv.css('top', height - subDiv.height() + 'px');
      activePane.css('height', height + 'px');
      if(height<innerHeight){
         setTimeout('slidePane(' + slideValue + ',"' + id + '")',10);
      }
   }
}

function cancelXpWidgetEvent(){
   return false;
}

function Set_Cookie(name,value,expires,path,domain,secure) {
   expires = expires * 60*60*24*1000;
   var today = new Date();
   var expires_date = new Date( today.getTime() + (expires) );
   var cookieString = name + "=" +escape(value) +
          ( (expires) ? ";expires=" + expires_date.toGMTString() : "") +
          ( (path) ? ";path=" + path : "") +
          ( (domain) ? ";domain=" + domain : "") +
          ( (secure) ? ";secure" : "");
   document.cookie = cookieString;
   //jQuery.cookie(escape(value), cookieString);
}

function Get_Cookie(name) {
   var start = document.cookie.indexOf(name+"=");
   var len = start+name.length+1;
   if ((!start) && (name != document.cookie.substring(0,name.length))){
      return null;
   }
   if (start == -1){
      return null;
   }
   var end = document.cookie.indexOf(";",len);
   if (end == -1){
      end = document.cookie.length;
   }
   return unescape(document.cookie.substring(len,end));
}

function initDeleteLayer(){
   jQuery('#delete').css('height', jQuery('body').height() - 140 + "px");
}

function initLayer(id){
   jQuery('#' + id).css('height', jQuery('body').height() + "px");
}

function handleWidth(id,max_width,link_name){
   jQuery(document).ready(function() {
      var inner_div = jQuery('#inner_' + id);
      var width = inner_div.attr("scrollWidth");
      var height = inner_div.attr("scrollHeight");
      if (width > max_width){
         inner_div.css('width', max_width+'px');
         if (navigator.userAgent.indexOf("MSIE") != -1){
            inner_div.css('height', (height+50)+'px');
         }
      }
   });
}

function right_box_send(form_id,option,value) {
  document.getElementById(option).value = value;
  //jQuery('#'.option).val(value);
  document.getElementById(form_id).submit();
  //jQuery('#'.form_id).submit();
}

var context_id;

function cs_toggle_template(){
   var id = jQuery("[name='template_select']");
   jQuery('#template_extention').html(template_array[id.val()]);
   if (id.val() != '-1'){
      showTemplateInformation();
   }
}

function initToggleTemplate(id){
   context_id = id;
   jQuery(document).ready(function() {
      var id = jQuery("[name='template_select']");
      if (id.val() != '-1'){
         jQuery('#template_extention').html(template_array[id.val()]);
         showTemplateInformation();
      }
   });
}

function showTemplateInformation(){
   jQuery('#template_information_box').hide();
   jQuery('#toggle'+context_id).click(function(){
      if(jQuery('#toggle'+context_id).attr('src').toLowerCase().indexOf('less') >= 0){
         jQuery('#template_information_box').slideUp(200);
         jQuery('#toggle'+context_id).attr('src', jQuery('#toggle'+context_id).attr('src').replace('less','more'));
      } else {
         jQuery('#template_information_box').slideDown(200);
         jQuery('#toggle'+context_id).attr('src', jQuery('#toggle'+context_id).attr('src').replace('more','less'));
      }
   });
   jQuery('#toggle'+context_id).mouseover(function(){
      jQuery('#toggle'+context_id).attr('src', jQuery('#toggle'+context_id).attr('src').replace('.gif','_over.gif'));
   });
   jQuery('#toggle'+context_id).mouseout(function(){
      jQuery('#toggle'+context_id).attr('src', jQuery('#toggle'+context_id).attr('src').replace('_over.gif','.gif'));
   });
}

var netnavigation_slide_speed = 50;	// Speed of slide
var rubric_index;
var count_rubrics = new Array();
var path_info = false;

var savedActiveNetnavigationPane = false;
var savedActiveNetnavigationSub = false;
var currentlyExpandedRubric = false;
var netnavigation_currentDirection = new Array();

function initDhtmlNetnavigation(element_id,panelTitles,rubric, item_id){
   var netnavigation = jQuery('#' + element_id + item_id);
   var netnavigation_divs = netnavigation.children('div:first');
   var divs = netnavigation_divs.find('div');
   var end = divs.length;
   rubric_index=0;
   for(var no=0;no<divs.length;no++){
      temp_div = jQuery(divs[no]);
      if(temp_div.attr('class') == element_id + '_panel'){
         var outerContentDiv = jQuery('<div></div>');
         var contentDiv = temp_div.children('div:first');
         outerContentDiv.append(contentDiv);

         outerContentDiv.attr('id', 'rubricContent' + item_id +'_'+ rubric_index);
         outerContentDiv.attr('class', 'panelContent');

         var topBar = jQuery('<div></div>');
         var img = jQuery('<img/>');
         img.attr('id', 'ShowHideRubricButton' + item_id +'_'+ rubric_index);
         img.attr('src', 'images/arrow_netnavigation_up.gif');
         topBar.append(img);

         var span = jQuery('<span></span>');
         span.html(panelTitles[rubric_index].replace(/&COMMSYDHTMLTAG&/g,'</'));
         topBar.append(span);

         topBar.css('position', 'relative');
         img.click(showHideRubricContent);
         img.mouseover(mouseoverTopbar);
         img.mouseout(mouseoutTopbar);
         if(rubric_index != rubric){
            outerContentDiv.hide();
            contentDiv.css('top', 0 - contentDiv.offsetHeight + 'px');
            img.attr('src', 'images/arrow_netnavigation_down.gif');
         }

        topBar.attr('class', 'tpBar');
        temp_div.append(topBar);
        temp_div.append(outerContentDiv);
        rubric_index++;
      }
      count_rubrics[item_id] = rubric_index;
   }
}

function mouseoverTopbar(){
   jQuery(this).attr('src', jQuery(this).attr('src').replace('.gif','_over.gif'));
}

function mouseoutTopbar(){
   jQuery(this).attr('src', jQuery(this).attr('src').replace('_over.gif','.gif'));
}

function showHideRubricContent(e,inputObj){
   if(!inputObj){
      inputObj = jQuery(this);
   }
   var my_array = inputObj.attr('id').split('_');
   var number = my_array[1].replace(/[^0-9]/g,'');
   var number_item_id = my_array[0].replace(/[^0-9]/g,'');
   for(var no=0;no<count_rubrics[number_item_id];no++){
      var img = jQuery('#ShowHideRubricButton' + number_item_id + '_' + no);
      var temp_number_array = img.attr('id').split('_');
      var numericId = temp_number_array[1].replace(/[^0-9]/g,'');
      var obj = jQuery('#rubricContent' + number_item_id + '_' + numericId);
      if(img.attr('src').toLowerCase().indexOf('up') >= 0){
         img.attr('src', img.attr('src').replace('up','down'));
         obj.css('display', 'block');
         obj.hide();
      }else if (number == no){
         img.attr('src', img.attr('src').replace('down','up'));
         obj.css('display', 'block');
         obj.slideDown(200);
      }
   }
   return true;
}

// Flash Player Version Definition for study.log
// -----------------------------------------------------------------------------
// Globals
// Major version of Flash required
var requiredMajorVersion = 9;
// Minor version of Flash required
var requiredMinorVersion = 0;
// Minor version of Flash required
var requiredRevision = 124;
// -----------------------------------------------------------------------------

function getFlashMovie(movieName) {
   var isIE = navigator.appName.indexOf("Microsoft") != -1;
   return (isIE) ? window[movieName] : document[movieName];
}

function callStudyLogSortChronological() {
   getFlashMovie("study_log").callStudyLogSortChronological("");
}
function callStudyLogSortAlphabetical() {
   getFlashMovie("study_log").callStudyLogSortAlphabetical();
}
function callStudyLogSortDefault() {
   getFlashMovie("study_log").callStudyLogSortDefault();
}
function callStudyLogSortByTag(tag) {
   getFlashMovie("study_log").callStudyLogSortByTag(tag);
}
function callStudyLogSortByTagId(tagId) {
   getFlashMovie("study_log").callStudyLogSortByTagId(tagId);
}

jQuery(document).ready(function() {
   if (navigator.userAgent.indexOf("MSIE 6.0") == -1){
	   if(jQuery.datepicker){
		   jQuery.datepicker.regional['de'] = {//clearText: 'löschen',
		                                 //clearStatus: 'aktuelles Datum löschen',
		                                 //closeText: 'schließen',
		                              //closeStatus: 'ohne Änderungen schließen',
		                              //prevText: '',
		                              //prevStatus: 'letzten Monat zeigen',
		                              //nextText: '',
		                              //nextStatus: 'nächsten Monat zeigen',
		                              //currentText: 'heute',
		                              //currentStatus: '',
		                              monthNames: ['Januar','Februar','März','April','Mai','Juni','Juli','August','September','Oktober','November','Dezember'],
		                              monthNamesShort: ['Jan','Feb','Mär','Apr','Mai','Jun','Jul','Aug','Sep','Okt','Nov','Dez'],
		                              //monthStatus: 'anderen Monat anzeigen',
		                              //yearStatus: 'anderes Jahr anzeigen',
		                              //weekHeader: 'Wo',
		                              //weekStatus: 'Woche des Monats',
		                              dayNames: ['Sonntag','Montag','Dienstag','Mittwoch','Donnerstag','Freitag','Samstag'],
		                              dayNamesShort: ['So','Mo','Di','Mi','Do','Fr','Sa'],
		                              dayNamesMin: ['So','Mo','Di','Mi','Do','Fr','Sa'],
		                              //dayStatus: 'Setze DD als ersten Wochentag',
		                              //dateStatus: 'Wähle D, M d',
		                              dateFormat: 'dd.mm.yy',
		                              firstDay: 1,
		                              //initStatus: 'Wähle ein Datum',
		                              isRTL: false};
		   jQuery.datepicker.regional['en'] = {//clearText: 'löschen',
		                                 //clearStatus: 'aktuelles Datum löschen',
		                                 //closeText: 'schließen',
		                                 //closeStatus: 'ohne Änderungen schließen',
		                                 //prevText: '',
		                                 //prevStatus: 'letzten Monat zeigen',
		                                 //nextText: '',
		                                 //nextStatus: 'nächsten Monat zeigen',
		                                 //currentText: 'heute',
		                                 //currentStatus: '',
		                                 //monthNames: ['Januar','Februar','März','April','Mai','Juni','Juli','August','September','Oktober','November','Dezember'],
		                                 //monthNamesShort: ['Jan','Feb','Mär','Apr','Mai','Jun','Jul','Aug','Sep','Okt','Nov','Dez'],
		                                 //monthStatus: 'anderen Monat anzeigen',
		                                 //yearStatus: 'anderes Jahr anzeigen',
		                                 //weekHeader: 'Wo',
		                                 //weekStatus: 'Woche des Monats',
		                                 //dayNames: ['Sonntag','Montag','Dienstag','Mittwoch','Donnerstag','Freitag','Samstag'],
		                                 //dayNamesShort: ['So','Mo','Di','Mi','Do','Fr','Sa'],
		                                 //dayNamesMin: ['So','Mo','Di','Mi','Do','Fr','Sa'],
		                                 //dayStatus: 'Setze DD als ersten Wochentag',
		                                 //dateStatus: 'Wähle D, M d',
		                                 dateFormat: 'mm/dd/yy',
		                                 firstDay: 1,
		                                 //initStatus: 'Wähle ein Datum',
		                                 isRTL: false};
		   if(window.datepicker_language !== undefined && datepicker_language == 'de'){
		      jQuery.datepicker.setDefaults($.datepicker.regional['de']);
		   } else {
		     jQuery.datepicker.setDefaults($.datepicker.regional['en']);
		   }
		   if (navigator.userAgent.indexOf("MSIE 6.0") == -1){
		      datepicker_image = 'images/commsyicons/datepicker.png';
		   } else {
		      datepicker_image = 'images/commsyicons/datepicker.gif';
		   }
		   if(jQuery("input[name='dayStart']").length){
		      jQuery("input[name='dayStart']").datepicker({showOn: 'button', buttonImage: datepicker_image, buttonImageOnly: true, buttonText: datepicker_choose});
		   }
		   if(jQuery("input[name='dayEnd']").length){
		      jQuery("input[name='dayEnd']").datepicker({showOn: 'button', buttonImage: datepicker_image, buttonImageOnly: true, buttonText: datepicker_choose});
		   }
		   if(jQuery("input[name='dayActivateStart']").length){
		      jQuery("input[name='dayActivateStart']").datepicker({showOn: 'button', buttonImage: datepicker_image, buttonImageOnly: true, buttonText: datepicker_choose});
		   }
		   if(jQuery("input[name='recurring_end_date']").length){
			  if(!jQuery("input[name='recurring_end_date']").attr('disabled')){
			     jQuery("input[name='recurring_end_date']").datepicker({showOn: 'button', buttonImage: datepicker_image, buttonImageOnly: true, buttonText: datepicker_choose});
		  	  }
		   }
	   }
   }
});

jQuery(document).ready(function() {
	jQuery("a[name^='calendar_link']").hover(
		function() {
			jQuery(this).next("em").animate({opacity: "show", top: "-75"}, "slow");
		}, function() {
			jQuery(this).next("em").animate({opacity: "hide", top: "-85"}, "fast");
		});
});

var scrollbar_width = 12;

jQuery(document).ready(function() {
	if(jQuery('#calender_main').length){
		jQuery('#calender_main').jScrollPane({scrollbarWidth: scrollbar_width});
		jQuery('.jScrollPaneContainer').css('border-top', '1px solid #aaaaaa');
		jQuery('#calender_frame').css('background-color',jQuery('[id^=calendar_head]').css('background-color'));
		addNewDateLinks();
		resize_calendar();
		draw_dates();
		jQuery('#calender_main')[0].scrollTo(321);
		$(window).resize(function(){
			//jQuery('#calender_frame').css('width', '100%');
			//jQuery('#calender_main').css('width', '100%');
			//jQuery('jScrollPaneContainer').width('100%');
			resize_calendar();
			draw_dates();
		});
	}
	if(jQuery('#calender_month_frame').length){
		resize_calendar_month();
		draw_dates_month();
		$(window).resize(function(){
			resize_calendar_month();
			draw_dates_month();
		});
	}
});

function resize_calendar(){
	var frame_width = jQuery('#calender_frame').width();
	var main_width = frame_width - scrollbar_width;
	var time_width = main_width * 0.02;
	var time_width_array = time_width.toString().split('.');
	time_width = time_width_array[0];
	var entry_width = main_width * 0.14;
	var entry_width_array = entry_width.toString().split('.');
	entry_width = entry_width_array[0];
	jQuery('#calender_main').css({width: frame_width - scrollbar_width+'px'});
	jQuery('.calender_hour').css({width: frame_width - scrollbar_width+'px'});
	jQuery('.calendar_time').css({width: time_width+'px'});
	jQuery('.calendar_time_day').css({width: time_width+'px'});
	jQuery('.calendar_time_head').css({width: time_width+'px'});
	jQuery('[id^=calendar_entry]').css({width: entry_width+'px'});
	jQuery('[id^=calendar_head]').css({width: entry_width+'px'});
	getSwitchIconBar(frame_width);
}

function resize_calendar_month(){
	var frame_width = jQuery('#calender_month_frame').width();
	var frame_rest = frame_width % 7;
	jQuery('#calender_month_frame').css({width: (frame_width - frame_rest) +'px'});
	var entry_width = Math.floor(frame_width / 7)-1;
	jQuery('.calendar_month_entry_head').css({width: entry_width +'px'});
	jQuery('.calendar_month_entry').css({width: entry_width +'px'});
	jQuery('.calender_month_footer').css({width: (frame_width - frame_rest) - 1 +'px'});
	getSwitchIconBar(frame_width - frame_rest);
}

function getSwitchIconBar(frame_width){
	if(jQuery('#calendar_switch').width() >= jQuery('#calendar_calendarweek').width()){
		var add_width = jQuery('#calendar_switch').width() - jQuery('#calendar_calendarweek').width();
		jQuery('#switch_float').css('float', 'right');
	} else {
		var add_width = jQuery('#calendar_calendarweek').width() - jQuery('#calendar_switch').width();
		jQuery('#switch_float').css('float', 'left');
	}
	var switch_icon_bar_width = jQuery('#switch_icon_bar_today').width() + jQuery('#switch_icon_bar_week').width() + jQuery('#switch_icon_bar_month').width() + add_width ; // +40 -> padding
	jQuery('#switch_icon_bar').css({width: switch_icon_bar_width+'px'});
	if(jQuery('#calender_month_frame').length){
	   jQuery('#switch_top_bar').css({width: frame_width});
	}
	if(jQuery('#calender_frame').length){
      jQuery('#switch_top_bar').css({width: frame_width});
   }
}

function draw_dates(){
	jQuery('div[name=calendar_date]').remove();
	var left_position = 0;
	var current_day = '';
	var tooltip_added = false;
	var top_date_for_whole_day = 0;
	var height_day = 40;
	if(typeof(calendar_dates) != 'undefined'){
		for (var i = 0; i < calendar_dates.length; i++) {
			var day = calendar_dates[i][0]-1;
			if(current_day != day){
				current_day = day;
				left_position = 0;
				top_date_for_whole_day = 0;
			}
			if(i+1 < calendar_dates.length){
				var next_day = calendar_dates[i+1][0]-1;
			} else {
				var next_day = 'none';
			}
			var title = calendar_dates[i][1];
			var start_quaters = calendar_dates[i][2];
			var end_quaters = calendar_dates[i][3];
			var dates_on_day = calendar_dates[i][4];
			var color = calendar_dates[i][5];
			var color_border = calendar_dates[i][6];
			var href = calendar_dates[i][7];
			var tooltip = calendar_dates[i][8];
			var is_date_for_whole_day = calendar_dates[i][9];
			var max_overlap = calendar_dates[i][10];
			var start_column = calendar_dates[i][11];
			//var start_quarter = calendar_dates[i][12];
			var max_overlap_for_date = calendar_dates[i][13];

			if(start_quaters % 4 == 0){
				var start_div = start_quaters / 4;
				var top = 0;
			} else {
				var start_div = (start_quaters - start_quaters % 4) / 4;
				var top = (start_quaters % 4) * 10;
			}

			top = top+1;

			if(end_quaters != 0){
				var height = (end_quaters - start_quaters) * 10; // + ((end_quaters - start_quaters) / 4)-1;
			} else {
				var height = 40;
			}

			var start_spacer = 0;
			var end_spacer = 0;
			var between_space = (dates_on_day -1) * 0;
			var width_div = jQuery('#calendar_entry_date_div_' + start_div + '_'+day).width()-1;
			var width = width_div / max_overlap; // - (start_spacer + end_spacer) - between_space;
			if(max_overlap_for_date == 1){
			   width = width_div;
			}

			var left = (width * start_column)+1;// + ((left_position+1) * 2);

			// Ausgleich border
			width = width-2;
			height = height-2-1;

		    if(is_date_for_whole_day){
		      top_date_for_whole_day++;
		    	width = width_div - 2;
		    	left = 1;
		    	if(top_date_for_whole_day > 1){
		    	   top = 19 * (top_date_for_whole_day - 1);
		    	   var height_day_temp = 40 + ((top_date_for_whole_day - 2) * 19)-1;
		    	}
		    	if(height_day_temp > height_day){
		    	  height_day = height_day_temp;
		    	}
		    	jQuery('[class=calendar_time_day]').css('height', height_day);
		    	jQuery('[class=calendar_entry_day]').css('height', height_day);
		    	jQuery('#calendar_entry_date_div_'+day).prepend('<div name="calendar_date" style="position:absolute; top:' + (top) + 'px; left:' + left + 'px; height: 18px; width:' + width + 'px; background-color:' + color + '; z-index:1000; overflow:hidden; border:1px solid ' + color_border + ';"><div style="width:1000px; text-align:left; position:absolute; top:0px; left:0px;">' + title + '</div><div style="position:absolute; top:0px; left:0px; height:100%; width:100%;" data-tooltip="' + tooltip + '"><a href="' + href + '"><img src="images/spacer.gif" style="height:100%; width:100%;"/></a></div></div>');
		    } else {
		    	jQuery('#calendar_entry_date_div_' + start_div + '_'+day).prepend('<div name="calendar_date" style="position:absolute; top:' + top + 'px; left:' + left + 'px; height:' + height + 'px; width:' + width + 'px; background-color:' + color + '; z-index:1000; overflow:hidden; border:1px solid ' + color_border + ';"><div style="width:1000px; text-align:left; position:absolute; top:0px; left:0px;">' + title + '</div><div style="position:absolute; top:0px; left:0px; height:100%; width:100%;"><a href="' + href + '" data-tooltip="' + tooltip + '"><img src="images/spacer.gif" style="height:100%; width:100%;"/></a></div></div>');
		    }
	    }
		stickytooltip.init("*[data-tooltip]", "mystickytooltip");
	}
	if(typeof(today) != 'undefined'){
		if(today != ''){
			var today_color = '#f2e4b6';
			var today_color_work = '#fff0c0';
			var today_color_day = '#f2e4b6';
			jQuery.each(jQuery('[id^=calendar_head]'), function(){
				if((jQuery(this).attr('id').indexOf(today)) != -1){
					//jQuery(this).css('background-color', today_color);
					var today_array = jQuery(this).attr('id').split('_');
					var today_index = today_array[2];
					jQuery('#calendar_entry_' + today_index).css('background-color', today_color_day);
					for ( var index = 0; index <= 23; index++) {
						if((index < 8) || (index > 15)){
							jQuery('#calendar_entry_' + index + '_' + today_index).css('background-color', today_color);
						} else {
							jQuery('#calendar_entry_' + index + '_' + today_index).css('background-color', today_color_work);
						}
					}
				}
			});
		}
	}
}

function draw_dates_month(){
	if(typeof(calendar_dates) != 'undefined'){
		var top_position = 0;
		var current_date = '';
		var added_scrollpane = false;
		var added_jscrollpane = false;
		var max_dates_on_day = 0;
		for (var i = 0; i < calendar_dates.length; i++) {
			if(max_dates_on_day < calendar_dates[i][3]){
				max_dates_on_day = calendar_dates[i][3];
			}
		}
		if(max_dates_on_day > 4){
			var height= 101 + ((max_dates_on_day - 4) * 21);
			jQuery('[class=calendar_month_entry]').css('height', height);
		}
		for (var i = 0; i < calendar_dates.length; i++) {
			var day = calendar_dates[i][0];
			var month = calendar_dates[i][1];
			var title = calendar_dates[i][2];
			var dates_on_day = calendar_dates[i][3];
			var color = calendar_dates[i][4];
			var color_border = calendar_dates[i][5];
			var href = calendar_dates[i][6];
			var tooltip = calendar_dates[i][7];
			if(day+month != current_date){
				current_date = day+month
				top_position = 0;
				//added_scrollpane = false;
				//added_jscrollpane = false;
				jQuery('#calendar_month_entry_' + day + '_' + month).append('<div id="calendar_month_entry_' + day + '_' + month + '_scroll" style="position:absolute; top:18px; left:0px; height:62px;"></div>');
				if(dates_on_day >= 4){
					//jQuery('#calendar_month_entry_' + day + '_' + month + '_scroll').jScrollPane({scrollbarWidth: scrollbar_width});
				}
			}
			//if(!added_scrollpane){
			//	jQuery('#calendar_month_entry_' + day + '_' + month).append('<div id="calendar_month_entry_' + day + '_' + month + '_scroll" style="position:absolute; top:18px; left:0px; height:10px; background.color:red;"></div>');
			//	added_scrollpane = true;
			//}
			//if(dates_on_day >= 4 && !added_jscrollpane){
			//	jQuery('#calendar_month_entry_' + day + '_' + month + '_scroll').jScrollPane();
			//	added_jscrollpane = true;
			//}
			var top = (20 * top_position) + (1 * top_position);
			var width = jQuery('#calendar_month_entry_' + day + '_' + month).width() -2;
			jQuery('#calendar_month_entry_' + day + '_' + month + '_scroll').append('<div style="position: absolute; top:' + top + 'px; left:0px; width:' + width + 'px; height:18px; background-color:' + color + '; border:1px solid ' + color_border + '; overflow:hidden;"><div style="position:absolute; top:0px; left:0px; width:1000px;">' + title + '</div><div style="position:absolute; top:0px; left:0px; height:18px; width:100%; z-index:10000;"><a href="' + href + '" data-tooltip="' + tooltip + '"><img src="images/spacer.gif" style="height:100%; width:100%;"/></a></div></div>')
			top_position++;
			//if(dates_on_day >= 4 && !added_scrollpane){
			//	jQuery('#calendar_month_entry_' + day + '_' + month + '_scroll').jScrollPane();
			//	added_scrollpane = true;
			//}
		}
		//jQuery('#calendar_month_entry_1_12_scroll').jScrollPane();
		stickytooltip.init("*[data-tooltip]", "mystickytooltip");
	}
	if(typeof(today) != 'undefined'){
      if(today != ''){
         var today_color = '#fff0c0'
         var day = today.substring(0,2);
         if(day.substring(0,1) == 0){
            day = day.substring(1,2);
         }
         var month = today.substring(2,4);
         if(month.substring(0,1) == 0){
        	 month = month.substring(1,2);
         }
         jQuery('#calendar_month_entry_' + day + '_' + month).css('background-color', today_color);
      }
   }
}

function addNewDateLinks(){
	if(typeof(new_dates) != 'undefined'){
		for (var i = 0; i < new_dates.length; i++) {
			jQuery(new_dates[i][0]).append(new_dates[i][1]);
		}
	}
}

/* Parameter
   accept: 'groupItem',
   helperclass: 'sortHelper',
   activeclass : 	'sortableactive',
   hoverclass : 	'sortablehover',
   handle: 'div.itemHeader',
   tolerance: 'pointer',
   onChange : function()
*/
jQuery(document).ready(function() {
		jQuery(".column").sortable({
			connectWith: '.column',
			handle: 'div.portlet-header',
		    tolerance: 'pointer',
		    update: function(event, ui) {
			    var json_data = new Object();
			    var portlet_columns = jQuery(".column");
			    for ( var int = 0; int < portlet_columns.length-1; int++) {
			    	column_portlets = new Array();
					var portlet_column = jQuery(portlet_columns[int]);
					portlets = portlet_column.children();
					for ( var int2 = 0; int2 < portlets.length; int2++) {
						var portlet = jQuery(portlets[int2]);
						column_portlets.push(portlet.find('.portlet-content').find('div').attr('id'));
					}
					json_data['column_'+int] = column_portlets;
				}
			    jQuery.ajax({
			       //type: 'POST', // -> funktioniert nicht, Parameter als GET (Standard - keine Angabe notwendig) an den Server weiterleiten!
				   url: 'commsy.php?cid='+window.ajax_cid+'&mod=ajax&fct=privateroom_home&output=json&do=save_config',
				   data: json_data,
				   success: function(msg){
				   }
				});
			}
		});

		jQuery(".portlet").addClass("ui-widget ui-widget-content ui-helper-clearfix ui-corner-all")
			.find(".portlet-header")
				.addClass("ui-widget-header ui-corner-all")
//				.prepend('<span class="ui-icon ui-icon-minusthick"></span>')
				.end()
			.find(".portlet-content");

	
//		$(".portlet-header .ui-icon").click(function() {
//			$(this).toggleClass("ui-icon-minusthick").toggleClass("ui-icon-plusthick");
//			$(this).parents(".portlet:first").find(".portlet-content").toggle();
//		});

		jQuery(".column").disableSelection();
});

jQuery(document).ready(function() {
	if(jQuery('[id^=tag_tree]').length){
		jQuery.ui.dynatree.nodedatadefaults["icon"] = false;
		jQuery('[id^=tag_tree]').each(function(){
			jQuery(this).dynatree({
				fx: { height: "toggle", duration: 200 },
				checkbox: true,
				onActivate: function(dtnode){
					if( dtnode.data.url ){
						window.location(dtnode.data.url);
					}
					if( dtnode.data.StudyLog ){
						callStudyLogSortByTagId(dtnode.data.StudyLog);
					}
				},
				onSelect: function(select, dtnode){
					if( dtnode.data.checkbox ){
						jQuery("[#taglist_" + dtnode.data.checkbox).attr('checked', select);
					}
				}
			});
			var max_visible_nodes = 20;
			var max_expand_level = getExpandLevel(jQuery(this), max_visible_nodes);
			jQuery(this).dynatree("getRoot").visit(function(dtnode){
				if(dtnode.getLevel() < max_expand_level){
					dtnode.expand(true);
				}
				if( !dtnode.data.checkbox ){
					dtnode.data.hideCheckbox = true;
					dtnode.render(true);
				} else {
					dtnode.select(jQuery("[#taglist_" + dtnode.data.checkbox).attr('checked'));
				}
			});
			if(jQuery(this).attr('name') == 'tag_tree_detail'){
				collapseTree(jQuery(this).dynatree("getRoot"), true);
			}
		});
	}
});

function collapseTree(node, is_root){
	var collapse = true;
	if(node.childList != null){
		for (var int = 0; int < node.childList.length; int++) {
			var result = collapseTree(node.childList[int], false);
			if(!result){
				collapse = false;
			}
		}
	}
	if(!is_root){
		if(collapse){
			node.expand(false);
		} else {
			node.expand(true);
		}
		if(typeof(node.data.url) !== 'undefined' && node.data.url != null) {
			if(node.data.url.indexOf('name=selected') > -1){
				collapse = false;
			}
		}
	}
	return collapse;
}

function getExpandLevel(tree, maxVisible){
	var return_counter = 1;
	var counter = 0;
	var level = 0;
	tree.dynatree("getRoot").visit(function(dtnode){
		counter++;
		if(dtnode.getLevel() > level){
			level = dtnode.getLevel();
		}
	});
	var return_level = level;
	if(counter > maxVisible){
		for (var max_level = level; max_level > 0; max_level--) {
			return_counter = 0;
			tree.dynatree("getRoot").visit(function(dtnode){
				if(dtnode.getLevel() <= max_level){
					return_counter++;
				}
			});
			if(return_counter <= maxVisible){
				return_level = max_level;
				break;
			}
		}
	}
	return return_level;
}

jQuery(document).ready(function() {
	if(jQuery('#additional_calendar').length){
	   jQuery('#additional_calendar').datepicker({
	      onSelect: function(dateText, inst) {
		     if(typeof(additional_calendar_href) !== 'undefined' && additional_calendar_href != '') {
		        if(window.datepicker_language !== undefined && window.datepicker_language == 'de'){
		        	var day = dateText.substring(0,2);
			        var month = dateText.substring(3,5);
			        var year = dateText.substring(6,10);
				} else {
					var day = dateText.substring(3,5);
			        var month = dateText.substring(0,2);
			        var year = dateText.substring(6,10);
				}
		        if(presentation_mode == 1){
		        	var temp_date = new Date (year, month-1, day);
		        	var dayOfWeek = temp_date.getDay(); // Mo = 1 ... So = 0
		        	if(dayOfWeek != 0){
		        		offset = (dayOfWeek - 1) * (3600 * 24);
		        	} else {
		        		offset = 6 * (3600 * 24);
		        	}
		        	var date = (temp_date.getTime() / 1000) - offset;
		        } else if(presentation_mode == 2) {
		        	var date =  year + '' + month + '' + day;
		        }
		        window.location.href = additional_calendar_href + '' + date;
		     }
		  },
		  changeMonth: true,
		  changeYear: true
	   });
	}
});

jQuery(document).ready(function() {
	if(typeof(dropDownMenus) !== 'undefined'){
		if(dropDownMenus.length){
			// how many different menus?
			var dropdown_menus = new Array();
			for ( var int = 0; int < dropDownMenus.length; int++) {
				var tempDropDownMenu = dropDownMenus[int];
				var tempImage = tempDropDownMenu[0];
				var in_array = false;
				for ( var int2 = 0; int2 < dropdown_menus.length; int2++) {
					var array_element = dropdown_menus[int2];
					if(array_element == tempImage){
						in_array = true;
					}
				}
				if(!in_array){
					dropdown_menus.push(tempImage);
				}
			}

			// sort menu_entries to menus
			for ( var int3 = 0; int3 < dropdown_menus.length; int3++) {
				var current_menu = dropdown_menus[int3];

				var tempImage = current_menu;
				var disabled = false;
				if (jQuery('#'+tempImage).length){
					var image = jQuery('#'+tempImage);
				} else if (jQuery('#'+tempImage+'_disabled').length){
					var image = jQuery('#'+tempImage+'_disabled');
					disabled = true;
				}
				image.attr('id',image.attr('id')+'_dropdown_menu_'+int3);
				image.attr('alt','');
				image.parent().attr('title','');

				var button = jQuery('<img id="dropdown_button_'+int3+'" src="images/commsyicons/dropdownmenu.png" />');

				var html = jQuery('<div id="dropdown_menu_'+int3+'" class="dropdown_menu"></div>');
				var offset = image.offset();

				var ul = jQuery('<ul></ul>');

				for ( var int4 = 0; int4 < dropDownMenus.length; int4++) {
					var temp_menu_entry = dropDownMenus[int4];
					if(temp_menu_entry[0] == current_menu){
						if(temp_menu_entry[1] != 'seperator'){
							var tempActionImage = temp_menu_entry[1];
							var tempActionText = temp_menu_entry[2];
							var tempActionHREF = temp_menu_entry[3];
							ul.append('<li class="dropdown"><a href="'+tempActionHREF+'"><img src="'+tempActionImage+'" style="vertical-align:middle; padding-right:2px;" />'+tempActionText+'</a></li>');
						} else {
							ul.append('<li class="dropdown_seperator"><hr class="dropdown_seperator"></li>');
						}
					}
				}

				html.append(ul);
				image.parent().wrap('<div style="display:inline;"></div>');
				image.parent().parent().append(button);
				image.parent().parent().append(html);

				image.mouseover(function(){
					var id = this.id;
					var this_image = this;
					this_image.mouse_is_over = true;
					setTimeout(function() {
						if(this_image.mouse_is_over){
							var id_parts = id.split('_');
							var offset = jQuery('#dropdown_button_'+id_parts[4]).parent().offset();
							dropdown(jQuery('#dropdown_menu_'+id_parts[4]), offset);
						}
					}, 2000);
				});

				image.mouseout(function(){
					this.mouse_is_over = false;
				});

				jQuery('#dropdown_button_'+int3).click(function(){
					var id_parts = this.id.split('_');
					var offset = jQuery('#'+this.id).parent().offset();
					dropdown(jQuery('#dropdown_menu_'+id_parts[2]), offset);
				});

				jQuery('#dropdown_button_'+int3).mouseover(function(){
					var id = this.id;
					var this_image = this;
					this_image.mouse_is_over = true;
					setTimeout(function() {
						if(this_image.mouse_is_over){
							var id_parts = id.split('_');
							var offset = jQuery('#dropdown_button_'+id_parts[2]).parent().offset();
							dropdown(jQuery('#dropdown_menu_'+id_parts[2]), offset);
						}
					}, 2000);
				});

				jQuery('#dropdown_button_'+int3).mouseout(function(){
					this.mouse_is_over = false;
				});

				jQuery(document).mousedown(function(event) {
					var target = jQuery(event.target);
					var parents = target.parents();
					var has_class = false;
					for ( var int3 = 0; int3 < parents.length; int3++) {
						var parent = parents[int3];
						if(jQuery(parent).hasClass('dropdown_menu')){
							has_class = true;
						}
					}
					if(!target.hasClass('dropdown_menu') && !has_class){
						jQuery("[id^='dropdown_menu_']").slideUp(150);
					}
				});
			}
		}
	}
});

function dropdown(object, offset){
	object.css('top', offset.top + 18);
	object.css('left', offset.left - 3);
	if(object.css('display') == 'none'){
		object.slideDown(150);
	} else if(object.css('display') == 'block'){
		object.slideUp(150);
	}
	object.mouseleave(function(){
		setTimeout(function() {
			object.slideUp(150);
		}, 2000);
	});
}
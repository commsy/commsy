

function getQueryParams(qs) {
    qs = qs.split("+").join(" ");
    var params = {};
    var tokens;

    while (tokens = /[?&]?([^=]+)=([^&]*)/g.exec(qs)) {
        params[decodeURIComponent(tokens[1])]
            = decodeURIComponent(tokens[2]);
    }
    return params;
}

function getURLParam(name) {
  name = name.replace(/[\[]/,"\\\[").replace(/[\]]/,"\\\]");
  var regexS = "[\\?&]"+name+"=([^&#]*)";
  var regex = new RegExp( regexS );
  var results = regex.exec( window.location.href );
  if( results == null )
    return "";
  else
    return results[1];
}

jQuery(document).ready(function() {
  		jQuery(".dragable_item").draggable({
  		   cursor:"move",
  		   opacity: 0.7,
  		   helper: function(event) {
  		      var img = jQuery("#" + this.id + "_img").html();
  		      var new_img = img.replace('16x16','32x32');
				return new_img;
			},
  		   cursorAt: { cursor: 'crosshair', top: 22, left: 22 }
  		   });
      jQuery(".droppable_list").droppable({
			hoverClass: 'droppable_item_hover',
			drop: function(event, ui) {
				 //var $_GET = getQueryParams(document.location.search);
				 var json_data = new Object();
			    json_data['action'] = 'add_item';
			    var mylistId = this.id.replace(/[^0-9]/g,'');
			    var itemId = ui.draggable[0].id.replace(/[^0-9]/g,'');
			    json_data['mylist_id'] = mylistId;
			    json_data['item_id'] = itemId;
			    jQuery.ajax({
				   url: 'commsy.php?cid='+window.ajax_cid+'&mod=ajax&fct=privateroom_entry&output=json&do=update_mylist',
				   data: json_data,
				   success: function(msg){
			    	   var resultJSON = eval('(' + msg + ')');
                  if (resultJSON === undefined){
                  }else{
                      var text = '<span id="mylist_count_' + mylistId +'">' + resultJSON[itemId] + '</span>';
                      jQuery("#mylist_count_"+mylistId).replaceWith(text);
                  }
 				   }
				});
			}
		});
      jQuery(".droppable_matrix").droppable({
			hoverClass: 'droppable_item_hover',
			drop: function(event, ui) {
			   drop_to_matrix(this.id, ui.draggable[0].id.replace(/[^0-9]/g,''));
			}
		});
      jQuery(".droppable_buzzword").droppable({
			hoverClass: 'droppable_item_hover',
			drop: function(event, ui) {
				 //var $_GET = getQueryParams(document.location.search);
				 var json_data = new Object();
			    json_data['action'] = 'add_item';
			    var buzzwordId = this.id.replace(/[^0-9]/g,'');
			    var itemId = ui.draggable[0].id.replace(/[^0-9]/g,'');
			    json_data['buzzword_id'] = buzzwordId;
			    json_data['item_id'] = itemId;
			    jQuery.ajax({
				   url: 'commsy.php?cid='+window.ajax_cid+'&mod=ajax&fct=privateroom_entry&output=json&do=update_buzzwords',
				   data: json_data,
				   success: function(msg){
			    	   var resultJSON = eval('(' + msg + ')');
                  if (resultJSON === undefined){
                  }else{
                      jQuery("#buzzword_"+buzzwordId).css('font-size',resultJSON[itemId]+"px");
                  }
 				   }
				});
			}
		});
	});



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
   if(typeof(reset_search_text_message) !== 'undefined'){
      if(jQuery('#' + id).val() == reset_search_text_message){
         jQuery('#' + id).val("");
      }
   } else {
      jQuery('#' + id).val("");
   }
}

function resetSearchTextEntries(id){
   if(typeof(reset_search_text_message_entries) !== 'undefined'){
      if(jQuery('#' + id).val() == reset_search_text_message_entries){
         jQuery('#' + id).val("");
      }
   } else {
      jQuery('#' + id).val("");
   }
}

function resetSearchTextMatrixColumn(id){
   if(typeof(new_column_message) !== 'undefined'){
      if(jQuery('#' + id).val() == new_column_message){
         jQuery('#' + id).val("");
      }
   } else {
      jQuery('#' + id).val("");
   }
}

function resetSearchTextMatrixRow(id){
   if(typeof(new_row_message) !== 'undefined'){
      if(jQuery('#' + id).val() == new_row_message){
         jQuery('#' + id).val("");
      }
   } else {
      jQuery('#' + id).val("");
   }
}

function resetMyListText(id){
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
		   if(jQuery("input[name='workflow_resubmission_date']").length){
			  jQuery("input[name='workflow_resubmission_date']").datepicker({showOn: 'button', buttonImage: datepicker_image, buttonImageOnly: true, buttonText: datepicker_choose});
		   }
		   if(jQuery("input[name='workflow_validity_date']").length){
			  jQuery("input[name='workflow_validity_date']").datepicker({showOn: 'button', buttonImage: datepicker_image, buttonImageOnly: true, buttonText: datepicker_choose});
		   }
		   if(jQuery("input[name='document_release_date']").length){
			  jQuery("input[name='document_release_date']").datepicker({showOn: 'button', buttonImage: datepicker_image, buttonImageOnly: true, buttonText: datepicker_choose});
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
//		$(window).resize(function(){
//			//jQuery('#calender_frame').css('width', '100%');
//			//jQuery('#calender_main').css('width', '100%');
//			//jQuery('jScrollPaneContainer').width('100%');
//			resize_calendar();
//			draw_dates();
//		});
	}
	if(jQuery('#calender_month_frame').length){
		resize_calendar_month();
		draw_dates_month();
//		$(window).resize(function(){
//			resize_calendar_month();
//			draw_dates_month();
//		});
	}

	// set commsy body to a fixed size
	var body_width = jQuery('[class=commsy_body]').width();
	jQuery('[class=commsy_body]').css('width', body_width);
	jQuery('[class=commsy_footer]').css('width', body_width);
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
				current_date = day+month;
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
			jQuery('#calendar_month_entry_' + day + '_' + month + '_scroll').append('<div style="position: absolute; top:' + top + 'px; left:0px; width:' + width + 'px; height:18px; background-color:' + color + '; border:1px solid ' + color_border + '; overflow:hidden;"><div style="position:absolute; top:0px; left:0px; width:1000px;">' + title + '</div><div style="position:absolute; top:0px; left:0px; height:18px; width:100%; z-index:10000;"><a href="' + href + '" data-tooltip="' + tooltip + '"><img src="images/spacer.gif" style="height:100%; width:100%;"/></a></div></div>');
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
         var today_color = '#fff0c0';
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
			    //if(window.ajax_function == 'privateroom_home'){
				    var json_data = new Object();
				    var portlet_columns = jQuery(".column");
				    for ( var int = 0; int < portlet_columns.length; int++) {
				    	var column_portlets = new Array();
						var portlet_column = jQuery(portlet_columns[int]);
						var portlets = portlet_column.children();
						for ( var int2 = 0; int2 < portlets.length; int2++) {
							var portlet = jQuery(portlets[int2]);
							if(window.ajax_function == 'privateroom_home'){
								column_portlets.push(portlet.find('.portlet-content').find('div').attr('id'));
							} else if (window.ajax_function == 'privateroom_myroom') {
								column_portlets.push(portlet.find('.portlet-header').attr('id'));
							} else if (window.ajax_function == 'privateroom_myentries') {
								column_portlets.push(portlet.find('.portlet-header').parent().attr('id'));
							} else if (window.ajax_function == 'privateroom_mycalendar') {
								column_portlets.push(portlet.find('.portlet-header').attr('id'));
							}
						}
						if(column_portlets.length == 0){
							column_portlets[0] = 'empty';
						}
						json_data['column_'+int] = column_portlets;
					}
			    //} else if (window.ajax_function == 'privateroom_myroom'){
			    //
			    //}
			    jQuery.ajax({
			       //type: 'POST', // -> funktioniert nicht, Parameter als GET (Standard - keine Angabe notwendig) an den Server weiterleiten!
				   url: 'commsy.php?cid='+window.ajax_cid+'&mod=ajax&fct='+window.ajax_function+'&output=json&do=save_config',
				   data: json_data,
				   success: function(msg){
			    	//alert(msg);
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

		//jQuery(".column").disableSelection();
});

jQuery(document).ready(function() {
	jQuery("[name=myentries_remove]").each(function (i) {
		var id = jQuery(this).parent().parent().parent().attr('id');
		jQuery(this).click(function() {
			jQuery('#'+id).remove();
			jQuery('#'+id+'_preferences').remove();

			// Haken im DropDown-Menu entfernen!
			jQuery('[name=myentries]:checked').each(function(){
				if(id == jQuery(this).attr('value')){
					jQuery(this).attr('checked', false);
				}
			});

			var json_data = new Object();
		    var portlet_columns = jQuery(".column");
		    for ( var int = 0; int < portlet_columns.length; int++) {
		    	var column_portlets = new Array();
				var portlet_column = jQuery(portlet_columns[int]);
				var portlets = portlet_column.children();
				for ( var int2 = 0; int2 < portlets.length; int2++) {
					var portlet = jQuery(portlets[int2]);
					if(window.ajax_function == 'privateroom_home'){
						column_portlets.push(portlet.find('.portlet-content').find('div').attr('id'));
					} else if (window.ajax_function == 'privateroom_myroom') {
						column_portlets.push(portlet.find('.portlet-header').attr('id'));
					} else if (window.ajax_function == 'privateroom_myentries') {
						column_portlets.push(portlet.find('.portlet-header').parent().attr('id'));
					}
				}
				if(column_portlets.length == 0){
					jQuery('#myentries_left').remove();
					jQuery('#myentries_right').css('width', '100%');
				}
				json_data['column_'+int] = column_portlets;
			}

			jQuery.ajax({
		       url: 'commsy.php?cid='+window.ajax_cid+'&mod=ajax&fct='+window.ajax_function+'&output=json&do=save_config',
			   data: json_data,
			   success: function(msg){
			   }
			});
		});
	});
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
							if(jQuery('#dropdown_menu_'+id_parts[4]).css('display') == 'none'){
								dropdown(jQuery('#dropdown_menu_'+id_parts[4]), offset, id_parts[4]);
							}
						}
					}, 2000);
				});

				image.mouseout(function(){
					this.mouse_is_over = false;
				});

				jQuery('#dropdown_button_'+int3).click(function(){
					var id_parts = this.id.split('_');
					var offset = jQuery('#'+this.id).parent().offset();
					dropdown(jQuery('#dropdown_menu_'+id_parts[2]), offset, id_parts[2]);
				});

				jQuery('#dropdown_button_'+int3).mouseover(function(){
					var id = this.id;
					var this_image = this;
					this_image.mouse_is_over = true;
					setTimeout(function() {
						if(this_image.mouse_is_over){
							var id_parts = id.split('_');
							var offset = jQuery('#dropdown_button_'+id_parts[2]).parent().offset();
							if(jQuery('#dropdown_menu_'+id_parts[2]).css('display') == 'none'){
								dropdown(jQuery('#dropdown_menu_'+id_parts[2]), offset, id_parts[2]);
							}
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
						jQuery("[id^='dropdown_button_']").attr('src', 'images/commsyicons/dropdownmenu.png');
					}
				});
			}
		}
	}
});

function dropdown(object, offset, button){
	object.css('top', offset.top + 18);
	object.css('left', offset.left - 3);
	if(object.css('display') == 'none'){
		object.slideDown(150);
		jQuery('#dropdown_button_'+button).attr('src', 'images/commsyicons/dropdownmenu_up.png');
	} else if(object.css('display') == 'block'){
		object.slideUp(150);
		jQuery("[id^='dropdown_button_']").attr('src', 'images/commsyicons/dropdownmenu.png');
	}
	object.mouseleave(function(){
		setTimeout(function() {
			object.slideUp(150);
			jQuery("[id^='dropdown_button_']").attr('src', 'images/commsyicons/dropdownmenu.png');
		}, 2000);
	});
}

function delete_date_recurring() {
	var href = jQuery("[id='delete_confirm_entry']").attr('href');
	var href_new = '';

	// remove anchor
	var split = href.split('#');
	var anchor = '';
	if(split.length > 1) {
		anchor = split[1];
	}

	// remove 'action=delete'
	split = split[0].split('&');

	for(var i in split) {
		if(split[i] != 'action=delete') {
			href_new += split[i] + '&';
		}
	}
	href_new += 'delete_option=' + extra_text;

	if(anchor != '') {
		href_new += '#' + anchor;
	}

	jQuery(location).attr('href', href_new);

	return false;
}

function delete_overlay(element, html_element, extra_object) {
	var delete_content_extra = '';
	if(extra_object != null) {
		delete_content_extra = extra_object.content;
	}

	delete_overlay.element = element;

	jQuery('body').append(
    	    jQuery("<div/>", {
    	    	"id"	: "delete_confirm_overlay_background",
    	    	"class" : "delete_confirm_background"
    	    })
    	).append(
    	    jQuery("<div/>", {
    	    	"id"	: "delete_confirm_overlay_box",
    	        "class" : "delete_confirm_box"
    	    }).append(
    	        jQuery("<form/>", {
    	            "method" : "post"
    	        }).append(
    	            jQuery("<h2/>", {
    	            	"text" : headline,
    	            	"style" : "text-align: center;"
    	            })
    	        ).append(
    	        	jQuery("<p style='text-align: left;'>"+text1+"</p>")
    	        ).append(
    	        	jQuery("<p style='text-align: left;'>"+text2+"</p>")
    	        ).append(
    	        	jQuery("<div/>", {
    	        	}).append(
    	        	    jQuery("<input/>", {
    	        	        "type" : "submit",
    	        	        "value" : button_delete,
    	        	        "name" : "delete_option",
    	        	        "style" : "float: right;",
    	        	        "click" : function() {
    	        	    		jQuery("[id='delete_confirm_overlay_background']").remove();
    	        	    		jQuery("[id='delete_confirm_overlay_box']").remove();

    	        	    		// remove event handler
    	        	    		delete_overlay.element.unbind();

    	        	    		// click for buttons and redirect for links
    	        	    		if(html_element.type == 'submit') {
    	        	    			/*
        	        	    		 * This is a workaround for mapping more then one button from the overlay
        	        	    		 * to the underlying formular, by changing the value of the underlying
        	        	    		 * submit button.
        	        	    		 * To avoid visual effects(change of value), the submit button will be
        	        	    		 * renamed and replaced by an invisible pendant.
        	        	    		 */

    	        	    			// set button parameters
    	        	    			var id = html_element.id;
    	        	    			var name = 'delete_option';

    	        	    			// Look for '#' in button-name
    	        	    			var rSplit = html_element.name.split('#');
    	        	    			if(rSplit.length > 1) {
    	        	    				name += '#' + rSplit[1];
    	        	    			}

    	        	    			// change id of original button
	        	    				html_element.id = html_element.id + '_fake';

    	        	    			// get form of originial button
    	        	    			var form = jQuery("input[id='" + html_element.id + "']").parent();
    	        	    			while(form[0].nodeName.substr(0, 4) != 'FORM') {
    	        	    				form = form.parent();
    	        	    			}

    	        	    			// add new invisible button to form
    	        	    			form.append(
    	        	    			    jQuery("<input/>", {
        	        	    			    "type" : html_element.type,
        	        	    			    "value" : button_delete,
        	        	    			    "name" : name,
        	        	    			    "style" : /*html_element.style + */" display: none",
        	        	    			    "id" : id
        	        	    			})
    	        	    			);

    	        	    			// click button
    	        	    			jQuery("input[id='" + id + "']").click();
    	        	    		} else {
    	        	    			var href = html_element.href;
    	        	    			var href_new = '';

    	        	    			// remove anchor
    	        	    			var split = href.split('#');
    	        	    			var anchor = '';
    	        	    			if(split.length > 1) {
    	        	    				anchor = split[1];
    	        	    			}

    	        	    			// remove 'action=delete'
    	        	    			split = split[0].split('&');

    	        	    			for(var i in split) {
    	        	    				if(split[i] != 'action=delete') {
    	        	    					href_new += split[i] + '&';
    	        	    				}
    	        	    			}
    	        	    			href_new += 'delete_option=' + button_delete;

    	        	    			if(anchor != '') {
    	        	    				href_new += '#' + anchor;
    	        	    			}

    	        	    			jQuery(location).attr('href', href_new);
    	        	    		}

    	        	    		return false;
    	        	    	}
    	        	    })
    	        	).append(
    	        		delete_content_extra
    	        	).append(
    	        		jQuery("<input/>", {
    	        			"type" : "submit",
    	        	        "value" : button_cancel,
    	        	        "name" : "delete_option",
    	        	        "style" : "float: left;",
    	        	        "click" : function() {
    	        				jQuery("[id='delete_confirm_overlay_box']").fadeOut('slow', function() {
    	        					jQuery("[id='delete_confirm_overlay_background']").remove();
	    	        				jQuery("[id='delete_confirm_overlay_box']").remove();
    	        				});
    	        				return false;
    	        		    }
    	        		})))));

    	jQuery("[id='delete_confirm_overlay_box']").fadeIn('slow');

    	return false;
}

/*
 *  Manages the deletion confirm box for select elements with submit button
 *  name confirm button "delete_confirmselect_..." and
 *  observed option "delete_check_..."
 */
jQuery(document).ready(function() {
	jQuery("[id^='delete_confirmselect_']").click(function() {
		var element = jQuery(this);
		var html_element = this;

		if(jQuery("[id^='delete_check_']").attr('selected')) {
			// add overlay
		   	delete_overlay(element, html_element, null);

		    return false;
		}
	});
});

/*
 *  Manages the deletion confirm box for all elements with name-tag: "delete_confirm_..."
 *  Use for form buttons and links
 */
jQuery(document).ready(function() {
	jQuery("[id^='delete_confirm_']").click(function() {
    	var element = jQuery(this);
    	var html_element = this;

    	var extra_object = null;

    	// create extra object
    	if( typeof(extra_text) != 'undefined' &&
    		typeof(extra_content) != 'undefined') {
    			extra_object = new Object();
    			extra_object.text = extra_text;
    			extra_object.content = extra_content;
    	}

    	// add overlay
    	delete_overlay(element, html_element, extra_object);

    	return false;
	});
});

function formatDiscussionTreeWithProgress(div_width, iteration, creators, dates) {
	var total = creators.length;

	if(iteration == total) {
		setupDiscussionTree();
		return false;
	}

	// do css stuff
	// set all creator texts at 50%
	var creator_width = (Math.floor(div_width / 2) * 1);
	jQuery(creators[iteration]).css('position', 'absolute');
	jQuery(creators[iteration]).css('display', 'inline');
	jQuery(creators[iteration]).css('left', creator_width);

	// set all date texts at 80%
	var date_width = (Math.floor(div_width / 5) * 4);
	jQuery(dates[iteration]).css('position', 'absolute');
	jQuery(dates[iteration]).css('display', 'inline');
	jQuery(dates[iteration]).css('left', date_width);

	// update progressbar
	var percent = (iteration+1) * 100 / total;
	jQuery('[id=discussion_tree_progressbar]').progressbar("value", percent);
	jQuery('[id=discussion_tree_progressbar_percent]').html(Math.floor(percent));

	// call recursivly
	setTimeout(function() {
		formatDiscussionTreeWithProgress(div_width, ++iteration, creators, dates);
	}, 1);

	return false;
}

function setupDiscussionTree() {
	var tree = jQuery('[id=discussion_tree]');

	tree.dynatree({
		fx: { height: "toggle", duration: 200 },
		onActivate: function(dtnode) {
			if(dtnode.data.url) {
				try {
					window.location(dtnode.data.url);
				}
				catch(e) {
				}
			}
		},
		onClick: function(dtnode, event) {
			// Hervorgehobenen Hintergrund verhindern, wenn nicht auf einen Link für einen Beitrag geklickt wird
			if(	event.target.nodeName == 'IMG' ||
					(event.target.nodeName == 'SPAN' &&
					event.target.className != 'ui-dynatree-expander')) {
				return false;
			}

			// set max tree depth
			if(dtnode.getLevel() > 11) return false;

			if(event.target.nodeName == 'A') {
				jQuery(location).attr('href', event.target.href);

				return false;
			}
		}
	});

	var max_visible_nodes = 10;
	var max_expand_level = getExpandLevel(tree, max_visible_nodes);

	// root immer ausklappen
	if(max_expand_level < 2) max_expand_level = 2;

	tree.dynatree("getRoot").visit(function(dtnode) {
		if(dtnode.getLevel() < max_expand_level) {
			dtnode.expand(true);
		}
	});

	// "ge�nderte" und "neue" Eintr�ge ausklappen
	jQuery('[id=discussion_tree]').dynatree("getRoot").visit(function(dtnode) {
	    var title = dtnode.data.title;
	    var regexp = /(change)/g;

	    if(regexp.test(title) == true) {
	    	dtnode.focus();
	    }
	});
	
	// build show all / hide all link
	
	var showAndHide = {
		status: "hide",
		
		init: function(tree, span_id) {
			jQuery(document).ready(function($) {
				jQuery('span[id="' + span_id + '"]').append('<a id="dicussion_threaded_show_hide_a" href="#"></a>');
			
				var link = jQuery('a[id="dicussion_threaded_show_hide_a"]');
				
				// get actual dynatree status - try to find a not expanded node
				tree.dynatree("getRoot").visit(function(node) {
					if(!node.isVisible()) {
						showAndHide.status = 'show';
						return false;
					}
				}, false);
				
				// set link text
				if(showAndHide.status == 'show') {
					link.text(show_all);
				} else {
					link.text(hide_all);
				}
				
				// bind onClick
				link.bind('click', function(e) {
					showAndHide.onClick($, tree, link, e);
				});
			});
		},
		
		onClick: function($, tree, link, e) {
			// switch status - expand / compress tree
			if(showAndHide.status == 'show') {
				link.text(hide_all);
				showAndHide.status = 'hide';
				tree.dynatree("getRoot").visit(function(node) {
					node.expand(true);
				});
			} else {
				link.text(show_all);
				showAndHide.status = 'show';
				tree.dynatree("getRoot").visit(function(node) {
					node.expand(false);
				}, false);
			}
		}
	};
	
	showAndHide.init(tree, "discussion_show_hide_all");

	// make tree visible
	jQuery('[id=discussion_tree]').fadeIn(200);

	// remove progressbar
	jQuery('[id=discussion_tree_progressbar_wrap]').remove();

	// set commsy body to a fixed size
	var body_width = jQuery('[class=commsy_body]').width();
	jQuery('[class=commsy_body]').css('width', body_width);
	jQuery('[class=commsy_footer]').css('width', body_width);
}

jQuery(document).ready(function() {
	var tree = jQuery('[id=discussion_tree]');

	if(tree.length) {
		jQuery.ui.dynatree.nodedatadefaults["icon"] = false;

		// set progressbar
		jQuery('[id=discussion_tree_progressbar]').progressbar();

		// get div width
		var div_width = jQuery('[class=infoborder]').width();

		var creators = jQuery('[id=discussion_tree] [class=discussion_detail_view_threaded_tree_creator]');
		var dates = jQuery('[id=discussion_tree] [class=discussion_detail_view_threaded_tree_date]');

		formatDiscussionTreeWithProgress(div_width, 0, creators, dates);

		return false;
	}
});

var portlet_data = new Object();

jQuery(document).ready(function() {
	//jQuery("[name=portlet_preferences]").each(function (i) {
    jQuery("a.preferences_flip").each(function (i) {
		var front_side = jQuery(this);
		var id = jQuery(this).parent().parent().attr('id');
		jQuery(this).click(function() {
			//var height = jQuery("#"+id).find('.portlet-content').height()+'px';
			var id_portlet = jQuery("#"+id).find('.portlet-content').find('div').attr('id');
			jQuery("#"+id).flip({
			   direction:'rl',
			   content:jQuery("#"+id+"_preferences"),
			   color: '#FFFFFF',
			   speed: 300,
			   onEnd: function(){
				  jQuery("#"+id).find('a.preferences_flip').each(function(){
					 jQuery(this).click(function(){
						jQuery("#"+id).revertFlip();
					 });
					 //jQuery(this).parent().parent().find('.portlet-content').css('height', height);
					 jQuery(this).parent().parent().find('.portlet-content').append(jQuery('<div id="'+id_portlet+'"></div>'));
				  });
				  jQuery("#"+id).find('div.portlet-back').each(function(){
				      portlet_turn_action(true, id, jQuery("#"+id));
				  });
				  jQuery("#"+id).find('div.portlet-front').each(function(){
				  	  portlet_turn_action(false, id, jQuery("#"+id));
				  });
			   }
			});
		});
	});
	jQuery("[name=portlet_remove]").each(function (i) {
		var id = jQuery(this).parent().parent().parent().attr('id');
		jQuery(this).click(function() {
			jQuery('#'+id).remove();

			// Haken im DropDown-Menu entfernen!
			jQuery('[name=portlets]:checked').each(function(){
		       if(id == jQuery(this).attr('value')){
			      jQuery(this).attr('checked', false);
			   }
			});

			var json_data = new Object();
		    var portlet_columns = jQuery(".column");
		    for ( var int = 0; int < portlet_columns.length; int++) {
		    	var column_portlets = new Array();
				var portlet_column = jQuery(portlet_columns[int]);
				var portlets = portlet_column.children();
				for ( var int2 = 0; int2 < portlets.length; int2++) {
					var portlet = jQuery(portlets[int2]);
					if(window.ajax_function == 'privateroom_home'){
						column_portlets.push(portlet.find('.portlet-content').find('div').attr('id'));
					} else if (window.ajax_function == 'privateroom_myroom') {
						column_portlets.push(portlet.find('.portlet-header').attr('id'));
					}
				}
				if(column_portlets.length == 0){
					column_portlets[0] = 'empty';
				}
				json_data['column_'+int] = column_portlets;
			}

			jQuery.ajax({
		       url: 'commsy.php?cid='+window.ajax_cid+'&mod=ajax&fct='+window.ajax_function+'&output=json&do=save_config',
			   data: json_data,
			   success: function(msg){
			   }
			});
		});
	});
});

function portlet_turn_action(preferences, id, portlet){
   if(preferences){
      if(id == 'cs_privateroom_home_youtube_view'){
         turn_portlet_youtube(id, portlet);
      } else if (id == 'cs_privateroom_home_new_item_view'){
         turn_portlet_new_item(id, portlet);
      } else if (id == 'cs_privateroom_home_flickr_view'){
         turn_portlet_flickr(id, portlet);
      } else if (id == 'cs_privateroom_home_twitter_view'){
         turn_portlet_twitter(id, portlet);
      } else if (id == 'cs_privateroom_home_rss_ticker_view'){
         turn_portlet_rss(id, portlet);
      } else if (id == 'my_matrix_box'){
         turn_portlet_matrix(id, portlet);
      } else if (id == 'my_tag_box'){
         turn_portlet_tag(id, portlet, 'entries');
      } else if (id == 'cs_privateroom_home_new_entries_view'){
         turn_portlet_new_entries(id, portlet);
      } else if ((id == 'my_buzzword_box') || (id == 'cs_privateroom_home_buzzword_view')){
         turn_portlet_buzzwords(id, portlet);
      } else if (id == 'cs_privateroom_home_note_view'){
         turn_portlet_note(id, portlet);
      } else if (id == 'cs_privateroom_home_tag_view'){
         turn_portlet_tag(id, portlet, 'home');
      }
   } else {
      if(id == 'cs_privateroom_home_youtube_view'){
         return_portlet_youtube(id, portlet);
      } else if (id == 'cs_privateroom_home_new_item_view'){
         return_portlet_new_item(id, portlet);
      } else if (id == 'cs_privateroom_home_flickr_view'){
         return_portlet_flickr(id, portlet);
      } else if (id == 'cs_privateroom_home_twitter_view'){
         return_portlet_twitter(id, portlet);
      } else if (id == 'cs_privateroom_home_rss_ticker_view'){
         return_portlet_rss(id, portlet);
      } else if (id == 'my_matrix_box'){
         return_portlet_matrix(id, portlet);
      } else if (id == 'my_tag_box'){
         return_portlet_tag(id, portlet, 'entries');
      } else if (id == 'cs_privateroom_home_new_entries_view'){
         return_portlet_new_entries(id, portlet);
      } else if ((id == 'my_buzzword_box') || (id == 'cs_privateroom_home_buzzword_view')){
         return_portlet_buzzwords(id, portlet);
      } else if (id == 'cs_privateroom_home_note_view'){
         return_portlet_note(id, portlet);
      } else if (id == 'cs_privateroom_home_tag_view'){
         return_portlet_tag(id, portlet, 'home');
      }
   }
}

function turn_portlet_youtube(id, portlet){
   if(portlet_data['youtube_channel']){
      jQuery('#portlet_youtube_channel').val(portlet_data['youtube_channel']);
   }
   jQuery("#"+id).find('input').each(function(){
      if(jQuery(this).attr('type') == 'submit'){
	     jQuery(this).click(function(){
	        portlet_data['youtube_channel'] = jQuery('#portlet_youtube_channel').val();
	    	var json_data = new Object();
	    	json_data['youtube_channel'] = jQuery('#portlet_youtube_channel').val();
	    	jQuery.ajax({
	    	   url: 'commsy.php?cid='+window.ajax_cid+'&mod=ajax&fct=privateroom_home_portlet_configuration&output=json&portlet=youtube',
	    	   data: json_data,
	    	   success: function(msg){
	    		  portlet_data['youtube_save'] = true;
	    	      portlet.revertFlip();
	    	   }
	    	});
		 });
      }
   });
}

function return_portlet_youtube(id, portlet){
   if(portlet_data['youtube_save']){
	  if(typeof(youtube_message) !== 'undefined'){
		  if(portlet_data['youtube_channel'] != ''){
			  var message = youtube_message.replace('TEMP_CHANNEL', portlet_data['youtube_channel']);
		  } else {
			  var message = youtube_message.replace('TEMP_CHANNEL', ' ... ');
		  }
		  jQuery('[name="youtube_message"]').html(message);
	  }

	  $("#youtubevideos_portlet").find('#channel_div').remove();
	  $("#youtubevideos_portlet").find('p.loader').remove();

	  $("#youtubevideos_portlet").youTubeChannel({
	     userName: portlet_data['youtube_channel'],
	     channel: "favorites",
	     hideAuthor: true,
	     numberToDisplay: 3,
	     linksInNewWindow: true
	  });
	  portlet_data['youtube_save'] = false;
   }
}

function turn_portlet_new_item(id, portlet){
}
function return_portlet_new_item(id, portlet){
}

function turn_portlet_flickr(id, portlet){
   if(portlet_data['flickr_id']){
      jQuery('#portlet_flickr_id').val(portlet_data['flickr_id']);
   }
   jQuery("#"+id).find('input').each(function(){
      if(jQuery(this).attr('type') == 'submit'){
         jQuery(this).click(function(){
            portlet_data['flickr_id'] = jQuery('#portlet_flickr_id').val();
            var json_data = new Object();
            json_data['flickr_id'] = jQuery('#portlet_flickr_id').val();
            jQuery.ajax({
               url: 'commsy.php?cid='+window.ajax_cid+'&mod=ajax&fct=privateroom_home_portlet_configuration&output=json&portlet=flickr',
               data: json_data,
               success: function(msg){
                  portlet_data['flickr_save'] = true;
                  portlet.revertFlip();
               }
            });
         });
      }
   });
}

function return_portlet_flickr(id, portlet){
   if(portlet_data['flickr_save']){
      if(typeof(flickr_message) !== 'undefined'){
         var message = flickr_message.replace('TEMP_ID', portlet_data['flickr_id']);
         jQuery('[name="flickr_message"]').html(message);
      }

      var url = "http://api.flickr.com/services/feeds/photos_faves.gne?format=json&id="+portlet_data['flickr_id']+"&jsoncallback=?";
      var bridge = new ctRotatorBridgeFlickr(url, function(dataSource){
         $("#flickr").ctRotator(dataSource, {
            showCount:1,
            speed: 5000,
            itemRenderer:function(item){
               return "<a href=\"" + item.url+ "\"><img style=\"height:200px;\" src=\"" + item.image + "\" alt=\"" + item.title + "\"/></a>";
            }
         });
      });
      bridge.getDataSource();

      portlet_data['flickr_save'] = false;
   }
}

function turn_portlet_twitter(id, portlet){
   if(portlet_data['twitter_channel_id']){
      jQuery('#portlet_twitter_channel_id').val(portlet_data['twitter_channel_id']);
   }
   jQuery("#"+id).find('input').each(function(){
      if(jQuery(this).attr('type') == 'submit'){
         jQuery(this).click(function(){
            portlet_data['twitter_channel_id'] = jQuery('#portlet_twitter_channel_id').val();
            var json_data = new Object();
            json_data['twitter_channel_id'] = jQuery('#portlet_twitter_channel_id').val();
            jQuery.ajax({
               url: 'commsy.php?cid='+window.ajax_cid+'&mod=ajax&fct=privateroom_home_portlet_configuration&output=json&portlet=twitter',
               data: json_data,
               success: function(msg){
                  portlet_data['twitter_save'] = true;
                  portlet.revertFlip();
               }
            });
         });
      }
   });
}

function return_portlet_twitter(id, portlet){
   if(portlet_data['twitter_save']){
      if(typeof(twitter_message) !== 'undefined'){
         var message = twitter_message.replace('TEMP_TWITTER_CHANNEL_ID', portlet_data['twitter_channel_id']);
         jQuery('[name="twitter_message"]').html(message);
      }

      $("#twitter_friends").twitterFriends({
         debug:1,
         username:portlet_data['twitter_channel_id']
      });

      portlet_data['twitter_save'] = false;
   }
}

function turn_portlet_rss(id, portlet){
   jQuery('#portlet_rss_add_button').click(function(){
	  if(jQuery('#portlet_rss_title').val() != '' && jQuery('#portlet_rss_adress').val() != ''){
         jQuery('#portlet_rss_list').append('<div class="rss_list_div" name="'+jQuery('#portlet_rss_title').val()+'"><input type="checkbox" name="portlet_rss[]" value="'+jQuery('#portlet_rss_title').val()+'" checked >'+jQuery('#portlet_rss_title').val()+' ('+jQuery('#portlet_rss_adress').val()+')</div>');

         var json_data = new Object();
    	 json_data['rss_add_titel'] = jQuery('#portlet_rss_title').val();
    	 json_data['rss_add_adress'] = jQuery('#portlet_rss_adress').val();
    	 jQuery.ajax({
    	    url: 'commsy.php?cid='+window.ajax_cid+'&mod=ajax&fct=privateroom_home_portlet_configuration&output=json&portlet=rss_add',
    	    data: json_data,
    	    success: function(msg){
    	       portlet_data['rss_save'] = true;
    	    }
    	 });

         jQuery('#portlet_rss_title').val('');
         jQuery('#portlet_rss_adress').val('');
	  }
   });
   jQuery('#portlet_rss_button').click(function(){
	  var json_data = new Object();
	  var checked_array = new Array();
	  jQuery(this).parent().find('[name="portlet_rss[]"]:checked').each(function(){
		  checked_array.push(jQuery(this).attr('value'));
	  });

	  if(jQuery('#portlet_rss_title').val() != '' && jQuery('#portlet_rss_adress').val() != ''){
		  json_data['rss_add_titel'] = jQuery('#portlet_rss_title').val();
		  json_data['rss_add_adress'] = jQuery('#portlet_rss_adress').val();
		  jQuery('#portlet_rss_list').append('<div class="rss_list_div" name="'+jQuery('#portlet_rss_title').val()+'"><input type="checkbox" name="portlet_rss[]" value="'+jQuery('#portlet_rss_title').val()+'" checked >'+jQuery('#portlet_rss_title').val()+' ('+jQuery('#portlet_rss_adress').val()+')</div>');
	      checked_array.push(jQuery('#portlet_rss_title').val());
	  }

	  portlet_data['rss_checked'] = checked_array;
	  json_data['rss_checked'] = checked_array;


	  jQuery.ajax({
	     url: 'commsy.php?cid='+window.ajax_cid+'&mod=ajax&fct=privateroom_home_portlet_configuration&output=json&portlet=rss_save',
	     data: json_data,
	     success: function(msg){
	        portlet_data['rss_save'] = true;
	        portlet.revertFlip();
	     }
	  });
   });
   if(typeof(portlet_data['rss_checked']) !== 'undefined'){
	   jQuery('#portlet_rss_button').parent().find('.rss_list_div').each(function(){
		  var checked = false;
		  for ( var int = 0; int < portlet_data['rss_checked'].length; int++) {
			 if(jQuery(this).attr('name') == portlet_data['rss_checked'][int]){
		    	 checked = true;
		     }
		  }
		  if(!checked){
	         jQuery(this).remove();
		  }
	   });
   }
}

function return_portlet_rss(id, portlet){
   if(portlet_data['rss_save']){
	  if(typeof(portlet_data['rss_checked']) !== 'undefined'){
	     var temp_div = jQuery('.ticker').parent();
	     jQuery(temp_div).children().remove();

	     for ( var int = 0; int < portlet_data['rss_checked'].length; int++) {
		    var rss = portlet_data['rss_checked'][int];
		    jQuery(temp_div).append('<h4 style="margin-bottom: 0px; margin-top: 0px;">'+rss+'</h4><div id="'+rss+'" class="ticker"></div>');
		    new rssticker_ajax(rss, 0, rss, "ticker", 10000, "date",rss_ticker_cid,rss_ticker_sid);
	     }

         portlet_data['rss_save'] = false;
	  }
   }
}

function MatrixItem(id, name) {
    this.id = id;
    this.name = name;
}
portlet_data['current_matrix_rows_new'] = new Array();
portlet_data['current_matrix_columns_new'] = new Array();
function turn_portlet_matrix(id, portlet){
   if(typeof(portlet_data['change']) !== 'undefined'){
      jQuery('#matrix_rows').find('.matrix_text').each(function(){
         var id_array = jQuery(this).attr('name').split('_');
         if(portlet_data['change'][0][id_array[1]]){
            jQuery(this).val(portlet_data['change'][0][id_array[1]]);
         }
      });
      jQuery('#matrix_columns').find('.matrix_text').each(function(){
          var id_array = jQuery(this).attr('name').split('_');
          if(portlet_data['change'][0][id_array[1]]){
             jQuery(this).val(portlet_data['change'][0][id_array[1]]);
          }
       });
   }
   jQuery("#"+id).find('input').each(function(){
      if(jQuery(this).attr('type') == 'submit'){
	     jQuery(this).click(function(){
	    	   portlet_data['current_matrix_rows'] = new Array();
	    	   portlet_data['current_matrix_columns'] = new Array();
	    	   portlet_data['change'] = new Array();

	    	   var json_data = new Object();
	    	   json_data['new_matrix_row'] = jQuery('#new_matrix_row').val();
	    	   json_data['new_matrix_column'] = jQuery('#new_matrix_column').val();

	    	   var checked_array = new Array();
	    	   jQuery(this).parent().find('[name^="matrix_"]:checked').each(function(){
	    		  checked_array.push(new Array(jQuery(this).attr('value'), jQuery('[name=matrixtext_'+jQuery(this).attr('value')+']').val()));
	    	   });
	    	   json_data['current_matrix'] = checked_array;
	    	   portlet_data['current_matrix'] = checked_array;

	    	   jQuery.ajax({
	    	   url: 'commsy.php?cid='+window.ajax_cid+'&mod=ajax&fct=privateroom_matrix_configuration&output=json&do=save_config',
	    	      data: json_data,
	    	      success: function(msg){
	    		     //alert(msg);
	    	         portlet_data['matrix_save'] = true;
	    	         //portlet.revertFlip();

	    	         jQuery('#matrix_rows').find('[name^="matrix_"]:checked').each(function(){
        	    		 portlet_data['current_matrix_rows'].push(new MatrixItem(jQuery(this).val(), ''));
        	    	 });

                     jQuery('#matrix_columns').find('[name^="matrix_"]:checked').each(function(){
                    	 portlet_data['current_matrix_columns'].push(new MatrixItem(jQuery(this).val(),''));
         	    	 });

	    	         var resultJSON = eval('(' + msg + ')');
                     if (resultJSON === undefined){
                     }else{
                	    if(resultJSON['new_row']){
                		   jQuery('#matrix_rows').append('<div><input name="matrix_'+resultJSON['new_row']+'" value="'+resultJSON['new_row']+'" checked="checked" type="checkbox"><input type="text" value="'+resultJSON['new_row_name']+'" name="matrixtext_'+resultJSON['new_row']+'" class="matrix_text"></div>');
                		   portlet_data['current_matrix_rows_new'].push(new MatrixItem(resultJSON['new_row'], resultJSON['new_row_name']));
                	    }
                	    if(resultJSON['new_column']){
                	       jQuery('#matrix_columns').append('<div><input name="matrix_'+resultJSON['new_column']+'" value="'+resultJSON['new_column']+'" checked="checked" type="checkbox"><input type="text" value="'+resultJSON['new_column_name']+'" name="matrixtext_'+resultJSON['new_column']+'" class="matrix_text"></div>');
                	       portlet_data['current_matrix_columns_new'].push(new MatrixItem(resultJSON['new_column'], resultJSON['new_column_name']));
                	    }
                	    portlet_data['change'].push(resultJSON);
                     }
                     jQuery('#matrix_rows').find('[name^="matrix_"]:not(:checked)').each(function(){
       	    	        jQuery(this).parent().remove();
       	    	     });
                     jQuery('#matrix_columns').find('[name^="matrix_"]:not(:checked)').each(function(){
        	    	    jQuery(this).parent().remove();
        	    	 });
                     jQuery('#new_matrix_row').val(new_row_message);
                     jQuery('#new_matrix_column').val(new_column_message);
	    	     }
	         });
		 });
      }
   });
}

function return_portlet_matrix(id, portlet){
   if(portlet_data['matrix_save']){
	  // Zeilen
	  jQuery('#matrix_table').find('tr').each(function(){
		  if(jQuery(this).attr('id') != 'matrix_table_header'){
			  var row_exists = false;
			  for (var int = 0; int < portlet_data['current_matrix_rows'].length; int++) {
				if(portlet_data['current_matrix_rows'][int].id == jQuery(this).attr('id')){
					row_exists = true;
				}
			  }
			  if(!row_exists){
			     jQuery(this).remove();
			  }
		  }
	  });

	  jQuery('#matrix_table').find('tr').each(function(){
	  	  if(jQuery(this).attr('id') != 'matrix_table_header'){
	  		  var change = portlet_data['change'][0];
	  		  jQuery(this).find('td').first().html(change[jQuery(this).attr('id')]);
	  	  }
	  });

	  for ( var int = 0; int < portlet_data['current_matrix_rows_new'].length; int++) {
		var temp_row = portlet_data['current_matrix_rows_new'][int];
		jQuery('#matrix_table').append('<tr id="'+temp_row.id+'"><td name="matrix_table_left" style="background-color:#CCCCCC;">'+temp_row.name+'</td></tr>');
		for ( var int2 = 0; int2 < portlet_data['current_matrix_columns'].length; int2++) {
			var temp_column = portlet_data['current_matrix_columns'][int2].id;
			jQuery('#'+temp_row.id).append('<td class="droppable_matrix" id="id_'+temp_row.id+'_'+temp_column+'" style="text-align:center;">0</td>');
		}
	  }

	  // Spalten
	  jQuery('#matrix_table').find('tr').each(function(){
		  if(jQuery(this).attr('id') != 'matrix_table_header'){
			  jQuery(this).find('td').each(function(){
				  if(jQuery(this).attr('name') != 'matrix_table_left'){
					  var column_exists = false;
					  for (var int = 0; int < portlet_data['current_matrix_columns'].length; int++) {
						var temp_array = jQuery(this).attr('id').split('_');
						var temp_id = temp_array[2];
					  	if(portlet_data['current_matrix_columns'][int].id == temp_id){
					  		column_exists = true;
					  	}
					  }
					  if(!column_exists){
					     jQuery(this).remove();
					  }
				  }
			  });
		  } else {
			  jQuery(this).find('td').each(function(){
				  if(jQuery(this).attr('id') != 'matrix_table_top_left'){
					  var column_exists = false;
					  for (var int = 0; int < portlet_data['current_matrix_columns'].length; int++) {
					  	if(portlet_data['current_matrix_columns'][int].id == jQuery(this).attr('id')){
					  		column_exists = true;
					  	}
					  }
					  if(!column_exists){
					     jQuery(this).remove();
					  }
				  }
			  });
			  jQuery(this).find('td').each(function(){
				  if(jQuery(this).attr('id') != 'matrix_table_top_left'){
					  var change = portlet_data['change'][0];
			  		  jQuery(this).html(change[jQuery(this).attr('id')]);
				  }
			  });
		  }
	  });

	  for ( var int = 0; int < portlet_data['current_matrix_columns_new'].length; int++) {
	     var temp_column = portlet_data['current_matrix_columns_new'][int];
	     jQuery('#matrix_table').find('tr').each(function(){
			  if(jQuery(this).attr('id') != 'matrix_table_header'){
				  jQuery(this).append('<td class="droppable_matrix" id="id_'+jQuery(this).attr('id')+'_'+temp_column.id+'" style="text-align:center;">0</td>');
			  } else {
				  jQuery(this).append('<td class="droppable_matrix" id="'+temp_column.id+'" style="background-color:#CCCCCC;">'+temp_column.name+'</td>');
			  }
		  });
	  }

	  portlet_data['matrix_save'] = false;
	  portlet_data['current_matrix_rows_new'] = new Array();
	  portlet_data['current_matrix_columns_new'] = new Array();
   }
   jQuery(".droppable_matrix").droppable({
	  hoverClass: 'droppable_item_hover',
      drop: function(event, ui) {
	     drop_to_matrix(this.id, ui.draggable[0].id.replace(/[^0-9]/g,''));
	  }
   });
}

function drop_to_matrix(id, item_id){
   var json_data = new Object();
   var id_array = id.split('_');
   json_data['row_id'] = id_array[1];
   json_data['column_id'] = id_array[2];
   json_data['item_id'] = item_id;

   jQuery.ajax({
   url: 'commsy.php?cid='+window.ajax_cid+'&mod=ajax&fct=privateroom_entry&output=json&do=update_matrix&action=add_item',
      data: json_data,
      success: function(msg){
	     //alert(msg);
         var resultJSON = eval('(' + msg + ')');
         if (resultJSON === undefined){
         }else{
    	    jQuery('#'+id).find('.matrix_current_count').html(resultJSON['matrix_counter']);
         }
      }
   });
}

function turn_portlet_new_entries(id, portlet){
   if(portlet_data['youtube_channel']){
      jQuery('#portlet_new_entries_count').val(portlet_data['portlet_new_entries_count']);
   }
   jQuery("#"+id).find('input').each(function(){
      if(jQuery(this).attr('type') == 'submit'){
	     jQuery(this).click(function(){
	        portlet_data['new_entries_count'] = jQuery('#portlet_new_entries_count').find(':selected').val();
	        portlet_data['new_entries_show_user'] = jQuery('#portlet_new_entries_show_user').find(':selected').val();
	    	var json_data = new Object();
	    	json_data['new_entries_count'] = jQuery('#portlet_new_entries_count').find(':selected').val();
	    	json_data['new_entries_show_user'] = jQuery('#portlet_new_entries_show_user').find(':selected').val();
	    	jQuery.ajax({
	    	   url: 'commsy.php?cid='+window.ajax_cid+'&mod=ajax&fct=privateroom_home_portlet_configuration&output=json&portlet=new_entries',
	    	   data: json_data,
	    	   success: function(msg){
	    		  portlet_data['new_entries_save'] = true;
	    	      //portlet.revertFlip();
	    		  window.location = window.location.href;
	    	   }
	    	});
		 });
      }
   });
}

function return_portlet_new_entries(id, portlet){
   if(portlet_data['new_entries_save']){
	  portlet_data['new_entries_save'] = false;
   }
}

function turn_portlet_note(id, portlet){
   if(typeof(portlet_data['note_content']) !== 'undefined'){
	  jQuery('#portlet_note_content').html(portlet_data['note_content']);
   } else {
	  jQuery('#portlet_note_content').html(jQuery('#portlet_note_content').html().replace(/COMMSY_BR/g, '\n'));
   }
   jQuery('#portlet_note_save_button').click(function(){
	  //if(jQuery('#portlet_note_content').val() != ''){
         var json_data = new Object();
         var content = jQuery('#portlet_note_content').val().replace(/(\r\n)|(\r)|(\n)/g, 'COMMSY_BR');
         content = content.replace(/["]/g, 'COMMSY_DOUBLE_QUOTE');
         content = content.replace(/[']/g, 'COMMSY_SINGLE_QUOTE');
    	 json_data['portlet_note_content'] = content;
    	 portlet_data['note_content'] = content;
    	 jQuery.ajax({
    	    url: 'commsy.php?cid='+window.ajax_cid+'&mod=ajax&fct=privateroom_home_portlet_configuration&output=json&portlet=note',
    	    data: json_data,
    	    success: function(msg){
    		   var resultJSON = eval('(' + msg + ')');
               if (resultJSON === undefined){
               }else{
    		      portlet_data['note_content_html'] = resultJSON['content_html'];
    		      portlet_data['note_content'] = resultJSON['content'].replace(/COMMSY_BR/g, '\n');
    		      portlet_data['note_content'] = portlet_data['note_content'].replace(/COMMSY_DOUBLE_QUOTE/g, '"');
    		      portlet_data['note_content'] = portlet_data['note_content'].replace(/COMMSY_SINGLE_QUOTE/g, "'");
    	          portlet_data['note_save'] = true;
    	          portlet.revertFlip();
               }
    	    }
    	 });
	  //}
   });
}

function return_portlet_note(id, portlet){
   if(portlet_data['note_save']){
	  if(typeof(portlet_data['note_content_html']) !== 'undefined'){
	     jQuery('#portlet_note_content_p').html(portlet_data['note_content_html']);
         portlet_data['note_save'] = false;
	  }
   }
}

function turn_portlet_tag(id, portlet, page){
	 jQuery('#my_tag_form_button_add').click(function(){
		if(jQuery('#my_tag_form_new_tag').val() != ''){
	         var json_data = new Object();
	    	 json_data['new_tag_name'] = jQuery('#my_tag_form_new_tag').val();
	    	 json_data['new_tag_father'] = jQuery('#my_tag_form_father_id').find(':selected').val();
	    	 json_data['tag_page'] = page;
	    	 jQuery.ajax({
	    	    url: 'commsy.php?cid='+window.ajax_cid+'&mod=ajax&fct=privateroom_tag_configuration&output=json&do=save_new_tag',
	    	    data: json_data,
	    	    success: function(msg){
	    		   var resultJSON = eval('(' + msg + ')');
	               if (resultJSON === undefined){
	               }else{
	            	   update_tag_form(resultJSON);
	            	   portlet_data['tree_update'] = resultJSON['tree_update'];
	            	   portlet_data['tag_save'] = true;
	               }
	    	    }
	    	 });
		  }
	 });

	 jQuery('#my_tag_form_button_sort').click(function(){
		if(jQuery('#my_tag_form_sort_1').find(':selected').val() != jQuery('#my_tag_form_sort_2').find(':selected').val()){
			 var json_data = new Object();
	    	 json_data['tag_sort_1'] = jQuery('#my_tag_form_sort_1').find(':selected').val();
	    	 json_data['tag_sort_2'] = jQuery('#my_tag_form_sort_2').find(':selected').val();
	    	 json_data['tag_sort_action'] = jQuery('#my_tag_form_sort_action').find(':selected').val();
	    	 json_data['tag_page'] = page;
	    	 jQuery.ajax({
	    	    url: 'commsy.php?cid='+window.ajax_cid+'&mod=ajax&fct=privateroom_tag_configuration&output=json&do=sort_tag',
	    	    data: json_data,
	    	    success: function(msg){
	    		   var resultJSON = eval('(' + msg + ')');
	               if (resultJSON === undefined){
	               }else{
	            	   update_tag_form(resultJSON);
	            	   portlet_data['tree_update'] = resultJSON['tree_update'];
	            	   portlet_data['tag_save'] = true;
	               }
	    	    }
	    	 });
		  }
	 });

	 jQuery('#my_tag_form_button_sort_abc').click(function(){
		 var json_data = new Object();
		 json_data['tag_page'] = page;
    	 jQuery.ajax({
    	    url: 'commsy.php?cid='+window.ajax_cid+'&mod=ajax&fct=privateroom_tag_configuration&output=json&do=sort_tag_abc',
    	    data: json_data,
    	    success: function(msg){
    		   var resultJSON = eval('(' + msg + ')');
               if (resultJSON === undefined){
               }else{
            	   update_tag_form(resultJSON);
            	   portlet_data['tree_update'] = resultJSON['tree_update'];
            	   portlet_data['tag_save'] = true;
               }
    	    }
    	 });
	 });

	 jQuery('#my_tag_form_button_combine').click(function(){
		if(jQuery('#my_tag_form_combine_1').find(':selected').val() != jQuery('#my_tag_form_combine_2').find(':selected').val()){
			 var json_data = new Object();
	    	 json_data['tag_combine_1'] = jQuery('#my_tag_form_combine_1').find(':selected').val();
	    	 json_data['tag_combine_2'] = jQuery('#my_tag_form_combine_2').find(':selected').val();
	    	 json_data['tag_combine_father'] = jQuery('#my_tag_form_combine_father').find(':selected').val();
	    	 json_data['tag_page'] = page;
	    	 jQuery.ajax({
	    	    url: 'commsy.php?cid='+window.ajax_cid+'&mod=ajax&fct=privateroom_tag_configuration&output=json&do=combine_tag',
	    	    data: json_data,
	    	    success: function(msg){
	    		   var resultJSON = eval('(' + msg + ')');
	               if (resultJSON === undefined){
	               }else{
	            	   update_tag_form(resultJSON);
	            	   portlet_data['tree_update'] = resultJSON['tree_update'];
	            	   portlet_data['tag_save'] = true;
	               }
	    	    }
	    	 });
		  }
	 });

	 activate_tag_change_form(page);
}

function update_tag_form(json_data){
	jQuery('#my_tag_form_father_id').html(json_data['values_update']);
	jQuery('#my_tag_form_sort_1').html(json_data['first_sort_update']);
	jQuery('#my_tag_form_sort_2').html(json_data['second_sort_update']);
	jQuery('#my_tag_form_combine_1').html(json_data['first_sort_update']);
	jQuery('#my_tag_form_combine_2').html(json_data['first_sort_update']);
	jQuery('#my_tag_form_combine_father').html(json_data['second_sort_update']);
	jQuery('#my_tag_form_change_table').html(json_data['change_update']);
	activate_tag_change_form();
}

function return_portlet_tag(id, portlet, page){
	if(portlet_data['tag_save']){
		if(typeof(portlet_data['tree_update']) !== 'undefined'){
		    jQuery('#my_tag_content_div').html(portlet_data['tree_update']);

		    activate_tag_tree_privateroom();

		    portlet_data['tree_update'] = null;
	        portlet_data['tag_save'] = false;
		}
	}
}

jQuery(document).ready(function() {
	activate_tag_tree_privateroom();
});

function activate_tag_tree_privateroom(){
	if(jQuery('[id^=tag_tree_privateroom]').length){
		jQuery.ui.dynatree.nodedatadefaults["icon"] = false;
		jQuery('[id^=tag_tree_privateroom]').each(function(){
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
}

function activate_tag_change_form(page){
	jQuery('[id^=my_tag_form_change_button]').click(function(){
		var id_array = jQuery(this).attr('id').split('-');
		if(jQuery('#my_tag_form_change_value-'+id_array[1]).val() != ''){
			 var json_data = new Object();
			 json_data['tag_change_id'] = id_array[1];
	    	 json_data['tag_change_value'] = jQuery('#my_tag_form_change_value-'+id_array[1]).val();
	    	 json_data['tag_page'] = page;
	    	 jQuery.ajax({
	    	    url: 'commsy.php?cid='+window.ajax_cid+'&mod=ajax&fct=privateroom_tag_configuration&output=json&do=change_tag',
	    	    data: json_data,
	    	    success: function(msg){
	    		   var resultJSON = eval('(' + msg + ')');
	               if (resultJSON === undefined){
	               }else{
	            	   update_tag_form(resultJSON);
	            	   portlet_data['tree_update'] = resultJSON['tree_update'];
	            	   portlet_data['tag_save'] = true;
	               }
	    	    }
	    	 });
		  }
	 });

	 jQuery('[id^=my_tag_form_delete_button]').click(function(){
		 var id_array = jQuery(this).attr('id').split('-');
		 var json_data = new Object();
		 json_data['tag_delete_id'] = id_array[1];
    	 jQuery.ajax({
    	    url: 'commsy.php?cid='+window.ajax_cid+'&mod=ajax&fct=privateroom_tag_configuration&output=json&do=delete_tag',
    	    data: json_data,
    	    success: function(msg){
    		   var resultJSON = eval('(' + msg + ')');
               if (resultJSON === undefined){
               }else{
            	   update_tag_form(resultJSON);
            	   portlet_data['tree_update'] = resultJSON['tree_update'];
            	   portlet_data['tag_save'] = true;
               }
    	    }
    	 });
	 });
}

function BuzzwordItem(id, name) {
    this.id = id;
    this.name = name;
}
function BuzzwordCombineItem(id_first, id_second, name) {
   this.id_first = id_first;
   this.id_second = id_second;
   this.name = name;
}
portlet_data['buzzwords_new'] = new Array();
portlet_data['buzzwords_change'] = new Array();
portlet_data['buzzwords_delete'] = new Array();
portlet_data['buzzwords_combine'] = new Array();
function turn_portlet_buzzwords(id, portlet){
   jQuery('#portlet_buzzword_new').val('');

   for ( var int = 0; int < portlet_data['buzzwords_change'].length; int++) {
      var temp_change_buzzword = portlet_data['buzzwords_change'][int];
      jQuery('#portlet_buzzword_'+temp_change_buzzword.id).val(temp_change_buzzword.name);
   }
   //portlet_data['buzzwords_change'] = new Array();

   jQuery('#portlet_buzzword_new_button').click(function(){
	  if(jQuery('#portlet_buzzword_new').val() != ''){
	      portlet_data['new_buzzword'] = jQuery('#portlet_buzzword_new').val();
		  var json_data = new Object();
		  json_data['new_buzzword'] = jQuery('#portlet_buzzword_new').val();
		  jQuery.ajax({
		     url: 'commsy.php?cid='+window.ajax_cid+'&mod=ajax&fct=privateroom_buzzword_configuration&output=json&do=save_new_buzzword',
		     data: json_data,
		     success: function(msg){
			    portlet_data['buzzwords_save'] = true;
			    jQuery('#portlet_buzzword_new').val('');
		        //portlet.revertFlip();
			    var resultJSON = eval('(' + msg + ')');
	            if (resultJSON === undefined){
	            }else{
	        	   portlet_data['buzzwords_new'].push(new BuzzwordItem(resultJSON['new_buzzword_id'], resultJSON['new_buzzword_name']));
	        	   var insert = false;
	        	   jQuery('#portlet_buzzword_preferences_list').find('div').each(function(){
	               if(jQuery(this).find('.portlet_buzzword_textfield').val().toLowerCase() > resultJSON['new_buzzword_name'].toLowerCase() && !insert){
	                  jQuery(this).before('<div><input type="text" class="portlet_buzzword_textfield" id="portlet_buzzword_'+resultJSON['new_buzzword_id']+'" value="'+resultJSON['new_buzzword_name']+'" size="40">&nbsp;<input type="submit" class="portlet_buzzword_change_button" id="'+resultJSON['new_buzzword_id']+'" value="Ändern">&nbsp;<input type="submit" class="portlet_buzzword_delete_button" id="'+resultJSON['new_buzzword_id']+'" value="Löschen"></div>');
	               	  insert = true;
	               }
	               });
	        	   if(!insert){
	        		  if(jQuery('#portlet_buzzword_preferences_list').find('div').size() == 0){
	        			 jQuery('#portlet_buzzword_preferences_list').append('<div><input type="text" class="portlet_buzzword_textfield" id="portlet_buzzword_'+resultJSON['new_buzzword_id']+'" value="'+resultJSON['new_buzzword_name']+'" size="40">&nbsp;<input type="submit" class="portlet_buzzword_change_button" id="'+resultJSON['new_buzzword_id']+'" value="Ändern">&nbsp;<input type="submit" class="portlet_buzzword_delete_button" id="'+resultJSON['new_buzzword_id']+'" value="Löschen"></div>');
	        		  } else {
	                     jQuery('#portlet_buzzword_preferences_list').find('div').last().after('<div><input type="text" class="portlet_buzzword_textfield" id="portlet_buzzword_'+resultJSON['new_buzzword_id']+'" value="'+resultJSON['new_buzzword_name']+'" size="40">&nbsp;<input type="submit" class="portlet_buzzword_change_button" id="'+resultJSON['new_buzzword_id']+'" value="Ändern">&nbsp;<input type="submit" class="portlet_buzzword_delete_button" id="'+resultJSON['new_buzzword_id']+'" value="Löschen"></div>');
	        		  }
	        	   }
	        	   activate_buzzword_buttons();
	        	   var insert = false;
	        	   jQuery('#portal_buzzword_combine_first').find('option').each(function(){
	            	   if((jQuery(this).html().toLowerCase() > resultJSON['new_buzzword_name'].toLowerCase()) && !insert){
	            		   jQuery(this).before('<option value="'+resultJSON['new_buzzword_id']+'">'+resultJSON['new_buzzword_name']+'</option>');
	            		   insert = true;
	            	   }
	               });
	        	   if(!insert){
	        		   if(jQuery('#portal_buzzword_combine_first').find('option').size() == 0){
	        			   jQuery('#portal_buzzword_combine_first').append('<option value="'+resultJSON['new_buzzword_id']+'">'+resultJSON['new_buzzword_name']+'</option>');
	        		   } else {
	        			   jQuery('#portal_buzzword_combine_first').find('option').last().after('<option value="'+resultJSON['new_buzzword_id']+'">'+resultJSON['new_buzzword_name']+'</option>');
	        		   }
	        	   }
	        	   var insert = false;
	        	   jQuery('#portal_buzzword_combine_second').find('option').each(function(){
	            	   if((jQuery(this).html().toLowerCase() > resultJSON['new_buzzword_name'].toLowerCase()) && !insert){
	            		   jQuery(this).before('<option value="'+resultJSON['new_buzzword_id']+'">'+resultJSON['new_buzzword_name']+'</option>');
	            		   insert = true;
	            	   }
	               });
	        	   if(!insert){
	        		   if(jQuery('#portal_buzzword_combine_second').find('option').size() == 0){
	        			   jQuery('#portal_buzzword_combine_second').append('<option value="'+resultJSON['new_buzzword_id']+'">'+resultJSON['new_buzzword_name']+'</option>');
	        		   } else {
	        			   jQuery('#portal_buzzword_combine_second').find('option').last().after('<option value="'+resultJSON['new_buzzword_id']+'">'+resultJSON['new_buzzword_name']+'</option>');
	        		   }
	        	   }
	            }
		     }
		  });
	   }
   });

   jQuery('#portlet_buzzword_combine_button').click(function(){
     var json_data = new Object();
     json_data['buzzword_combine_first'] = jQuery('#portal_buzzword_combine_first').find(':selected').val();
     json_data['buzzword_combine_second'] = jQuery('#portal_buzzword_combine_second').find(':selected').val();
     jQuery.ajax({
        url: 'commsy.php?cid='+window.ajax_cid+'&mod=ajax&fct=privateroom_buzzword_configuration&output=json&do=combine_buzzwords',
        data: json_data,
        success: function(msg){
          portlet_data['buzzwords_save'] = true;
          var resultJSON = eval('(' + msg + ')');
            if (resultJSON === undefined){
            }else{
               //portlet_data['buzzwords_combine'].push(new BuzzwordCombineItem(resultJSON['combine_first_id'], resultJSON['combine_second_id'], resultJSON['combine_name']));
               portlet_data['buzzwords_change'].push(new BuzzwordItem(resultJSON['combine_first_id'], resultJSON['combine_name']));
               portlet_data['buzzwords_delete'].push(new BuzzwordItem(resultJSON['combine_second_id'], resultJSON['combine_name']));
               jQuery('#portlet_buzzword_preferences_list').find('div').each(function(){
            	  var temp_id_array = jQuery(this).find('.portlet_buzzword_textfield').attr('id').split('_');
       			  var temp_id = temp_id_array[2];
                  if(temp_id == resultJSON['combine_first_id']){
                	  jQuery(this).find('.portlet_buzzword_textfield').val(resultJSON['combine_name']);
                  } else if(temp_id == resultJSON['combine_second_id']){
                	  jQuery(this).remove();
                  }
               });
               jQuery('#portal_buzzword_combine_first').find('option').each(function(){
            	   if(jQuery(this).val() == resultJSON['combine_first_id']){
            		   jQuery(this).html(resultJSON['combine_name']);
            	   } else if (jQuery(this).val() == resultJSON['combine_second_id']){
            		   jQuery(this).remove();
            	   }
               });
               jQuery('#portal_buzzword_combine_second').find('option').each(function(){
            	   if(jQuery(this).val() == resultJSON['combine_first_id']){
            		   jQuery(this).html(resultJSON['combine_name']);
            	   } else if (jQuery(this).val() == resultJSON['combine_second_id']){
            		   jQuery(this).remove();
            	   }
               });
            }
        }
     });
   });

   activate_buzzword_buttons();
}

function return_portlet_buzzwords(id, portlet){
   if(portlet_data['buzzwords_save']){
	  //jQuery(portlet).find('.portlet-content').find('#null').remove();
	  for ( var int3 = 0; int3 < portlet_data['buzzwords_new'].length; int3++) {
		 var temp_buzzword = portlet_data['buzzwords_new'][int3];
		 var insert = false;
		 jQuery(portlet).find('.portlet-content').find('a').each(function(){
			 if((jQuery(this).attr('title').toLowerCase() > temp_buzzword.name.toLowerCase()) && !insert){
				jQuery(this).before('<a href="commsy.php?cid='+buzzword_cid+'&amp;mod=entry&amp;fct=index&amp;selbuzzword='+temp_buzzword.id+'" title="'+temp_buzzword.name+'"><span id="buzzword_'+temp_buzzword.id+'" class="droppable_buzzword" style="margin-left:2px; margin-right:2px; color: rgb(63%,63%,63%);font-size:11px;">'+temp_buzzword.name+'</span></a>');
			    insert = true;
			 }
		 });
		 if(!insert){
			if(jQuery(portlet).find('.portlet-content').find('a').size() == 0){
			   jQuery(portlet).find('.disabled').remove();
			   jQuery(portlet).find('#id').remove();
			   jQuery(portlet).find('.portlet-content').append('<a href="commsy.php?cid='+buzzword_cid+'&amp;mod=entry&amp;fct=index&amp;selbuzzword='+temp_buzzword.id+'" title="'+temp_buzzword.name+'"><span id="buzzword_'+temp_buzzword.id+'" class="droppable_buzzword" style="margin-left:2px; margin-right:2px; color: rgb(63%,63%,63%);font-size:11px;">'+temp_buzzword.name+'</span></a>');
			} else {
		       jQuery(portlet).find('.portlet-content').find('a').last().after('<a href="commsy.php?cid='+buzzword_cid+'&amp;mod=entry&amp;fct=index&amp;selbuzzword='+temp_buzzword.id+'" title="'+temp_buzzword.name+'"><span id="buzzword_'+temp_buzzword.id+'" class="droppable_buzzword" style="margin-left:2px; margin-right:2px; color: rgb(63%,63%,63%);font-size:11px;">'+temp_buzzword.name+'</span></a>');
			}
		 }
	  }

	  for ( var int = 0; int < portlet_data['buzzwords_delete'].length; int++) {
		var temp_delete_buzzword = portlet_data['buzzwords_delete'][int];
		jQuery(portlet).find('.portlet-content').find('a').each(function(){
			var temp_id_array = jQuery(this).find('span').attr('id').split('_');
			var temp_id = temp_id_array[1];
			if(temp_id == temp_delete_buzzword.id){
			   jQuery(this).remove();
			}
		});
		if(jQuery(portlet).find('.portlet-content').find('a').size() == 0){
			jQuery(portlet).find('.portlet-content').append('<span class="disabled" style="font-size:10pt;">'+buzzword_message+'</span></div><div id="null"></div>');
		}
	  }

	  for ( var int = 0; int < portlet_data['buzzwords_change'].length; int++) {
		var temp_change_buzzword = portlet_data['buzzwords_change'][int];
		jQuery(portlet).find('.portlet-content').find('a').each(function(){
			var temp_id_array = jQuery(this).find('span').attr('id').split('_');
			var temp_id = temp_id_array[1];
			if(temp_id == temp_change_buzzword.id){
			   jQuery(this).attr('title', temp_change_buzzword.name);
			   jQuery(this).find('span').html(temp_change_buzzword.name);
			}
		});
	  }

	  jQuery(".droppable_buzzword").droppable({
		 hoverClass: 'droppable_item_hover',
		 drop: function(event, ui) {
			//var $_GET = getQueryParams(document.location.search);
			var json_data = new Object();
		    json_data['action'] = 'add_item';
		    var buzzwordId = this.id.replace(/[^0-9]/g,'');
		    var itemId = ui.draggable[0].id.replace(/[^0-9]/g,'');
		    json_data['buzzword_id'] = buzzwordId;
		    json_data['item_id'] = itemId;
		    jQuery.ajax({
			   url: 'commsy.php?cid='+window.ajax_cid+'&mod=ajax&fct=privateroom_entry&output=json&do=update_buzzwords',
			   data: json_data,
			   success: function(msg){
		    	   var resultJSON = eval('(' + msg + ')');
            if (resultJSON === undefined){
            }else{
                jQuery("#buzzword_"+buzzwordId).css('font-size',resultJSON[itemId]+"px");
            }
			   }
			});
		 }
	  });

	  portlet_data['buzzwords_new'] = new Array();
	  //portlet_data['buzzwords_change'] = new Array();
	  portlet_data['buzzwords_delete'] = new Array();
	  portlet_data['buzzwords_save'] = false;
   }
}

function activate_buzzword_buttons(){
   jQuery('.portlet_buzzword_change_button').each(function(){
      jQuery(this).click(function(){
        var json_data = new Object();
        json_data['buzzword_change_id'] = jQuery(this).attr('id');
        json_data['buzzword_change_name'] = jQuery('#portlet_buzzword_'+jQuery(this).attr('id')).val();
        jQuery.ajax({
           url: 'commsy.php?cid='+window.ajax_cid+'&mod=ajax&fct=privateroom_buzzword_configuration&output=json&do=change_buzzword',
           data: json_data,
           success: function(msg){
             //jQuery('#'+json_data['buzzword_delete']).parent().remove();
             portlet_data['buzzwords_save'] = true;
             var resultJSON = eval('(' + msg + ')');
               if (resultJSON === undefined){
               }else{
                  portlet_data['buzzwords_change'].push(new BuzzwordItem(resultJSON['change_buzzword_id'], resultJSON['change_buzzword_name']));
                  jQuery('#portal_buzzword_combine_first').find('option').each(function(){
               	     if(jQuery(this).val() == resultJSON['change_buzzword_id']){
               		    jQuery(this).html(resultJSON['change_buzzword_name']);
               	     }
                  });
                  jQuery('#portal_buzzword_combine_second').find('option').each(function(){
            	     if(jQuery(this).val() == resultJSON['change_buzzword_id']){
            		    jQuery(this).html(resultJSON['change_buzzword_name']);
            	     }
                  });
               }
           }
        });
      });
   });
   jQuery('.portlet_buzzword_delete_button').each(function(){
      jQuery(this).click(function(){
        var json_data = new Object();
        json_data['buzzword_delete'] = jQuery(this).attr('id');
        jQuery.ajax({
           url: 'commsy.php?cid='+window.ajax_cid+'&mod=ajax&fct=privateroom_buzzword_configuration&output=json&do=delete_buzzword',
           data: json_data,
           success: function(msg){
             jQuery('#'+json_data['buzzword_delete']).parent().remove();
             portlet_data['buzzwords_save'] = true;
             var resultJSON = eval('(' + msg + ')');
               if (resultJSON === undefined){
               }else{
                  portlet_data['buzzwords_delete'].push(new BuzzwordItem(resultJSON['delete_buzzword_id'], resultJSON['delete_buzzword_name']));
                  jQuery('#portal_buzzword_combine_first').find('option').each(function(){
                     if(jQuery(this).val() == resultJSON['delete_buzzword_id']){
                	    jQuery(this).remove();
                	 }
                  });
                  jQuery('#portal_buzzword_combine_second').find('option').each(function(){
                     if(jQuery(this).val() == resultJSON['delete_buzzword_id']){
                 	    jQuery(this).remove();
                 	 }
                  });
               }
           }
        });
      });
   });
}

jQuery(document).ready(function() {
	if(typeof(dropDownPortlets) !== 'undefined'){
		if(dropDownPortlets.length){
			// how many different menus?
			var dropdown_menus = new Array();
			for ( var int = 0; int < dropDownPortlets.length; int++) {
				var tempDropDownMenu = dropDownPortlets[int];
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

				if(portletsColumnCount == 2){
					ul.append(jQuery('<li class="dropdown" style="text-align:center;">'+portletsColumnText+': <input type="radio" name="column_count" value="2" checked>2 <input type="radio" name="column_count" value="3">3</li>'));
				} else if (portletsColumnCount == 3){
					ul.append(jQuery('<li class="dropdown" style="text-align:center;">'+portletsColumnText+': <input type="radio" name="column_count" value="2">2 <input type="radio" name="column_count" value="3" checked>3</li>'));
				}

				ul.append('<li class="dropdown_seperator"><hr class="dropdown_seperator"></li>');
				for ( var int4 = 0; int4 < dropDownPortlets.length; int4++) {
					var temp_menu_entry = dropDownPortlets[int4];
					if(temp_menu_entry[0] == current_menu){
						if(temp_menu_entry[1] != 'seperator'){
							var tempActionChecked = temp_menu_entry[1];
							var tempActionText = temp_menu_entry[2];
							var tempActionValue = temp_menu_entry[3];
							ul.append('<li class="dropdown"><input type="checkbox" name="portlets" value="'+tempActionValue+'" '+tempActionChecked+'>'+tempActionText+'</li>');
						} else {
							ul.append('<li class="dropdown_seperator"><hr class="dropdown_seperator"></li>');
						}
					}
				}
				ul.append('<li class="dropdown_seperator"><hr class="dropdown_seperator"></li>');

				var ok_button = jQuery('<li class="dropdown" style="text-align:center;"><input type="submit" value="'+portletsSaveButton+'"></li>');
				ul.append(ok_button);

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
							//var offset = jQuery('#dropdown_button_'+id_parts[4]).parent().offset();
							var offset = jQuery('#dropdown_button_'+id_parts[4]).parent().parent().parent().offset();
							var width = jQuery('#dropdown_button_'+id_parts[4]).parent().parent().parent().css('width');
							if(jQuery('#dropdown_menu_'+id_parts[4]).css('display') == 'none'){
								dropdown_portlets(jQuery('#dropdown_menu_'+id_parts[4]), offset, id_parts[4], width);
							}
						}
					}, 2000);
				});

				image.mouseout(function(){
					this.mouse_is_over = false;
				});

				jQuery('#dropdown_button_'+int3).click(function(){
					var id_parts = this.id.split('_');
					//var offset = jQuery('#'+this.id).parent().offset();
					//var offset = 400;
					var offset = jQuery('#'+this.id).parent().parent().parent().offset();
					var width = jQuery('#'+this.id).parent().parent().parent().css('width');
					dropdown_portlets(jQuery('#dropdown_menu_'+id_parts[2]), offset, id_parts[2], width);
				});

				jQuery('#dropdown_button_'+int3).mouseover(function(){
					var id = this.id;
					var this_image = this;
					this_image.mouse_is_over = true;
					setTimeout(function() {
						if(this_image.mouse_is_over){
							var id_parts = id.split('_');
							//var offset = jQuery('#dropdown_button_'+id_parts[2]).parent().offset();
							var offset = jQuery('#dropdown_button_'+id_parts[2]).parent().parent().parent().offset();
							var width = jQuery('#dropdown_button_'+id_parts[2]).parent().parent().parent().css('width');
							if(jQuery('#dropdown_menu_'+id_parts[2]).css('display') == 'none'){
								dropdown_portlets(jQuery('#dropdown_menu_'+id_parts[2]), offset, id_parts[2], width);
							}
						}
					}, 2000);
				});

				jQuery('#dropdown_button_'+int3).mouseout(function(){
					this.mouse_is_over = false;
				});

				ok_button.click(function(){
					var json_data = new Object();
					json_data['column_count'] = jQuery('[name=column_count]:checked').attr('value');

					var portlet_array = new Array();
					jQuery('[name=portlets]:checked').each(function(){
						portlet_array.push(jQuery(this).attr('value'));
					});
					json_data['portlets'] = portlet_array;

					jQuery.ajax({
					   url: 'commsy.php?cid='+window.ajax_cid+'&mod=ajax&fct=privateroom_home_configuration&output=json&do=save_config',
					   data: json_data,
					   success: function(msg){
						window.location = 'commsy.php?cid='+window.ajax_cid+'&mod=home&fct=index';
					   }
					});
				});
			}
		}
	}
});

function dropdown_portlets(object, offset, button, width){
	object.css('top', offset.top + 24);
	object.css('left', offset.left);
	var width_temp = width.substring(0,width.indexOf('px'));
	object.css('width', parseInt(width_temp) + 10);
	if(object.css('display') == 'none'){
		object.slideDown(150);
		jQuery('#dropdown_button_'+button).attr('src', 'images/commsyicons/dropdownmenu_up.png');
	} else if(object.css('display') == 'block'){
		object.slideUp(150);
		jQuery("[id^='dropdown_button_']").attr('src', 'images/commsyicons/dropdownmenu.png');
	}
}

jQuery(document).ready(function() {
	jQuery("[name=myroom_remove]").each(function (i) {
		var id = jQuery(this).parent().parent().attr('id');
		jQuery(this).click(function() {
			jQuery('#'+id).parent().remove();

			// Haken im DropDown-Menu entfernen!
			jQuery('[name=myrooms]:checked').each(function(){
				if(id == jQuery(this).attr('value')){
					jQuery(this).attr('checked', false);
				}
			});

			var json_data = new Object();

			var portlet_array = new Array();
			jQuery('[name=myrooms]:checked').each(function(){
				portlet_array.push(jQuery(this).attr('value'));
			});
			json_data['myrooms'] = portlet_array;

		    var portlet_columns = jQuery(".column");
		    for ( var int = 0; int < portlet_columns.length; int++) {
		    	var column_portlets = new Array();
				var portlet_column = jQuery(portlet_columns[int]);
				var portlets = portlet_column.children();
				for ( var int2 = 0; int2 < portlets.length; int2++) {
					var portlet = jQuery(portlets[int2]);
					//if(window.ajax_function == 'privateroom_home'){
					//	column_portlets.push(portlet.find('.portlet-content').find('div').attr('id'));
					//} else if (window.ajax_function == 'privateroom_myroom') {
						column_portlets.push(portlet.find('.portlet-header').attr('id'));
					//}
				}
				if(column_portlets.length == 0){
					column_portlets[0] = 'empty';
				}
				json_data['column_'+int] = column_portlets;
			}

			jQuery.ajax({
		       url: 'commsy.php?cid='+window.ajax_cid+'&mod=ajax&fct='+window.ajax_function+'&output=json&do=save_config',
		       data: json_data,
		       success: function(msg){
		       }
		    });
		});
	});
});

jQuery(document).ready(function() {
	if(typeof(dropDownMyRooms) !== 'undefined'){
		if(dropDownMyRooms.length){
			// how many different menus?
			var dropdown_menus = new Array();
			for ( var int = 0; int < dropDownMyRooms.length; int++) {
				var tempDropDownMenu = dropDownMyRooms[int];
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
				var scroll_li;
				var scroll_div;
				var scroll_ul;
				var scroll = false;

				for ( var int4 = 0; int4 < dropDownMyRooms.length; int4++) {
					var temp_menu_entry = dropDownMyRooms[int4];
					if(temp_menu_entry[0] == current_menu){
						if(temp_menu_entry[1] == 'seperator'){
							ul.append('<li class="dropdown_seperator"><hr class="dropdown_seperator"></li>');

						} else if (temp_menu_entry[1] == 'scroll_start') {
							scroll_ul = jQuery('<ul></ul>');
							scroll = true;
						} else if (temp_menu_entry[1] == 'scroll_end') {
							scroll_li = jQuery('<li></li>');
							scroll_div = jQuery('<div class="dropdown_scroll_myrooms"></div>');
							scroll_div.append(scroll_ul);
							scroll_li.append(scroll_div);
							ul.append(scroll_li);
							scroll = false;
						} else if (temp_menu_entry[1] == 'headline') {
							var tempActionChecked = temp_menu_entry[1];
							var tempActionText = temp_menu_entry[2];
							var tempActionValue = temp_menu_entry[3];
							if(!scroll){
								ul.append('<br/><li class="dropdown">'+tempActionText+'</li>');
							} else {
								scroll_ul.append('<br/><li class="dropdown">'+tempActionText+'</li>');
							}
						} else {
							var tempActionChecked = temp_menu_entry[1];
							var tempActionText = temp_menu_entry[2];
							var tempActionValue = temp_menu_entry[3];
							if(!scroll){
								ul.append('<li class="dropdown"><input type="checkbox" name="myrooms" value="'+tempActionValue+'" '+tempActionChecked+'>'+tempActionText+'</li>');
							} else {
								scroll_ul.append('<li class="dropdown"><input type="checkbox" name="myrooms" value="'+tempActionValue+'" '+tempActionChecked+'>'+tempActionText+'</li>');
							}
						}
					}
				}
				ul.append('<li class="dropdown_seperator"><hr class="dropdown_seperator"></li>');

				var ok_button = jQuery('<li class="dropdown" style="text-align:center;"><input type="submit" value="'+myroomSaveButton+'"></li>');
				ul.append(ok_button);

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
							//var offset = jQuery('#dropdown_button_'+id_parts[4]).parent().offset();
							var offset = jQuery('#dropdown_button_'+id_parts[4]).parent().parent().parent().offset();
							var width = jQuery('#dropdown_button_'+id_parts[4]).parent().parent().parent().css('width');
							if(jQuery('#dropdown_menu_'+id_parts[4]).css('display') == 'none'){
								dropdown_portlets(jQuery('#dropdown_menu_'+id_parts[4]), offset, id_parts[4], width);
							}
						}
					}, 2000);
				});

				image.mouseout(function(){
					this.mouse_is_over = false;
				});

				jQuery('#dropdown_button_'+int3).click(function(){
					var id_parts = this.id.split('_');
					//var offset = jQuery('#'+this.id).parent().offset();
					var offset = jQuery('#'+this.id).parent().parent().parent().offset();
					var width = jQuery('#'+this.id).parent().parent().parent().css('width');
					dropdown_portlets(jQuery('#dropdown_menu_'+id_parts[2]), offset, id_parts[2], width);
				});

				jQuery('#dropdown_button_'+int3).mouseover(function(){
					var id = this.id;
					var this_image = this;
					this_image.mouse_is_over = true;
					setTimeout(function() {
						if(this_image.mouse_is_over){
							var id_parts = id.split('_');
							//var offset = jQuery('#dropdown_button_'+id_parts[2]).parent().offset();
							var offset = jQuery('#dropdown_button_'+id_parts[2]).parent().parent().parent().offset();
							var width = jQuery('#dropdown_button_'+id_parts[2]).parent().parent().parent().css('width');
							if(jQuery('#dropdown_menu_'+id_parts[2]).css('display') == 'none'){
								dropdown_portlets(jQuery('#dropdown_menu_'+id_parts[2]), offset, id_parts[2], width);
							}
						}
					}, 2000);
				});

				jQuery('#dropdown_button_'+int3).mouseout(function(){
					this.mouse_is_over = false;
				});

				ok_button.click(function(){
					var json_data = new Object();

					var portlet_array = new Array();
					jQuery('[name=myrooms]:checked').each(function(){
						portlet_array.push(jQuery(this).attr('value'));
					});
					json_data['myrooms'] = portlet_array;

					jQuery.ajax({
					   url: 'commsy.php?cid='+window.ajax_cid+'&mod=ajax&fct=privateroom_myroom_configuration&output=json&do=save_config',
					   data: json_data,
					   success: function(msg){
						window.location = 'commsy.php?cid='+window.ajax_cid+'&mod=myroom&fct=index';
					   }
					});
				});
			}
		}
	}
});

jQuery(document).ready(function() {
	if(typeof(dropDownMyEntries) !== 'undefined'){
		if(dropDownMyEntries.length){
			// how many different menus?
			var dropdown_menus = new Array();
			for ( var int = 0; int < dropDownMyEntries.length; int++) {
				var tempDropDownMenu = dropDownMyEntries[int];
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

				for ( var int4 = 0; int4 < dropDownMyEntries.length; int4++) {
					var temp_menu_entry = dropDownMyEntries[int4];
					if(temp_menu_entry[0] == current_menu){
						if(temp_menu_entry[1] != 'seperator'){
							var tempActionChecked = temp_menu_entry[1];
							var tempActionText = temp_menu_entry[2];
							var tempActionValue = temp_menu_entry[3];
							ul.append('<li class="dropdown"><input type="checkbox" name="myentries" value="'+tempActionValue+'" '+tempActionChecked+'>'+tempActionText+'</li>');
						} else {
							ul.append('<li class="dropdown_seperator"><hr class="dropdown_seperator"></li>');
						}
					}
				}
				ul.append('<li class="dropdown_seperator"><hr class="dropdown_seperator"></li>');

				var ok_button = jQuery('<li class="dropdown" style="text-align:center;"><input type="submit" value="'+myentriesSaveButton+'"></li>');
				ul.append(ok_button);

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
							//var offset = jQuery('#dropdown_button_'+id_parts[4]).parent().offset();
							var offset = jQuery('#dropdown_button_'+id_parts[4]).parent().parent().parent().offset();
							var width = jQuery('#dropdown_button_'+id_parts[4]).parent().parent().parent().css('width');
							if(jQuery('#dropdown_menu_'+id_parts[4]).css('display') == 'none'){
								dropdown_portlets(jQuery('#dropdown_menu_'+id_parts[4]), offset, id_parts[4], width);
							}
						}
					}, 2000);
				});

				image.mouseout(function(){
					this.mouse_is_over = false;
				});

				jQuery('#dropdown_button_'+int3).click(function(){
					var id_parts = this.id.split('_');
					//var offset = jQuery('#'+this.id).parent().offset();
					var offset = jQuery('#'+this.id).parent().parent().parent().offset();
					var width = jQuery('#'+this.id).parent().parent().parent().css('width');
					dropdown_portlets(jQuery('#dropdown_menu_'+id_parts[2]), offset, id_parts[2], width);
				});

				jQuery('#dropdown_button_'+int3).mouseover(function(){
					var id = this.id;
					var this_image = this;
					this_image.mouse_is_over = true;
					setTimeout(function() {
						if(this_image.mouse_is_over){
							var id_parts = id.split('_');
							//var offset = jQuery('#dropdown_button_'+id_parts[2]).parent().offset();
							var offset = jQuery('#dropdown_button_'+id_parts[2]).parent().parent().parent().offset();
							var width = jQuery('#dropdown_button_'+id_parts[2]).parent().parent().parent().css('width');
							if(jQuery('#dropdown_menu_'+id_parts[2]).css('display') == 'none'){
								dropdown_portlets(jQuery('#dropdown_menu_'+id_parts[2]), offset, id_parts[2], width);
							}
						}
					}, 2000);
				});

				jQuery('#dropdown_button_'+int3).mouseout(function(){
					this.mouse_is_over = false;
				});

				ok_button.click(function(){
					var json_data = new Object();

					var portlet_array = new Array();
					jQuery('[name=myentries]:checked').each(function(){
						portlet_array.push(jQuery(this).attr('value'));
					});
					json_data['myentries'] = portlet_array;

					jQuery.ajax({
					   url: 'commsy.php?cid='+window.ajax_cid+'&mod=ajax&fct=privateroom_my_entries_configuration&output=json&do=save_config',
					   data: json_data,
					   success: function(msg){
					      //window.location = 'commsy.php?cid='+window.ajax_cid+'&mod=entry&fct=index';
						  window.location = window.location.href;
					   }
					});
				});
			}
		}
	}
});

jQuery(document).ready(function() {
	if(typeof(dropDownMyCalendar) !== 'undefined'){
		if(dropDownMyCalendar.length){
			// how many different menus?
			var dropdown_menus = new Array();
			for ( var int = 0; int < dropDownMyCalendar.length; int++) {
				var tempDropDownMenu = dropDownMyCalendar[int];
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

			// check for other dropdown's
			var dropdown_count_all = jQuery("img[id^='dropdown_button_']").length;
			var offset = 0;
			if(dropdown_count_all > dropdown_menus.length) {
				offset = dropdown_count_all;
			}

			// sort menu_entries to menus
			for ( var int3 = offset; int3 < dropdown_menus.length + offset; int3++) {
				var current_menu = dropdown_menus[int3-offset];

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
				var scroll_li;
				var scroll_div;
				var scroll_ul;
				var scroll = false;

				for ( var int4 = 0; int4 < dropDownMyCalendar.length; int4++) {
					var temp_menu_entry = dropDownMyCalendar[int4];
					if(temp_menu_entry[0] == current_menu){
						if (temp_menu_entry[1] == 'seperator') {
							ul.append('<li class="dropdown_seperator"><hr class="dropdown_seperator"></li>');
						} else if (temp_menu_entry[1] == 'seperator_75') {
							ul.append('<li class="dropdown_seperator"><hr class="dropdown_seperator_75"></li>');
						} else if (temp_menu_entry[1] == 'text') {
							ul.append('<li class="dropdown_text">'+temp_menu_entry[2]+'</li>');
						} else if (temp_menu_entry[1] == 'scroll_start') {
							scroll_ul = jQuery('<ul></ul>');
							scroll = true;
						} else if (temp_menu_entry[1] == 'scroll_end') {
							scroll_li = jQuery('<li></li>');
							scroll_div = jQuery('<div class="dropdown_scroll"></div>');
							scroll_div.append(scroll_ul);
							scroll_li.append(scroll_div);
							ul.append(scroll_li);
							scroll = false;
						} else {
							var tempActionChecked = temp_menu_entry[1];
							var tempActionText = temp_menu_entry[2];
							var tempActionValue = temp_menu_entry[3];
							if(!scroll){
							   ul.append('<li class="dropdown"><input type="checkbox" name="mycalendar" value="'+tempActionValue+'" '+tempActionChecked+'>'+tempActionText+'</li>');
							} else {
							   scroll_ul.append('<li class="dropdown"><input type="checkbox" name="mycalendar" value="'+tempActionValue+'" '+tempActionChecked+'>'+tempActionText+'</li>');
							}
						}
					}
				}
				ul.append('<li class="dropdown_seperator"><hr class="dropdown_seperator"></li>');

				var ok_button = jQuery('<li class="dropdown" style="text-align:center;"><input type="submit" value="'+mycalendarSaveButton+'"></li>');
				ul.append(ok_button);

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
							//var offset = jQuery('#dropdown_button_'+id_parts[4]).parent().offset();
							var offset = jQuery('#dropdown_button_'+id_parts[4]).parent().parent().parent().offset();
							var width = jQuery('#dropdown_button_'+id_parts[4]).parent().parent().parent().css('width');
							if(jQuery('#dropdown_menu_'+id_parts[4]).css('display') == 'none'){
								dropdown_portlets(jQuery('#dropdown_menu_'+id_parts[4]), offset, id_parts[4], width);
							}
						}
					}, 2000);
				});

				image.mouseout(function(){
					this.mouse_is_over = false;
				});

				jQuery('#dropdown_button_'+int3).click(function(){
					var id_parts = this.id.split('_');
					//var offset = jQuery('#'+this.id).parent().offset();
					var offset = jQuery('#'+this.id).parent().parent().parent().offset();
					var width = jQuery('#'+this.id).parent().parent().parent().css('width');
					dropdown_portlets(jQuery('#dropdown_menu_'+id_parts[2]), offset, id_parts[2], width);
				});

				jQuery('#dropdown_button_'+int3).mouseover(function(){
					var id = this.id;
					var this_image = this;
					this_image.mouse_is_over = true;
					setTimeout(function() {
						if(this_image.mouse_is_over){
							var id_parts = id.split('_');
							//var offset = jQuery('#dropdown_button_'+id_parts[2]).parent().offset();
							var offset = jQuery('#dropdown_button_'+id_parts[2]).parent().parent().parent().offset();
							var width = jQuery('#dropdown_button_'+id_parts[2]).parent().parent().parent().css('width');
							if(jQuery('#dropdown_menu_'+id_parts[2]).css('display') == 'none'){
								dropdown_portlets(jQuery('#dropdown_menu_'+id_parts[2]), offset, id_parts[2], width);
							}
						}
					}, 2000);
				});

				jQuery('#dropdown_button_'+int3).mouseout(function(){
					this.mouse_is_over = false;
				});

				ok_button.click(function(){
					var json_data = new Object();

					var portlet_array = new Array();
					jQuery('[name=mycalendar]:checked').each(function(){
						portlet_array.push(jQuery(this).attr('value'));
					});
					json_data['mycalendar'] = portlet_array;

					jQuery.ajax({
					   url: 'commsy.php?cid='+window.ajax_cid+'&mod=ajax&fct=privateroom_mycalendar_configuration&output=json&do=save_config',
					   data: json_data,
					   success: function(msg){
					      //window.location = 'commsy.php?cid='+window.ajax_cid+'&mod=entry&fct=index';
						  window.location = window.location.href;
					   }
					});
				});
			}
		}
	}
});

jQuery(document).ready(function() {
	jQuery("[name=mycalendar_remove]").each(function (i) {
		var id = jQuery(this).parent().parent().attr('id');
		jQuery(this).click(function() {
			jQuery('#'+id).parent().remove();
			jQuery('#'+id+'_preferences').remove();

			// Haken im DropDown-Menu entfernen!
			jQuery('[name=mycalendar]:checked').each(function(){
				if(id == jQuery(this).attr('value')){
					jQuery(this).attr('checked', false);
				}
			});

			var json_data = new Object();
		    var portlet_columns = jQuery(".column");
		    for ( var int = 0; int < portlet_columns.length; int++) {
		    	var column_portlets = new Array();
				var portlet_column = jQuery(portlet_columns[int]);
				var portlets = portlet_column.children();
				for ( var int2 = 0; int2 < portlets.length; int2++) {
					var portlet = jQuery(portlets[int2]);
					column_portlets.push(portlet.find('.portlet-header').attr('id'));
				}
				json_data['column_'+int] = column_portlets;
			}

			jQuery.ajax({
		       url: 'commsy.php?cid='+window.ajax_cid+'&mod=ajax&fct=privateroom_mycalendar&output=json&do=save_config',
			   data: json_data,
			   success: function(msg){
			   }
			});
		});
	});
});

function uploadify_onComplete(event, queueID, fileObj, response, data) {
	// add checkbox and file name to finished list
	jQuery("div[id='fileFinished']").append(
		jQuery("<input/>", {
			"type"		:	"checkbox",
			"checked"	:	"checked",
			"name"		:	"filelist[]",
			"value"		:	response
		}),
		jQuery("<span/>", {
			"style"		:	"font-size: 10pt;",
			"innerHTML"	:	fileObj.name
		}),
		jQuery("<br/>"
		)
	);

	// this is for browser compatibility
	jQuery("div[id='fileFinished'] input:last").attr('checked', 'checked');
}

var uploadify_onAllCompleteSubmitForm = false;

function uploadify_onAllComplete(event, data) {
	if(uploadify_onAllCompleteSubmitForm) {
		jQuery("td[class='buttonbar'] input[type='submit'][name='option']")[0].click();
	}

	return true;
}

/*
 * this functions checks if all files are uploaded when the form submit button is pressed
 */
jQuery(document).ready(function() {
	// if there is uploadify
	if(jQuery("object[id='uploadifyUploader']")) {
		// observe the submit button
		jQuery(jQuery("td[class='buttonbar'] input[type='submit'][name='option']")[0]).bind('click', function(eventObject) {
			// if there are files in upload queue
			if(	jQuery("div[id='uploadifyQueue'] > div").length > 0 &&
				uploadify_onAllCompleteSubmitForm == false) {
				// if all uploads completed, submit the form
				uploadify_onAllCompleteSubmitForm = true;

				// start upload process
				jQuery('#uploadify').uploadifyUpload();

				// stop form submit
				return false;
			} else {
				if(uploadify_onAllCompleteSubmitForm == true) {
					uploadify_onAllCompleteSubmitForm = false;
					jQuery("input[type='submit']")[0].unbind();
					return true;
				}
			}
			return true;
		});
	}

	return true;
});

jQuery(document).ready(function() {

	/* This is basic - uses default settings */

	//jQuery("a[rel^='lightbox']").fancybox();

	/* Using custom settings */

	//jQuery("a[rel^='lightbox']").fancybox({
	//	'hideOnContentClick': true
	//});

	/* Apply fancybox to multiple items */

	jQuery("a[rel^='lightbox']").fancybox({
		'transitionIn'	:	'fade',
		'transitionOut'	:	'fade',
		'speedIn'		:	600,
		'speedOut'		:	200,
		'overlayShow'	:	true,
		'titleShow'	    :   false,
		'autoScale'     :   true,
		'overlayOpacity':   0.8,
		'overlayColor'  :   '#000000',
		// CommSy
		'downloadShow'	:   true
		// CommSy
	});

});

function ListeID(id) {
    this.id = id;
}

jQuery(document).ready(function() {
	if(typeof(dropDownForLists) !== 'undefined'){
		if(dropDownForLists.length){
			for (var i = 0; i < dropDownForLists.length; i++) {
				var list_id = dropDownForLists[i][0];
				var dropDownMenus = dropDownForLists[i][1];
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
							image.attr('id',image.attr('id')+'_list_'+list_id+'_dropdown_menu_'+int3);
							image.attr('alt','');
							image.parent().attr('title','');

							var button = jQuery('<img id="list_'+list_id+'_dropdown_button_'+int3+'" class="dropdown_button" src="images/commsyicons/dropdownmenu.png" />');

							var html = jQuery('<div id="list_'+list_id+'_dropdown_menu_'+int3+'" class="dropdown_menu"></div>');
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
										var offset = jQuery('#list_'+id_parts[1]+'_dropdown_button_'+id_parts[4]).parent().offset();
										if(jQuery('#list_'+id_parts[1]+'_dropdown_menu_'+id_parts[4]).css('display') == 'none'){
											dropdown_liste(jQuery('#list_'+id_parts[1]+'_dropdown_menu_'+id_parts[4]), offset, id_parts[4], id_parts[1]);
										}
									}
								}, 2000);
							});

							image.mouseout(function(){
								this.mouse_is_over = false;
							});

							jQuery('#list_'+list_id+'_dropdown_button_'+int3).click(function(){
								var id_parts = this.id.split('_');
								var offset = jQuery('#'+this.id).parent().offset();
								dropdown_liste(jQuery('#list_'+id_parts[1]+'_dropdown_menu_'+id_parts[4]), offset, id_parts[4], id_parts[1]);
							});

							jQuery('#list_'+list_id+'_dropdown_button_'+int3).mouseover(function(){
								var id = this.id;
								var this_image = this;
								this_image.mouse_is_over = true;
								setTimeout(function() {
									if(this_image.mouse_is_over){
										var id_parts = id.split('_');
										var offset = jQuery('#list_'+id_parts[1]+'_dropdown_button_'+id_parts[4]).parent().offset();
										if(jQuery('#list_'+id_parts[1]+'_dropdown_menu_'+id_parts[4]).css('display') == 'none'){
											dropdown_liste(jQuery('#list_'+id_parts[1]+'_dropdown_menu_'+id_parts[4]), offset, id_parts[4], id_parts[1]);
										}
									}
								}, 2000);
							});

							jQuery('#list_'+list_id+'_dropdown_button_'+int3).mouseout(function(){
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
									jQuery(".dropdown_menu").slideUp(150);
									jQuery(".dropdown_button").attr('src', 'images/commsyicons/dropdownmenu.png');
								}
							});
						}
					}
				}
			}
		}
	}
});

function dropdown_liste(object, offset, button, liste){
	object.css('top', offset.top + 18);
	object.css('left', offset.left - 3);
	if(object.css('display') == 'none'){
		object.slideDown(150);
		jQuery('#list_'+liste+'_dropdown_button_'+button).attr('src', 'images/commsyicons/dropdownmenu_up.png');
	} else if(object.css('display') == 'block'){
		object.slideUp(150);
		jQuery('#list_'+liste+'_dropdown_button_'+button).attr('src', 'images/commsyicons/dropdownmenu.png');
	}
	object.mouseleave(function(){
		setTimeout(function() {
			object.slideUp(150);
			jQuery('#list_'+liste+'_dropdown_button_'+button).attr('src', 'images/commsyicons/dropdownmenu.png');
		}, 2000);
	});
}

/*
 * this script handles the room selection in the portals data upload form
 */
jQuery(document).ready(function() {
	jQuery("select[name='configuration_data_upload_room_select']").bind('change', function(eventObject) {
		var room_id = eventObject.target.value;

		// get according room limit from hidden fields
		var room_limit = jQuery("input[name='room_limit_" + room_id + "']").attr('value');

		// set limit in text field
		jQuery("input[name='configuration_data_upload_room_value']").attr('value', room_limit);
	});
});

/*
 * assessment vote function and tooltip initialization
 */
jQuery(document).ready(function() {
	// init sticky tooltip
	stickytooltip.init("*[data-tooltip]", "assessment_tooltip");
	
	var stars = new Object;
	stars = jQuery('span[id^=assessment_vote_star_] img');
	
	// store old status
	var old_status = new Object;
	stars.each(function() {
		old_status[jQuery(this).parent().attr('id')] = jQuery(this).attr('src');
	});
	
	// register mouseover
	stars.each(function() {
		jQuery(this).mouseover(function() {
			// get id of parent span
			var regexp = new RegExp('assessment_vote_star_(.*)');
			var matches = regexp.exec(jQuery(this).parent().attr('id'));
			
			// set all stars up to the hovered one to full
			for(var i = 0; i <= matches[1]; i++) {
				jQuery('span[id=assessment_vote_star_' + i + '] img').attr('src', 'images/commsyicons/32x32/star_select.png');
			}
		});
	});
	
	// register mouseout
	stars.each(function() {
		jQuery(this).mouseout(function() {
			// set all stars to there previous status
			stars.each(function() {
				jQuery(this).attr('src', old_status[jQuery(this).parent().attr('id')]);
			});
		});
	});
	
	// register click
	stars.each(function() {
		jQuery(this).click(function() {
			// perform ajax call to register voting
			var json_data = new Object();
		    json_data['do'] = 'vote';
		    json_data['item_id'] = getURLParam('iid');
		    
		    // get value of vote
		    var regexp = new RegExp('assessment_vote_star_(.*)');
		    var matches = regexp.exec(jQuery(this).parent().attr('id'));
		    json_data['vote'] = parseInt(matches[1]) + 1;
		    
		    jQuery.ajax({
			   url: 'commsy.php?cid='+window.ajax_cid+'&mod=ajax&fct=assessment&output=json',
			   data: json_data,
			   success: function(msg) {
		    	    var resultJSON = eval('(' + msg + ')');
	            	if (resultJSON === undefined) {
	            	} else {
	               		// page reload
	               		location.reload();
	            	}
	           }
			});
		});
	});
});

/*
 * assessment delete function
 */
jQuery(document).ready(function() {
	jQuery('a[id="assessment_delete_own"]').click(function() {
		// perform ajax call to delete own voting
		var json_data = new Object();
		json_data['do'] = 'delete_own';
		json_data['item_id'] = getURLParam('iid');
		
		jQuery.ajax({
			url: 'commsy.php?cid='+window.ajax_cid+'&mod=ajax&fct=assessment&output=json',
			   data: json_data,
			   success: function(msg) {
		    	    var resultJSON = eval('(' + msg + ')');
	            	if (resultJSON === undefined) {
	            	} else {
	               		// page reload
	               		location.reload();
	            	}
	           }
		});
	});
});

/*
 * this is the new uploadify error handler to provide multi-language support
 */
function uploadify_onError(event, queueID, fileObj, errorObj) {
	var error_text = '';
	jQuery.each(uploadify_errorLang, function() {
		if(this.type == errorObj.type) {
			error_text = this.text;
			return false;
		}
	});

	jQuery("#uploadify" + queueID + " .percentage").text(" - " + error_text);
	jQuery("#uploadify" + queueID).addClass('uploadifyError');

	return false;
}

/*
 * Room-wide search
 */
var roomwide_search_state = new Object();
roomwide_search_state['page'] = 0;
roomwide_search_state['last'] = 0;
jQuery(document).ready(function() {
    roomwide_search_extended_search(false);
	jQuery('#privateroom_home_roomwide_search_form').bind('submit', function(event){
		event.preventDefault();
		jQuery('#privateroom_home_roomwide_search_extended').hide();
		jQuery('#privateroom_home_roomwide_search_toggle').attr('src', jQuery('#privateroom_home_roomwide_search_toggle').attr('src').replace('less','more'));
		var table_height = jQuery('#privateroom_home_roomwide_search_table').height();
		if(table_height != 0){
		   jQuery('#privateroom_home_roomwide_search_div').css('height',table_height);
		}
		jQuery('#privateroom_home_roomwide_search_table').children().remove();
		jQuery('#privateroom_home_roomwide_search_div').css('height',50);
		jQuery('#privateroom_home_roomwide_search_table').append('<tr><td><div id="roomwide_search_animation"></div></td></tr>');
		json_data = new Object();
		json_data['search'] = jQuery('#privateroom_home_roomwide_search_text').val();
		json_data['page'] = roomwide_search_state['page'];
		json_data['interval'] = jQuery('[name=roomwide_search_interval]:checked').val();
		var item_types = new Array();
		jQuery('[name=roomwide_search_type]:checked').each(function(){
			item_types.push(jQuery(this).attr('value'));
		});
		json_data['roomwide_search_type'] = item_types;
		var rooms = new Array();
		jQuery('[name=roomwide_search_room]:checked').each(function(){
			rooms.push(jQuery(this).attr('value'));
		});
		json_data['roomwide_search_room'] = rooms;

	    jQuery.ajax({
	       url: 'commsy.php?cid='+window.ajax_cid+'&mod=ajax&fct=privateroom_roomwide_search&output=json',
		   data: json_data,
		   success: function(msg){
	          var resultJSON = eval('(' + msg + ')');
              if (resultJSON === undefined){
              }else{
            	if((resultJSON['roomwide_search_info']['page'] == '0') && (resultJSON['roomwide_search_info']['last'] > 0) && (resultJSON['roomwide_search_info']['page'] < resultJSON['roomwide_search_info']['last'])){
            		var first_link = '&lt;&lt;';
            		var prev_link = '&lt;';
            		var next_link = '<a href="#" onclick="roomwide_search_next()">&gt;</a>';
            		var last_link = '<a href="#" onclick="roomwide_search_last()">&gt;&gt;</a>';
            	} else if((resultJSON['roomwide_search_info']['page'] > '0') && (resultJSON['roomwide_search_info']['page'] < resultJSON['roomwide_search_info']['last'])){
            		var first_link = '<a href="#" onclick="roomwide_search_first()">&lt;&lt;</a>';
            		var prev_link = '<a href="#" onclick="roomwide_search_prev()">&lt;</a>';
            		var next_link = '<a href="#" onclick="roomwide_search_next()">&gt;</a>';
            		var last_link = '<a href="#" onclick="roomwide_search_last()">&gt;&gt;</a>';
            	} else if((resultJSON['roomwide_search_info']['page'] == resultJSON['roomwide_search_info']['last']) && (resultJSON['roomwide_search_info']['last'] > 0)){
            		var first_link = '<a href="#" onclick="roomwide_search_first()">&lt;&lt;</a>';
            		var prev_link = '<a href="#" onclick="roomwide_search_prev()">&lt;</a>';
            		var next_link = '&gt;';
            		var last_link = '&gt;&gt;';
            	} else {
            		var first_link = '&lt;&lt;';
            		var prev_link = '&lt;';
            		var next_link = '&gt;';
            		var last_link = '&gt;&gt;';
            	}
            	roomwide_search_state['page'] = resultJSON['roomwide_search_info']['page'];
            	roomwide_search_state['last'] = resultJSON['roomwide_search_info']['last'];
            	var from = resultJSON['roomwide_search_info']['from'];
        		var to = resultJSON['roomwide_search_info']['to'];
        		var count = resultJSON['roomwide_search_info']['count'];

        		jQuery('#privateroom_home_roomwide_search_table').children().remove();
        		if(to > 0){
        			jQuery('#privateroom_home_roomwide_search_table').append(jQuery('<tr><td colspan="2" style="height:20px;">'+first_link+' '+prev_link+' '+from+' '+roomwide_search_to+' '+to+' '+roomwide_search_from+' '+count+' '+next_link+' '+last_link+' '+'</td></tr>'));
        			for ( var int = 0; int < resultJSON['roomwide_search_results'].length; int++) {
						var json_element = resultJSON['roomwide_search_results'][int];
						var temp_icon_link = '<a href="commsy.php?cid='+json_element['cid']+'&mod='+json_element['type']+'&fct=detail&iid='+json_element['iid']+'&search_path=true" title="'+json_element['hover']+'" target="_self"><img src="images/commsyicons/32x32/'+json_element['type']+'.png" style="padding-right: 3px;" title="'+json_element['hover']+'"></a>';
						var temp_item_link = '<a href="commsy.php?cid='+json_element['cid']+'&mod='+json_element['type']+'&fct=detail&iid='+json_element['iid']+'&search_path=true" title="'+json_element['hover']+'" target="_self">'+json_element['title']+'</a>';
						var temp_room_link = '<a href="commsy.php?cid='+json_element['cid']+'&amp;mod=home&amp;fct=index" title="'+json_element['room_name']+'" target="_self">'+json_element['room_name']+'</a>';
						if((int % 2) == 0){
							html = jQuery('<tr class="list"><td class="even" style="height:20px; font-size:8pt;"><div style="float: left;">'+temp_icon_link+'</div>'+temp_item_link+' '+json_element['status']+'<br><span style="font-size: 8pt;">(Raum: '+temp_room_link+')</span></td></tr>');
						} else {
							html = jQuery('<tr class="list"><td class="odd" style="height:20px; font-size:8pt;"><div style="float: left;">'+temp_icon_link+'</div>'+temp_item_link+' '+json_element['status']+'<br><span style="font-size: 8pt;">(Raum: '+temp_room_link+')</span></td></tr>');
						}
						jQuery('#privateroom_home_roomwide_search_table').append(html);
					}
            	} else {
            		jQuery('#privateroom_home_roomwide_search_table').append(jQuery('<tr><td colspan="2" style="height:20px;">'+roomwide_search_empty_result+'</td></tr>'));
            	}
        		jQuery('#privateroom_home_roomwide_search_div').css('height','100%');
              }
		   }
		});
	});
});
function roomwide_search_first(){
	roomwide_search_state['page'] = 0;
	jQuery('#privateroom_home_roomwide_search_form').submit();
}
function roomwide_search_prev(){
	roomwide_search_state['page']--;
	jQuery('#privateroom_home_roomwide_search_form').submit();
}
function roomwide_search_next(){
	roomwide_search_state['page']++;
	jQuery('#privateroom_home_roomwide_search_form').submit();
}
function roomwide_search_last(){
	roomwide_search_state['page'] = roomwide_search_state['last'];
	jQuery('#privateroom_home_roomwide_search_form').submit();
}
function roomwide_search_extended_search(is_shown){
	   if (is_shown == false){
	      jQuery('#privateroom_home_roomwide_search_extended').hide();
	   }else{
	      jQuery('#privateroom_home_roomwide_search_toggle').attr('src', jQuery('#privateroom_home_roomwide_search_toggle').attr('src').replace('more','less'));
	   }
	   jQuery('#privateroom_home_roomwide_search_toggle').click(function(){
	      if(jQuery('#privateroom_home_roomwide_search_toggle').attr('src').toLowerCase().indexOf('less') >= 0){
	         jQuery('#privateroom_home_roomwide_search_extended').slideUp(200);
	         jQuery('#privateroom_home_roomwide_search_toggle').attr('src', jQuery('#privateroom_home_roomwide_search_toggle').attr('src').replace('less','more'));
	      } else {
	         jQuery('#privateroom_home_roomwide_search_extended').slideDown(200);
	         jQuery('#privateroom_home_roomwide_search_toggle').attr('src', jQuery('#privateroom_home_roomwide_search_toggle').attr('src').replace('more','less'));
	      }
	   });
	   jQuery('#privateroom_home_roomwide_search_toggle').mouseover(function(){
	      jQuery('#privateroom_home_roomwide_search_toggle').attr('src', jQuery('#privateroom_home_roomwide_search_toggle').attr('src').replace('.gif','_over.gif'));
	   });
	   jQuery('#privateroom_home_roomwide_search_toggle').mouseout(function(){
	      jQuery('#privateroom_home_roomwide_search_toggle').attr('src', jQuery('#privateroom_home_roomwide_search_toggle').attr('src').replace('_over.gif','.gif'));
	   });
	}
	
/* AJAX Search */
jQuery(document).ready(function() {
	var Search = {
			response: null,
			categories: null,
			last_search: "",
			input_search: null,
			trigger_display: false,
			mouse_was_over: false,
			numRubrics: null,
			lock: false,
			display: {
				offset: null,
				results: null,
				tags: null,
				response: null
			},
			config: {
				threshold: 3,
				categorizeByRubrics: true,
				resultsPerRubric: 10
			},
			
			/* init function */
			init: function() {
				if(typeof(indexed_search) !== 'undefined' && indexed_search === true) {
					this.input_search = jQuery('input[id="searchtext"]');
					
					// modify search form
					this.modifySearchForm();
					
					// register change function
					this.registerChange();
					
					// register submit function
					this.registerSubmit();
					
					// init tag filter array
					this.display.tags = new Array();
				}
			},
			
			modifySearchForm: function() {
				// turn autocomplete off
				this.input_search.attr('autocomplete', 'off');
				
				// change css style
				this.input_search.css('position', 'absolute');
				this.input_search.css('z-index', '5');
				this.input_search.css('background-color', 'transparent');
				
				// inject another input form for autocompletion
				jQuery('<input>', {
					"id":		"search_autocomplete",
					"style":	"width:220px; font-size:10pt; margin-bottom:0px; border:0 none; color:silver; margin: 0; outline: 0 none; padding: 3px 0px 1px 2px;"
				}).insertAfter(this.input_search);
			},
			
			registerChange: function() {
				// register change function
				this.input_search.keyup(function() {
					// get length of insert text
					var searchtext = jQuery(this).val();
					var length = searchtext.length;
					
					// reset autocomplete
					jQuery('input[id="search_autocomplete"]').attr('value', '');
					
					// trigger search if last_length was below treshhold
					if(length >= Search.config.threshold) {
						// wait for 300 ms and ensure, there is no new user input
						jQuery(this).oneTime(300, function() {
							if(searchtext == jQuery(this).val()) {
								// only process, if search text changed
								if(Search.last_search != searchtext) {
									Search.last_search = jQuery(this).val();
									
									// perform ajax call
									var json_data = new Object();
									json_data['do'] = 'search';
									json_data['selrubric'] = jQuery(this).parent().find('input[name="selrubric"]').val();
									json_data['searchtext'] = searchtext;
									
									// remove old results
						   			jQuery('div[class="search_fast_results"]').remove();
						   			
						   			// create fast search results div
									jQuery('<div/>', {
						   				'class':	"search_fast_results"
						   			})
						   				.append(jQuery('<div/>', {
						   					id:		"loading_animation"
						   				})
						   					.append(jQuery('<img/>', {
						   						src:		"javascript/jQuery/commsy_images/roomwide_search_animation.gif"
						   					}))).appendTo(jQuery('input[id="searchtext"]').parent().parent());
									
									jQuery.ajax({
										url: 'commsy.php?cid=' + getURLParam('cid') + '&mod=ajax&fct=search&output=json',
										   data: json_data,
										   success: function(data) {
											   	var ret = jQuery.parseJSON(data);
										   		if(ret) {
										   			console.log(ret);
										   			
										   			Search.response = ret.results;
										   			Search.categories = ret.categories;
										   			
										   			// autocompletion
										   			var first = true;
										   			var auto_complete = '';
										   			jQuery.each(Search.response, function(index, element) {
										   				if(first == true) {
										   					var tmp = element.complete;
										   					
										   				}
										   				if(searchtext == element.complete.slice(0, searchtext.length) && (element.complete.length < auto_complete.length || auto_complete == 0)) {
										   					auto_complete = element.complete;
										   				}
										   			});
										   			auto_complete = searchtext + auto_complete.slice(searchtext.length);
										   			jQuery('input[id="search_autocomplete"]').attr('value', auto_complete);
										   			
										   			// fadeout loading animation from fast search results
										   			jQuery('div[class="search_fast_results"] div[id="loading_animation"] img').fadeOut('slow', function() {
										   				jQuery('div[id="results"]').remove();
										   				
										   				// present fast search results
										   				jQuery('<div/>', {
										   					id:		"results"
										   				})
										   					.append(jQuery('<table/>', {
										   						'class':		"list"
										   					})
										   						.append(jQuery('<tr/>', {
										   							
										   						})
										   							.append(jQuery('<td/>', {
											   							'class':		"head"
											   						})))).appendTo('div[class="search_fast_results"]');
										   				
										   				// show the first ten results
										   				var count = 1;
										   				var css_class = 'odd';
										   				jQuery.each(Search.response, function(index, element) {
										   					if(count > 10) return false;
										   					
										   					// determ css class
										   					if(count % 2 == 0) {
										   						css_class = 'even';
										   					} else {
										   						css_class = 'odd';
										   					}
										   					
										   					jQuery('<tr/>', {
										   						
										   					})
											   					.append(jQuery('<td/>', {
											   						'class':		css_class
											   					})
											   						.append(jQuery('<a/>', {
											   							href:		'commsy.php?cid=' + getURLParam('cid') + '&mod=' + element.type + '&fct=detail&iid=' + index + '&search_path=true',
											   							text:		element.title
											   						}))).appendTo('div[class="search_fast_results"] div[id="results"] table');
										   					
										   					count++;
										   				});
										   				
										   				jQuery('<tr/>', {
										   					
										   				}).append(
										   					jQuery('<td/>', {
									   							'class':		'head'
									   						})).appendTo('div[class="search_fast_results"] div[id="results"] table');
									   					
									   					jQuery('<tr/>', {
									   						
									   					}).append(
									   						jQuery('<td/>', {
									   							
									   						}).append(
									   							jQuery('<a/>', {
									   								id:			'search_fast_show_all',
									   								text:		search_lang_fast_show_all
									   							}))).appendTo('div[class="search_fast_results"] div[id="results"] table');
										   				
										   				// register click function
										   				jQuery('a[id="search_fast_show_all"]').click(function() {
										   					Search.input_search.parent().submit();
										   				});
										   				
										   				// register hover function
														jQuery('div[class="search_fast_results"]').hover(
															function() {
																// mouse pointer enters
																Search.mouse_was_in = true;
															},
															function() {
																// mouse pointer leaves
																if(Search.mouse_was_in == true) {
																	var fast_results = jQuery(this);
																	fast_results.css('display', 'none');
																	
																	Search.input_search.hover(function() {
																		fast_results.css('display', 'block');
																		Search.mouse_was_in = false;
																	});
																}
															}
														);
										   				
										   				// trigger results in overlay if needed
											   			if(Search.trigger_display) {
											   				Search.displayResults();
											   				Search.displayCategories();
											   			}
										   			});
										   		}
								           }
									});
								}
							}
						});
					}
				});
			},
			
			registerSubmit: function() {
				// register submit function
				jQuery('input[id="search_autocomplete"]').parent().submit(function() {
					// skip, if search field length is below threshold
					if(Search.input_search.val().length < Search.config.threshold) return false;
					
					// skip, if window was not closed
					if(jQuery('div[id="search_overlay_front"]').length != 0) return false;
					
					Search.display.results = null;
					Search.display.offset = null;
					
					jQuery('<div/>', {
						id: "search_overlay_background",
						style: "position: absolute; left: 0px; top: 0px; z-index: 900; width: 100%; height: 1000%; background-color: #FFFFFF; opacity: 0.7;"
					}).appendTo('body');
					
					jQuery('<div/>', {
						id: "search_overlay_front",
						style: "position: absolute; left: 0px; top: 0px; z-index: 1000; width: 100%; height: 100%;"
					}).append(
						jQuery('<div/>', {
							style: "margin-left: 20%; margin-top: 40px; width: 60%;"
						}).append(
							jQuery('<div/>', {
								id: 'profile_content',
								style: '-moz-border-radius: 5px 5px 0px 0px; box-shadow: 8px 8px #dddddd; display: none;'
							}).append(
								jQuery('<div/>', {
									
								}).append(
									jQuery('<div/>', {
										'class': 'profile_title',
										style: 'float: right;'
									}).append(
										jQuery('<a/>', {
											id: 'search_overlay_close',
											'class': 'titlelink',
											href: '#',
											text: 'X'
										}))).append(
									jQuery('<h2/>', {
										id: 'profile_title',
										text: search_lang_results
									}))).append(
								jQuery('<div/>', {
									id: 'search_overlay_result_message',
									style: 'display: none;'
								})).append(
									jQuery('<img/>', {
										id: "search_overlay_loading_animation",
										src:		"javascript/jQuery/commsy_images/roomwide_search_animation.gif"
									})).append(
									jQuery('<div/>', {
										id: 'search_overlay_results',
										style: 'width: 80%; float: left;'
									})).append(
									jQuery('<div/>', {
										id: 'search_overlay_config',
										style: 'width: 20%; float: right;'
									})).append(
									jQuery('<div/>', {
										style: 'clear: both;'
									})))).appendTo('body');
					
					// generate config box
					jQuery('<div/>', {
						text: search_lang_view_options,
						'class': 'search_overlay'
					}).append(
						jQuery('<div/>', {
							
						}).append(
							jQuery('<div/>', {
								'class': 'search_overlay_config_label',
								text: search_lang_view_options_rubric
							}).append(
								jQuery('<input/>', {
									id: 'search_overlay_config_form_categorize',
									'class': 'search_overlay_config_form',
									type: 'checkbox'
								}))).append(
							jQuery('<div/>', {
								'class': 'search_overlay_config_label'
							}).append(
								jQuery('<span/>', {
									id: 'search_overlay_config_form_per_text',
									text: search_lang_view_options_per_rubric
								})).append(
								jQuery('<input/>', {
									id: 'search_overlay_config_form_per_rubric',
									'class': 'search_overlay_config_form',
									type: 'input',
									size: 2,
									style: 'margin: 1px;',
									value: Search.config.resultsPerRubric
								}))).append(
							jQuery('<div/>', {
								style: 'clear:both; '
							}))).appendTo('div[id="search_overlay_config"]');
					
					// generate restriction box
					jQuery('<div/>', {
						text: search_lang_restriction_options,
						'class': 'search_overlay'
					}).append(
						jQuery('<div/>', {
							
						}).append(
							jQuery('<div/>', {
								'class': 'search_overlay_config_label',
								text: search_lang_restriction_categories
							}).append(
								jQuery('<div/>', {
									id: 'tag_tree',
									style: 'border-top: 1px solid black; margin-right: 3px;'
								}))).append(
							jQuery('<div/>', {
								style: 'clear:both; '
							}))).appendTo('div[id="search_overlay_config"]');
					
					// set default values
					if(Search.config.categorizeByRubrics == true) {
						jQuery('input[id="search_overlay_config_form_categorize"]').attr('checked', 'checked');
					}
					
					// register events
					jQuery('a[id="search_overlay_close"]').click(function() {
						jQuery('div[id="search_overlay_background"]').remove();
						jQuery('div[id="search_overlay_front"]').remove();
					});
					jQuery('input[id="search_overlay_config_form_categorize"]').click(function() {
						// clear content of search_overlay_results
						jQuery('div[id="search_overlay_results"] div').remove();
						
						// update config
						Search.config.categorizeByRubrics = jQuery(this).attr('checked');
						Search.display.results = null;
						Search.display.offset = null;
						if(Search.config.categorizeByRubrics == false) {
							// view changed from categorized to uncategorized
							Search.config.resultsPerRubric *= Search.numRubrics;
							jQuery('span[id="search_overlay_config_form_per_text"]').text(search_lang_view_options_per_page);
						} else {
							// view changed from uncategorized to categorized
							Search.config.resultsPerRubric /= Search.numRubrics;
							Search.config.resultsPerRubric = parseInt(Search.config.resultsPerRubric);
							jQuery('span[id="search_overlay_config_form_per_text"]').text(search_lang_view_options_per_rubric);
						}
						jQuery('input[id="search_overlay_config_form_per_rubric"]').val(Search.config.resultsPerRubric);
						
						// redraw
						Search.displayResults();
					});
					jQuery('input[id="search_overlay_config_form_per_rubric"]').change(function() {
						jQuery(this).oneTime(300, function() {
							// clear content of search_overlay_results
							jQuery('div[id="search_overlay_results"] div').remove();
							
							// update config
							Search.config.resultsPerRubric = parseInt(jQuery(this).val());
							Search.display.results = null;
							Search.display.offset = null;
							
							// redraw
							Search.displayResults();
						});
					});
					
					// display results if there is already a response
					if(Search.response != null) {
						if(Search.last_search != Search.input_search.val()) {
							Search.trigger_display = true;
						} else {
							Search.displayResults();
							Search.displayCategories();
						}
					} else {
						Search.trigger_display = true;
					}
					
					// fade in
					jQuery('div[id="profile_content"]').fadeIn();
					
					// cancel page reload
					return false;
				});
			},
			
			displayResultsByRubric: function() {
				// display results
				var empty = true;
				var offsetEnd = null;
				jQuery.each(search_rubrics, function(index, element) {
					// are there results for actual rubric?
					empty = true;
					jQuery.each(Search.display.response, function(i, e) {
						if(e.type == element) {
							empty = false;
							return false;
						}
					});
					
					// display rubric
					if(!empty) {
						offsetEnd = Search.display.offset[index] + Search.config.resultsPerRubric;
						if(offsetEnd > Search.display.results[index]) {
							offsetEnd = Search.display.results[index];
						}
						jQuery('<div/>', {
							id: 'search_overlay_rubric_' + element,
							'class': 'search_overlay',
							text: search_lang_rubrics[index]
						}).append(
							jQuery('<span/>', {
								text: Search.display.offset[index] + 1 + ' - ' + offsetEnd + '(' + Search.display.results[index] + ')',
								style: 'margin-left: 5px;'
							})).append(
							jQuery((Search.display.offset[index] == 0 ? '<span/>' : '<a/>'), {
								id: 'search_overlay_rubric_page_first_' + index,
								text: '<<',
								style: 'margin-left: 10px;'
							})).append(
							jQuery('<span/>', {
								text: '|'
							})).append(
							jQuery((Search.display.offset[index] == 0 ? '<span/>' : '<a/>'), {
								id: 'search_overlay_rubric_page_prev_' + index,
								text: '<'
							})).append(
							jQuery('<span/>', {
								text: '|'
							})).append(
							jQuery((offsetEnd >= Search.display.results[index] ? '<span/>' : '<a/>'), {
								id: 'search_overlay_rubric_page_next_' + index,
								text: '>'								
							})).append(
							jQuery('<span/>', {
								text: '|'
							})).append(
							jQuery((offsetEnd >= Search.display.results[index] ? '<span/>' : '<a/>'), {
								id: 'search_overlay_rubric_page_last_' + index,
								text: '>>'
							})).append(
							jQuery('<img/>', {
								src: 'images/arrow_down.gif',
								style: 'float: right;'
							})).append(
							jQuery('<table/>', {
								
							})).appendTo(jQuery('div[id="search_overlay_results"]'));
						
						// display results for rubric
						var count = 1;
						var offset_count = 0;
		   				var css_class = 'odd';
						jQuery.each(Search.display.response, function(index_entry, element_entry) {
							// skip, if rubric does not match
							if(element_entry.type == element) {
								// skip if offset is not reached
								if(offset_count >= Search.display.offset[index]) {
									// break if limit is reached
									if(count > Search.config.resultsPerRubric) return false;
									
									// determ css class
				   					if(count % 2 == 0) {
				   						css_class = 'even';
				   					} else {
				   						css_class = 'odd';
				   					}
				   					
				   					// file images
				   					var images = '';
				   					jQuery.each(element_entry.file_list, function(file_index, file_entry) {
				   						images += '<img src="' + file_entry.icon + '"/>';
				   					});
				   					
				   					// append table content
				   					jQuery('<tr/>', {
				   					}).append(
				   						jQuery('<td/>', {
				   							'class': css_class,
				   							style: 'width: 25%;'
				   						}).append(
			   								jQuery('<a/>', {
				   								text: element_entry.title,
				   								href:		'commsy.php?cid=' + getURLParam('cid') + '&mod=' + element_entry.type + '&fct=detail&iid=' + index_entry + '&search_path=true'
				   							})).append(
				   							jQuery('<span/>', {
				   								html: images
				   							}))).append(
				   						jQuery('<td/>', {
				   							'class': css_class,
				   							text: element_entry.modification_date
				   						})).appendTo(jQuery('div[id="search_overlay_rubric_' + element + '"] table'));
				   					
				   					count++;
								} else {
									offset_count++;
								}
							}
						});
					}
				});
				
				// add href to all links
				jQuery('a[id^="search_overlay_rubric_page_"]').attr('href', '#');
				
				// page event handling
				jQuery('a[id^="search_overlay_rubric_page_"]').click(function() {
					var id = jQuery(this).attr('id');
					/search_overlay_rubric_page_(.*?)_(.*)/.exec(id);
					var func = RegExp.$1;
					var index = RegExp.$2;
					
					switch(func) {
						case "first":
							Search.display.offset[index] = 0;
							break;
						case "prev":
							Search.display.offset[index] -= Search.config.resultsPerRubric;
							break;
						case "next":
							Search.display.offset[index] += Search.config.resultsPerRubric;
							break;
						case "last":
							Search.display.offset[index] = parseInt(Search.display.results[index] / Search.config.resultsPerRubric) * Search.config.resultsPerRubric;
							break;
					}
					
					// clear content of search_overlay_results
					jQuery('div[id="search_overlay_results"] div').remove();
					
					// redraw
					Search.displayResults();
				});
			},
			
			displayResultsNonCategorized: function() {
				var offsetEnd = Search.display.offset + Search.config.resultsPerRubric;
				if(offsetEnd > Search.display.results) {
					offsetEnd = Search.display.results;
				}
				
				// display results
				jQuery('<div/>', {
					id: 'search_overlay_rubric_none',
					'class': 'search_overlay',
					text: search_lang_uncategorized
				}).append(
					jQuery('<span/>', {
						text: Search.display.offset + 1 + ' - ' + offsetEnd + '(' + Search.display.results + ')',
						style: 'margin-left: 5px;'
					})).append(
					jQuery((Search.display.offset == 0 ? '<span/>' : '<a/>'), {
						id: 'search_overlay_rubric_page_first',
						text: '<<',
						style: 'margin-left: 10px;'
					})).append(
					jQuery('<span/>', {
						text: '|'
					})).append(
					jQuery((Search.display.offset == 0 ? '<span/>' : '<a/>'), {
						id: 'search_overlay_rubric_page_prev',
						text: '<'
					})).append(
					jQuery('<span/>', {
						text: '|'
					})).append(
					jQuery((offsetEnd >= Search.display.results ? '<span/>' : '<a/>'), {
						id: 'search_overlay_rubric_page_next',
						text: '>'								
					})).append(
					jQuery('<span/>', {
						text: '|'
					})).append(
					jQuery((offsetEnd >= Search.display.results ? '<span/>' : '<a/>'), {
						id: 'search_overlay_rubric_page_last',
						text: '>>'
					})).append(
					jQuery('<img/>', {
						src: 'images/arrow_down.gif',
						style: 'float: right;'
					})).append(
					jQuery('<table/>', {
						
					})).appendTo(jQuery('div[id="search_overlay_results"]'));
				
				// display results for rubric
				var count = 1;
   				var css_class = 'odd';
   				var offset_count = 0;
				jQuery.each(Search.display.response, function(index_entry, element_entry) {
					if(offset_count >= Search.display.offset) {
						// break if limit is reached
						if(count > Search.config.resultsPerRubric) return false;
						
						// determ css class
	   					if(count % 2 == 0) {
	   						css_class = 'even';
	   					} else {
	   						css_class = 'odd';
	   					}
	   					
	   					// file images
	   					var images = '';
	   					jQuery.each(element_entry.file_list, function(file_index, file_entry) {
	   						images += '<img src="' + file_entry.icon + '"/>';
	   					});
	   					
	   					// append table content
	   					jQuery('<tr/>', {
	   					}).append(
	   						jQuery('<td/>', {
	   							'class': css_class,
	   							style: 'width: 25%;'
	   						}).append(
	   							jQuery('<a/>', {
	   								text: element_entry.title,
	   								href:		'commsy.php?cid=' + getURLParam('cid') + '&mod=' + element_entry.type + '&fct=detail&iid=' + index_entry + '&search_path=true'
	   							})).append(
	   							jQuery('<span/>', {
	   								html: images
	   							}))).append(
	   						jQuery('<td/>', {
	   							'class': css_class,
	   							text: element_entry.modification_date
	   						})).appendTo(jQuery('div[id="search_overlay_rubric_none"] table'));
	   					
	   					count++;
					} else {
						offset_count++;
					}
				});
				
				// add href to all links
				jQuery('a[id^="search_overlay_rubric_page_"]').attr('href', '#');
				
				// page event handling
				jQuery('a[id^="search_overlay_rubric_page_"]').click(function() {
					var id = jQuery(this).attr('id');
					/search_overlay_rubric_page_(.*)/.exec(id);
					var func = RegExp.$1;
					
					switch(func) {
						case "first":
							Search.display.offset = 0;
							break;
						case "prev":
							Search.display.offset -= Search.config.resultsPerRubric;
							break;
						case "next":
							Search.display.offset += Search.config.resultsPerRubric;
							break;
						case "last":
							Search.display.offset = parseInt(Search.display.results / Search.config.resultsPerRubric) * Search.config.resultsPerRubric;
							break;
					}
					
					// clear content of search_overlay_results
					jQuery('div[id="search_overlay_results"] div').remove();
					
					// redraw
					Search.displayResults();
				});
			},
			
			filter: function(result) {
				var ret = false;
				
				// tags
				if(Search.display.tags.length == 0) {
					ret = true;
				} else {
					jQuery.each(result.tags, function(result_tag_index, result_tag_element) {
						jQuery.each(Search.display.tags, function(display_tag_index, display_tag_element) {
							if(result_tag_element == display_tag_element) {
								ret = true;
								
								// break
								return false;
							}
						});
						
						// break
						if(ret == true) return false;
					});
				}
				
				return ret;
			},
			
			appendTagTree: function(dom_element, root_tag) {
				if(root_tag.children.length > 0) {
					// append <ul>
					jQuery('<ul/>', {
						
					}).appendTo(dom_element);
					var ul = dom_element.find('ul');
					
					jQuery.each(root_tag.children, function(index, element) {
						// append <li/>
						jQuery('<li/>', {
							
						}).append(
							jQuery('<a/>', {
								text: element.title,
								id: 'search_tag_restriction_' + element.id,
								href: '#'
							})).appendTo(ul);
						
						// recursion
						var li = ul.find('li:last');
						Search.appendTagTree(li, element);
					});
				}
			},
			
			displayCategories: function() {
				// insert dynatree for categories
				Search.appendTagTree(jQuery('div[id="search_overlay_config"] div[id="tag_tree"]'), Search.categories);
				
				// handle dynatree
				if(jQuery('[id^=tag_tree]').length){
					jQuery.ui.dynatree.nodedatadefaults["icon"] = false;
					jQuery('[id^=tag_tree]').each(function(){
						jQuery(this).dynatree({
							fx: { height: "toggle", duration: 200 },
							checkbox: true,
							onClick: function(dtnode, event) {
								var target = jQuery(event.target);
								
								// separate tag id
								var result = target.attr('id').match(/search_tag_restriction_([0-9]*)/);
								var tag_id = parseInt(result[1]);
								
								// check if tag is already set
								var insert = true;
								if(jQuery.inArray(tag_id, Search.display.tags) != -1) insert = false;
								
								// insert / remove
								if(insert == true) {
									// add tag id
									Search.display.tags.push(tag_id);
									
									// make text bold and underlined
									target.css('font-weight', 'bold');
									target.css('text-decoration', 'underline');
								} else {
									// remove tag id
									var tmp = new Array();
									jQuery.each(Search.display.tags, function(index, element) {
										if(element != tag_id) {
											tmp.push(element);
										}
									});
									Search.display.tags = tmp;
									
									// remove text styles
									target.css('font-weight', 'normal');
									target.css('text-decoration', 'none');
								}
								
								// redraw
								jQuery('div[id="search_overlay_results"] div').remove();
								Search.display.results = null;
								Search.display.offset = null;
								Search.displayResults();
								
								
								
								// prevent highlighted background
								return false;
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
			},
			
			displayResults: function() {
				// remove loading animation
				jQuery('img[id="search_overlay_loading_animation"]').remove();
				
				// filter results
				this.display.response = new Array();
				jQuery.each(Search.response, function(index, element) {
					if(Search.filter(element)) Search.display.response.push(element);
				});
				
				// determe some values
				var numRubrics = 0;
				var tmp = false;
				var init = false;
				var numResults = 0;
				if(Search.display.results == null || Search.numRubrics == null) {
					init = true;
				}
				if(Search.config.categorizeByRubrics == true) {
					jQuery.each(search_rubrics, function(index, element) {
						// init results and offset arrays
						if(Search.display.results == null) {
							Search.display.results = new Array();
						}
						if(init == true && typeof(Search.display.results[index]) == 'undefined') {
							Search.display.results[index] = 0;
						}
						
						if(Search.display.offset == null) {
							Search.display.offset = new Array();
						}
						if(init == true && typeof(Search.display.offset[index]) == 'undefined') {
							Search.display.offset[index] = 0;
						}
						
						if(init == true) {
							// are there results for actual rubric?
							tmp = false;
							
							jQuery.each(Search.display.response, function(i, e) {
								if(e.type == element) {
									Search.display.results[index] += 1;
									tmp = true;
									numResults++;
								}
							});
							if(tmp == true) {
								numRubrics++;
							}
						}
					});
					if(init == true) {
						Search.numRubrics = numRubrics;
						Search.numResults = numResults;
					}
				} else {
					if(init == true) {
						jQuery.each(search_rubrics, function(index, element) {
							// are there results for actual rubric?
							jQuery.each(Search.display.response, function(i, e) {
								if(e.type == element) {
									numRubrics++;
									return false;
								}
							});
						});
						
						var tmp = 0;
						jQuery.each(Search.display.response, function(i, e) {
							numResults++;
						});
						
						Search.display.results = numResults;
						Search.display.offset = 0;
					}
				}
				
				// display categorized or not
				if(this.config.categorizeByRubrics == true) {
					this.displayResultsByRubric();
				} else {
					this.displayResultsNonCategorized();
				}
				
				// set num of results and rubrics
				var result_message = jQuery('div[id="search_overlay_result_message"]');
				result_message.text(search_lang_result_message);
				result_message.text(result_message.text().replace(/%1/, Search.numResults));
				result_message.text(result_message.text().replace(/%2/, Search.numRubrics));
				result_message.show();
				
				// register events
				jQuery('div[id^="search_overlay_rubric_"] img[src^="images/arrow"]').mouseover(function() {
					if(jQuery(this).attr('src') == 'images/arrow_up.gif') {
						jQuery(this).attr('src', 'images/arrow_up_over.gif');
					} else {
						jQuery(this).attr('src', 'images/arrow_down_over.gif');
					}
				});
				jQuery('div[id^="search_overlay_rubric_"] img[src^="images/arrow"]').mouseout(function() {
					if(jQuery(this).attr('src') == 'images/arrow_up_over.gif') {
						jQuery(this).attr('src', 'images/arrow_up.gif');
					} else {
						jQuery(this).attr('src', 'images/arrow_down.gif');
					}
				});
				jQuery('div[id^="search_overlay_rubric_"] img[src^="images/arrow"]').click(function() {
					if(jQuery(this).attr('src') == 'images/arrow_up_over.gif') {
						// expand
						jQuery(this).parent().find('table').slideDown();
						jQuery(this).attr('src', 'images/arrow_down_over.gif');
					} else {
						// collapse
						jQuery(this).parent().find('table').slideUp();
						jQuery(this).attr('src', 'images/arrow_up_over.gif');
					}
				});
				
				// unset
				Search.trigger_display = false;
			}
	};
	
	Search.init();
});

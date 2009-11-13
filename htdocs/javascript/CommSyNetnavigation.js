// $Id$
//
// Release $Name$
//
// Copyright (c)2002-2007 Dirk Bl�ssl, Matthias Finck, Dirk Fust, Franz Gr�nig,
// Oliver Hankel, Iver Jackewitz, Michael Janneck, Martti Jeenicke,
// Detlev Krause, Irina L. Marinescu, Frithjof Meyer, Timo Nolte, Bernd Pape,
// Edouard Simon, Monique Strauss, Jos� Manuel Gonz�lez V�zquez
//
//    This file is part of CommSy.
//
//    CommSy is free software; you can redistribute it and/or modify
//    it under the terms of the GNU General Public License as published by
//    the Free Software Foundation; either version 2 of the License, or
//    (at your option) any later version.
//
//    CommSy is distributed in the hope that it will be useful,
//    but WITHOUT ANY WARRANTY; without even the implied warranty of
//    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//    GNU General Public License for more details.
//
//    You have received a copy of the GNU General Public License
//    along with CommSy.

/*********************************************************************/
//       These functions are originally written by
//	www.dhtmlgoodies.com
//	Alf Magne Kalleland
//	They are adopted for use with CommSy
/*********************************************************************/

   var netnavigation_slide_speed = 50;	// Speed of slide
   var rubric_index;
   var count_rubrics = new Array();
   var path_info = false;

   var savedActiveNetnavigationPane = false;
   var savedActiveNetnavigationSub = false;
   var currentlyExpandedRubric = false;
   var netnavigation_currentDirection = new Array();


   function showHideRubricContent(e,inputObj){
      if(!inputObj){
         inputObj = this;
      }
      var my_array = inputObj.id.split('_');
      var number = my_array[1].replace(/[^0-9]/g,'');
      var number_item_id = my_array[0].replace(/[^0-9]/g,'');
      for(var no=0;no<count_rubrics[number_item_id];no++){
         var img = document.getElementById('ShowHideRubricButton' + number_item_id + '_' + no);
         var temp_number_array = img.id.split('_');
         var numericId = temp_number_array[1].replace(/[^0-9]/g,'');
         var obj = document.getElementById('rubricContent' + number_item_id + '_' + numericId);
         if(img.src.toLowerCase().indexOf('up') >= 0){
	   img.src = img.src.replace('up','down');
	   obj.style.display='block';
	   netnavigation_currentDirection[obj.id] = (netnavigation_slide_speed*-1);
//            new Effect.SlideUp(obj.id,{duration: 2});
	   slideRubric((netnavigation_slide_speed*-1), obj.id);
         }else if (number == no){
	   img.src = img.src.replace('down','up');
	   obj.style.display='block';
	   netnavigation_currentDirection[obj.id] = netnavigation_slide_speed;
//            new Effect.SlideDown(obj.id,{duration: 2});
	   slideRubric((netnavigation_slide_speed), obj.id);
         }
      }
      return true;
   }



   function slideRubric(slideValue,id){
      if(slideValue != netnavigation_currentDirection[id]){
         return false;
      }
      var activePane = document.getElementById(id);
      if(activePane == savedActiveNetnavigationPane){
         var subDiv = savedActiveNetnavigationSub;
      }else{
         var subDiv = activePane.getElementsByTagName('DIV')[0];
      }
      savedActiveNetnavigationPane = activePane;
      savedActiveNetnavigationSub = subDiv;

      var height = activePane.offsetHeight;
      var innerHeight = subDiv.offsetHeight;
      height+=slideValue;
      if(height<0){
         height=0;
      }
      if(height > innerHeight){
         height = innerHeight;
      }

      if(document.all){
         activePane.style.filter = 'alpha(opacity=' + Math.round((height / subDiv.offsetHeight)*100) + ')';
      }else{
         var opacity = (height / subDiv.offsetHeight);
	if(opacity==0){
            opacity=0.01;
         }
	if(opacity==1){
            opacity = 0.99;
         }
	activePane.style.opacity = opacity;
      }

      if(slideValue<0){
         activePane.style.height = height + 'px';
	subDiv.style.top = height - subDiv.offsetHeight + 'px';
	if(height>0){
	   setTimeout('slideRubric(' + slideValue + ',"' + id + '")',10);
	}else{
	   if(document.all){
               activePane.style.display='none';
            }
	}
      }else{
         subDiv.style.top = height - subDiv.offsetHeight + 'px';
	activePane.style.height = height + 'px';
	if(height<innerHeight){
	   setTimeout('slideRubric(' + slideValue + ',"' + id + '")',10);
	}
      }
   }


   function initDhtmlNetnavigation(element_id,panelTitles,rubric, item_id){
      var netnavigation = document.getElementById(element_id + item_id);
      var netnavigation_divs = netnavigation.getElementsByTagName('DIV');
      var divs = netnavigation_divs[0].getElementsByTagName('DIV');
      var end = divs.length;
      rubric_index=0;
      for(var no=0;no<divs.length;no++){
         if(divs[no].className == element_id + '_panel'){

            var outerContentDiv = document.createElement('DIV');
	   var contentDiv = divs[no].getElementsByTagName('DIV')[0];
	   outerContentDiv.appendChild(contentDiv);

	   outerContentDiv.id = 'rubricContent' + item_id +'_'+ rubric_index;
	   outerContentDiv.className = 'panelContent';

	   var topBar = document.createElement('DIV');
   	   var img = document.createElement('IMG');
	   img.id = 'ShowHideRubricButton' + item_id +'_'+ rubric_index;
	   img.src = 'images/arrow_netnavigation_up.gif';
	   topBar.appendChild(img);

            var span = document.createElement('SPAN');
	   span.innerHTML = panelTitles[rubric_index].replace(/&COMMSYDHTMLTAG&/g,'</');
	   topBar.appendChild(span);

	   topBar.style.position = 'relative';
            img.onclick = showHideRubricContent;
	   img.onmouseover = mouseoverTopbar;
	   img.onmouseout = mouseoutTopbar;
            if(rubric_index != rubric){
	      outerContentDiv.style.height = '0px';
	      contentDiv.style.top = 0 - contentDiv.offsetHeight + 'px';
	      img.src = 'images/arrow_netnavigation_down.gif';
	   }

	   topBar.className='tpBar';
	   divs[no].appendChild(topBar);
	   divs[no].appendChild(outerContentDiv);
	   rubric_index++;
	}
         count_rubrics[item_id] = rubric_index;
      }
   }
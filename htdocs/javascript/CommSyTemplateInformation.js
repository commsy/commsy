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

   var context_id;

   function cs_toggle_template(){
      var id = document.getElementsByName('template_select').namedItem('template_select');
      alert(id.value);
      document.getElementById('template_extention').innerHTML = template_array[id.value];
      if (id.value != '-1'){
         showTemplateInformation();
	  }
   }

   function initToggleTemplate(id){
      context_id = id;
      window.addEvent('domready', function(){
         var id = document.getElementsByName('template_select').namedItem('template_select');
         if (id.value != '-1'){
            document.getElementById('template_extention').innerHTML = template_array[id.value];
            showTemplateInformation();
         }
      });
   }

   function showTemplateInformation(){
      var mySlide = new Fx.Slide('template_information_box',{duration: 200});
      mySlide.hide();
      $('toggle'+context_id).addEvent('click', function(e){
         e = new Event(e);
         mySlide.toggle();
         e.stop();
         var img = document.getElementById('toggle'+context_id);
         if(img.src.toLowerCase().indexOf('less') >= 0){
            img.src = img.src.replace('less','more');
         }else {
            img.src = img.src.replace('more','less');
         }
      });
      $('toggle'+context_id).addEvent('mouseover', function(e){
         var img = document.getElementById('toggle'+context_id);
         img.src = img.src.replace('.gif','_over.gif');
	  });
      $('toggle'+context_id).addEvent('mouseout', function(e){
         var img = document.getElementById('toggle'+context_id);
         img.src = img.src.replace('_over.gif','.gif');
	  });
   }



// $Id$
//
// Release $Name$
//
// Copyright (c)2002-2007 Dirk Blössl, Matthias Finck, Dirk Fust, Franz Grünig,
// Oliver Hankel, Iver Jackewitz, Michael Janneck, Martti Jeenicke,
// Detlev Krause, Irina L. Marinescu, Frithjof Meyer, Timo Nolte, Bernd Pape,
// Edouard Simon, Monique Strauss, José Manuel González Vázquez
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

   var layer_id;
   function mouseoverTopbar(){
      var img = this;
      var src = img.src;
      img.src = img.src.replace('.gif','_over.gif');
   }

   function mouseoutTopbar(){
      var img = this;
      var src = img.src;
      img.src = img.src.replace('_over.gif','.gif');
   }

   function buildRoomLayer(e,inputObj){
      var div = document.getElementById(layer_id);
      var height = document.body.offsetHeight - 140;
      div.style.height = height + "px";
   }

   function initDeleteLayer(){
      layer_id = "delete";
      buildRoomLayer();
   }

   function handleWidth(id,max_width,link_name){
      window.addEvent('domready', function(){
      var div = document.getElementById(id);
      var inner_div = document.getElementById('inner_'+id);
      var width = inner_div.scrollWidth;
      if (width > max_width){
         var link = document.createElement('DIV');
         link.innerHTML = link_name.replace('COMMSYDHTMLTAG','</');
         div.appendChild(link);
         inner_div.className = 'handle_width_border';
      }
      });
   }



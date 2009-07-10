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

function initTextFormatingInformation(id,show){
   var item_id = id;
      var is_shown = show;
         var mySlide = new Fx.Slide('creator_information'+item_id,{duration: 200});
         if (is_shown == false){
            mySlide.hide();
         }else{
            var img = document.getElementById('toggle'+item_id);
            img.src = img.src.replace('more','less');
         }
         $('toggle'+item_id).addEvent('click', function(e){
            e = new Event(e);
	        mySlide.toggle();
   	        e.stop();
            var img = document.getElementById('toggle'+item_id);
            if(img.src.toLowerCase().indexOf('less') >= 0){
               img.src = img.src.replace('less','more');
            }else {
               img.src = img.src.replace('more','less');
            }
	   });
       $('toggle'+item_id).addEvent('mouseover', function(e){
            var img = document.getElementById('toggle'+item_id);
            img.src = img.src.replace('.gif','_over.gif');
	   });
       $('toggle'+item_id).addEvent('mouseout', function(e){
            var img = document.getElementById('toggle'+item_id);
            img.src = img.src.replace('_over.gif','.gif');
	   });

}

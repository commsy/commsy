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
$(document).ready(function(){ 

  jQuery("#banner_rotator").cycle({
                    fx:"fade",
                    pager:"#ir_nav",
                    pagerAnchorBuilder: function(idx, slide) {
                      return '<a id="banner_rotator_image_'+idx+'" class="ir_standard" href="#"><strong>'+idx+'</strong></a>';
                    },
                    updateActivePagerLink: function(pager, currSlideIndex){
                       jQuery(pager).find("a").each(function(){
                                  jQuery(this).attr("class","ir_standard");
                       });
              jQuery("#banner_rotator_image_"+currSlideIndex).attr("class","ir_active");
               },
                    timeout:10000,
                    fit:true,
                    width:"980px"
                 });
});
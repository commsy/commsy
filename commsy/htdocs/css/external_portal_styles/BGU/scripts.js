jQuery(document).ready(function(){
    
    
    jQuery("#language").hover(function(){
      jQuery(".tx-srlanguagemenu-pi1").fadeToggle("fast", "swing");
    });
	
    jQuery("#technical-department-header").click(function(){
        var breite = jQuery("#technical-department").css("width");
        if (breite == "898px") {
			jQuery(this).removeClass("active");
			jQuery(".info-teaser-outer-wrapper").delay(700).fadeIn(500);
            jQuery("#technical-department").animate({
                width: "278px"
            }, 800);
        }
        else {
			jQuery(this).addClass("active");
			jQuery(".info-teaser-outer-wrapper").fadeOut(100);
            jQuery("#technical-department").animate({
                width: "898px"
            }, 800);
        }
    });
	
	jQuery("div.info-teaser-row:odd").css({
        "margin-right": "0"
    });
	
	jQuery('div.career-teaser').each(function(){
       jQuery(this).find('div.career-teaser-box:odd').css({
            "margin": "0px"
        });
    });
    
});

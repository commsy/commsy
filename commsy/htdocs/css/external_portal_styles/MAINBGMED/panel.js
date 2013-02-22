$(document).ready(function(){ 
  $(".slide").siblings(".panel").hide();
 
  $(".slide").click(function(){
        $(this).siblings(".panel").slideToggle("fast");
        $(this).toggleClass("active");
        return false;
    });

  $(".slide2").siblings(".panel").hide();

  $(".slide2").click(function(){
        $(this).siblings(".panel").slideToggle("fast");
        $(this).toggleClass("active2");
        return false;
    });

       
    
});
    


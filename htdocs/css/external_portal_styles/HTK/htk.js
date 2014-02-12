$(document).ready(function(){
   
  $(".slide").siblings(".panel").hide();
    
  $(".slide").click(function(){
        $(this).siblings(".panel").slideToggle("fast");
        $(this).toggleClass("active");
        return false;
    });
    
  
  $(".slide_text").siblings(".panel").show();
    
  $(".slide_text").click(function(){
        $(this).siblings(".panel").slideToggle("fast");
        $(this).toggleClass("active");
        return false;
    });

});
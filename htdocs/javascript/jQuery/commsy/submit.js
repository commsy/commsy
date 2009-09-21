jQuery(document).ready(function() {
   jQuery("select").change(function () {
      if(this.id == 'submit_form'){
         jQuery(this).parents("form").map(function () {
            this.submit();
         });
      }
   });
   
   jQuery("input").change(function () {
      if((this.type == 'checkbox') && (this.id == 'submit_form')){
         jQuery(this).parents("form").map(function () {
            this.submit();
         });
      }
   });
   
   jQuery("a").click(function () {
      if(this.id == 'submit_form'){
         jQuery(this).parents("form").map(function () {
            this.submit();
         });
      }
   });
});
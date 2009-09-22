jQuery(document).ready(function() {
   //jQuery("select").change(function () {
   //   if(this.id == 'submit_form'){
   //      jQuery(this).parents("form").map(function () {
   //         this.submit();
   //      });
   //   }
   //});
   jQuery("select[id='submit_form']").each(function(i) {
      jQuery(this).change(function () {
         jQuery(this).parents("form").map(function () {
            this.submit();
         });
      })
   });
   
   //jQuery("input").change(function () {
   //   if((this.type == 'checkbox') && (this.id == 'submit_form')){
   //      jQuery(this).parents("form").map(function () {
   //         this.submit();
   //      });
   //   }
   //});
   jQuery("input[id='submit_form'][type='checkbox']").each(function(i) {
      jQuery(this).change(function () {
         jQuery(this).parents("form").map(function () {
            this.submit();
         });
      })
   });
   
   //jQuery("a").click(function () {
   //   if(this.id == 'submit_form'){
   //      jQuery(this).parents("form").map(function () {
   //         this.submit();
   //      });
   //   }
   //});
   jQuery("a[id='submit_form']").each(function(i) {
      jQuery(this).click(function () {
         jQuery(this).parents("form").map(function () {
            this.submit();
         });
      })
   });
   
});
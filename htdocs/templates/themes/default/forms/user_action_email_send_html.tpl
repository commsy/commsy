<form name="edit" enctype="multipart/form-data" method="post" action="commsy.php?cid={$environment['cid']}&amp;mod=user&amp;fct=action" style="font-size:10pt; margin:0px; padding:0px;" id="edit">
   <input type="hidden" value="d070d4f5f58d5d77eda2fcf91d655f81" name="security_token">

   <input type="hidden" value="" name="right_box_option" style="font-size:8pt;" id="right_box_option">

   <!-- BEGIN OF FORM-VIEW -->

{$room['rubric']}

   <table summary="layout" id="form">
      <tbody>
         <tr>
            <td colspan="4" style="border:0px; padding:0px;">
               <input type="hidden" value="1" name="with_mail">
            </td>
         </tr>
         <tr>
            <!-- BEGIN OF FORM-ELEMENT: name (text) -->
            <td style="width:10%; vertical-align:baseline;" class="key">___COMMON_NAME___:</td>
            <td class="formfield">
            	<div style="font-size:10pt; text-align:left;">
            	{foreach $user.data as $tempuser}
            		{$tempuser.name} ({$tempuser.email})<br/>
            	{/foreach}
            	</div>
         	</td>
            <!-- END OF FORM-ELEMENT: name (text) -->
         </tr>
         <tr>
            <!-- BEGIN OF FORM-ELEMENT: copy (checkbox) -->
            <td style="width:10%; vertical-align:baseline;" class="key"></td>
            <td class="formfield"><div style="font-size:10pt; text-align:left;">
				<input type="checkbox" tabindex="8" value="copy" name="copy">&nbsp;<span style="font-size:10pt;">___MAILCOPY_TO_SENDER___</span></div>
			</td>
            <!-- END OF FORM-ELEMENT: copy (checkbox) -->
         </tr>
         <tr>
            <!-- BEGIN OF FORM-ELEMENT: subject (textfield) -->
            <td style="width:10%; vertical-align:baseline;" class="key">___MAIL_SUBJECT___</td>
            <td class="formfield"><div style="font-size:10pt; text-align:left;">
				<input type="text" class="text" tabindex="9" size="50" maxlength="200" value="" style="font-size:10pt;" name="subject"></div>
			</td>
            <!-- END OF FORM-ELEMENT: subject (textfield) -->
         </tr>
         <tr class="textarea">
            <!-- BEGIN OF FORM-ELEMENT: content (textarea) -->
            <td style="width: 100%; " colspan="2" class="key">___COMMON_BODY___:<span class="required">*</span>
               <div style="font-size:10pt; text-align:left;">
                  <textarea tabindex="10" rows="20" cols="90" name="content">{$email.footer}</textarea>
               </div>
            </td>
            <!-- END OF FORM-ELEMENT: content (textarea) -->
         </tr>
      </tbody>
   </table>

   <table style="width: 100%; border-collapse:collapse;">
      <tbody>
         <tr>
            <td class="buttonbar" colspan="2">
               <span style="font-size:16pt;" class="required">*</span>
               <span style="font-weight:normal;" class="key">___COMMON_MANDATORY_FIELDS___</span>
               <input type="submit" style="font-size:10pt;" tabindex="106" value="___MAIL_SEND_BUTTON___" name="option">
               <input type="submit" style="font-size:10pt;" tabindex="107" value="___COMMON_CANCEL_BUTTON___" name="option">
            </td>
            <td style="padding-top:2px; border-bottom: none; text-align: right;" class="buttonbar"></td>
         </tr>
      </tbody>
   </table>
</form>
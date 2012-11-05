{* include template functions *}
{include file="include/functions.tpl" inline}

<div id="popup_top_wrapper">
  <div id="popup_my_area">
    <div id="popup_frame_my_area">
      <div id="popup_inner_my_area">

        <div id="popup_pagetitle">
          <a id="popup_close" href="" title="___COMMON_CLOSE___"><img src="{$basic.tpl_path}img/popup_close.gif" alt="___COMMON_CLOSE___" /></a>
          <h2>
            ___COMMON_PROFIL_EDIT___
          </h2>
          <div class="clear"> </div>
        </div>
        <div id="popup_content_wrapper">
          <div id="profile_content_row_three">
            <div class="tab_navigation">
        <!--      <a href="account" class="pop_tab_active">___PROFILE_ACCOUNT_DATA___</a>-->
              <a href="user" class="pop_tab">Personendaten in CommSy</a>
              <a href="newsletter" class="pop_tab">___PROFILE_NEWSLETTER_DATA___</a>
        <!--      <a href="cs_bar" class="pop_tab">___PROFILE_COMMSY_BAR_DATA___</a> -->

              <div class="clear"> </div>
            </div>

            <div id="popup_tabcontent">

<!--
              <div class="tab" id="account">
                <div id="content_row_three">
                  <fieldset>
                    <legend>___MYAREA_MY_PROFILE___:</legend>

                    <div class="input_row">
                      <label for="forname">___USER_FIRSTNAME___:</label>
                      <input id="forname" type="text" class="size_200 mandatory" name="form_data[forname]" value="{show var=$popup.form.account.firstname}"/>
                    </div>

                    <div class="input_row">
                      <label for="surname">___USER_LASTNAME___:</label>
                      <input id="surname" type="text" class="size_200 mandatory" name="form_data[surname]" value="{show var=$popup.form.account.lastname}"/>
                    </div>

                    <div class="input_row">
                      {if $popup.form.config.show_account_change_form === true}
                        <label for="user_id">___USER_USER_ID___:</label>
                        <input id="user_id" type="text" class="size_200 mandatory" name="form_data[user_id]" value="{show var=$popup.form.account.user_id}"/>
                      {else}
                        {show var=$popup.form.account.user_id}
                      {/if}
                    </div>

                    {if $popup.form.config.show_password_change_form === true}
                      <div class="input_row">
                        <label for="old_password">___USER_PASSWORD_OLD___:</label>
                        <input id="old_password" type="text" class="size_200" name="form_data[old_password]" />
                      </div>

                      <div class="input_row">
                        <label for="new_password">___USER_PASSWORD_NEW___:</label>
                        <input id="new_password" type="text" class="size_200" name="form_data[new_password]" />
                      </div>

                      <div class="input_row">
                        <label for="new_password_confirm">___USER_PASSWORD_NEW2___:</label>
                        <input id="new_password_confirm" type="text" class="size_200" name="form_data[new_password_confirm]" />
                      </div>
                    {/if}

                    <div class="input_row">
                      <label for="language">___USER_LANGUAGE___:</label>
                      <select id="language" name="form_data[language]">
                        {foreach $popup.form.languages as $language}
                          <option value="{$language.value}"{if $language.value == $popup.form.account.language} selected="selected"{/if}>{$language.text}</option>
                        {/foreach}
                      </select>
                    </div>

                    {if $popup.form.config.show_mail_change_form === true}
                      <div class="input_row">
                        <label for="mail_account">___USER_EMAIL___:</label>

                        <div class="input_container_180">
                          <input id="mail_account" name="form_data[mail_account]" type="checkbox"{if $popup.form.account.email_account == true} checked="checked"{/if}/> ___USER_MAIL_GET_ACCOUNT___<br/>
                          <input id="mail_room" name="form_data[mail_room]" type="checkbox"{if $popup.form.account.email_room == true} checked="checked"{/if}/> ___USER_MAIL_OPEN_ROOM_PO___
                        </div>
                      </div>
                    {/if}


                    <div class="input_row">
                      <label for="upload">___CONFIGURATION_NEW_UPLOAD___:</label>

                      <div class="input_container_180">
                        <input id="upload" type="radio" name="form_data[upload]"{if $popup.form.account.new_upload == true} checked="checked"{/if}/> ___CONFIGURATION_NEW_UPLOAD_YES___<br/>
                        <input type="radio" name="form_data[upload]"{if $popup.form.account.new_upload != true} checked="checked"{/if}/> ___CONFIGURATION_NEW_UPLOAD_NO___
                      </div>
                    </div>

                    <div class="input_row">
                      <label for="auto_save">___CONFIGURATION_AUTO_SAVE___:</label>

                      <div class="input_container_180">
                        <input id="auto_save" value="on" type="radio" name="form_data[auto_save]"{if $popup.form.account.auto_save == true} checked="checked"{/if}/> ___CONFIGURATION_AUTO_SAVE_YES___<br/>
                        <input type="radio" value="off" name="form_data[auto_save]"{if $popup.form.account.auto_save != true} checked="checked"{/if}/> ___CONFIGURATION_AUTO_SAVE_NO___
                      </div>
                    </div>
                    {if $popup.form.account.email_to_commsy_on}
                      <div class="input_row">
                        <label for="email_to_commsy">___PRIVATE_ROOM_EMAIL_TO_COMMSY___:</label>
                        <div class="input_container_180">
                          <input id="email_to_commsy" name="form_data[email_to_commsy]" type="checkbox"{if $popup.form.account.email_to_commsy == true} checked="checked"{/if}/> ___PRIVATE_ROOM_EMAIL_TO_COMMSY_CHECKBOX_NEW___ {$popup.form.account.email_to_commsy_mailadress}<br/>
                          <input id="email_to_commsy_secret" type="text" class="size_200" name="form_data[email_to_commsy_secret]" value="{show var=$popup.form.account.email_to_commsy_secret}"/> ___PRIVATE_ROOM_EMAIL_TO_COMMSY_NO_SECRET___<br/>
                          ___PRIVATE_ROOM_EMAIL_TO_COMMSY_TEXT___
                        </div>
                      </div>
                    {/if}

                    <div class="input_row" style="margin-bottom:40px;">
                      <input id="submit" class="submit popup_button" data-custom="part: 'account'" type="button" name="save" value="___PREFERENCES_SAVE_BUTTON___"/>
                      <input id="delete" class="popup_button" data-custom="part: 'account_delete'" type="button" name="form_data[delete]" value="___PREFERENCES_DELETE_BUTTON___"/>
                    </div>

                    <div id="delete_options" class="hidden">
                      {if $popup.form.config.datenschutz_overwrite === true}
                         {i18n tag="PREFERENCES_REALLY_DELETE_DESC_ROOM" param1="{$popup.context.context_name}" param2="___PREFERENCES_LOCK_BUTTON_ROOM___" param3="___PREFERENCES_REALLY_DELETE_BUTTON_ROOM___"}
                      {/if}
                      {if $popup.form.config.datenschutz_overwrite === false}
                         {i18n tag="PREFERENCES_REALLY_DELETE_DESC_ROOM_NOT_OVERWRITE" param1="{$popup.context.context_name}" param2="___PREFERENCES_LOCK_BUTTON_ROOM___" param3="___PREFERENCES_REALLY_DELETE_BUTTON_ROOM___"}
                      {/if}
                      <input id="lock_room" type="button" name="form_data[lock_room]" value="___PREFERENCES_LOCK_BUTTON_ROOM___"/>
                      <input id="delete_room" type="button" name="form_data[delete_room]" value="___PREFERENCES_REALLY_DELETE_BUTTON_ROOM___"/>
                      <br/><br/>

                      {assign var="lockButton" value="{i18n tag="PREFERENCES_LOCK_BUTTON" param1="{$popup.portal.portal_name}"}"}
                      {assign var="deleteButton" value="{i18n tag="PREFERENCES_REALLY_DELETE_BUTTON" param1="{$popup.portal.portal_name}"}"}
                      {i18n tag="PREFERENCES_REALLY_DELETE_DESC" param1="{$popup.portal.portal_name}" param2="{$lockButton}" param3="{$deleteButton}"}
                      <input id="lock_portal" type="button" name="form_data[lock_portal]" value="{$lockButton}"/>
                      <input id="delete_portal" type="button" name="form_data[delete_portal]" value="{$deleteButton}"/>
                    </div>
                  </fieldset>

                  {if $popup.form.config.show_merge_form === true}
                    <fieldset>
                      <legend>___ACCOUNT_MERGE___:</legend>

                      <div class="input_row">
                        <div>
                          {i18n tag="ACCOUNT_MERGE_TEXT" param1=$popup.portal.portal_name}
                        </div>
                      </div>

                      {if sizeof($popup.form.data.auth_source_array) > 1 && $popup.form.config.show_auth_source === true}
                        <div class="input_row">
                          <label for="auth_source">___USER_AUTH_SOURCE___:</label>
                          <select id="auth_source" name="form_data[auth_source]">
                            {foreach $popup.form.data.auth_source_array as $auth_source}
                              <option value="{$auth_source.value}"{if $auth_source.value == $popup.form.data.default_auth_source} selected="selected"{/if}>{$auth_source.text}</option>
                            {/foreach}
                          </select>
                        </div>
                      {/if}

                      <div class="input_row">
                        <label for="merge_user_id">___USER_USER_ID___:</label>
                        <input id="merge_user_id" type="text" class="size_200 mandatory" name="form_data[merge_user_id]" />
                      </div>

                      <div class="input_row">
                        <label for="merge_user_password">___USER_PASSWORD___:</label>
                        <input id="merge_user_password" type="text" class="size_200 mandatory" name="form_data[merge_user_password]" />
                      </div>

                      <div class="input_row">
                        <div class="input_container_180">
                          ___COMMON_DONT_STOP___
                        </div>
                      </div>

                      <div class="input_row">
                        <div class="input_container_180">
                          <input id="merge" class="submit popup_button" data-custom="part: 'account_merge'" type="submit" name="form_data[merge]" value="___ACCOUNT_MERGE_BUTTON___"/>
                        </div>
                      </div>
                    </fieldset>
                  {/if}
                </div>
              </div>
-->
              <div class="tab" id="user">
                <div id="content_row_three">
                  <fieldset>
                    <legend>Allgemein</legend>

                    <div class="input_row">
                      <label for="data_title">___USER_TITLE___:</label>
                      <input id="data_title" type="text" class="size_200 float-left" name="form_data[title]" value="{show var=$popup.form.user.title}" />
                      <input id="data_title_all" type="checkbox" class="float-left" name="form_data[title_all]" />
                      <label for="data_title_all" class="float-left">___USER_CHANGE_IN_ALL_ROOMS___</label>
                      <div class="clear"></div>
                    </div>

                    <div class="input_row">
                      <label for="data_birthday">___USER_BIRTHDAY___:</label>
                      <input id="data_birthday" type="text" class="size_200 float-left datepicker" name="form_data[birthday]" value="{show var=$popup.form.user.birthday}" />
                      <input id="data_birthday_all" type="checkbox" class="float-left" name="form_data[birthday_all]" />
                      <label for="data_birthday_all" class="float-left">___USER_CHANGE_IN_ALL_ROOMS___</label>
                      <div class="clear"></div>
                    </div>

                    <div class="input_row">
                      <label for="data_picture">___USER_PICTURE_UPLOADFILE___:</label>

                      <div class="uploader-single">
                        <form method="post" action="UploadFile.php" id="myForm" enctype="multipart/form-data" >
                           <input id="data_picture" class="fileSelector"></input>
                           <div class="filePreview"></div>
                           <div class="fileList"></div>
                        </form>
                      </div>
                      <div class="clear"></div>
                    </div>

                    {if !empty($popup.form.user.picture)}
                      <div class="input_row">
                        <div class="input_container_180">
                          <img src="commsy.php?cid={$environment.cid}&mod=picture&fct=getfile&picture={$popup.form.user.picture}" alt="___USER_PICTURE_UPLOADFILE___" />
                        </div>
                      </div>

                      <div class="input_row">
                        <div class="input_container_180">
                          <input id="delete_picture" class="float-left" type="checkbox" name="form_data[delete_picture]" value="1"/>
                          <label for="delete_picture" class="float-left">___USER_DEL_PIC_BUTTON___</label>
                          <div class="clear"></div>
                        </div>
                      </div>
                    {/if}

                    <div class="input_row">
                      <div class="input_container_180">
                        <input id="data_picture_all" type="checkbox" class="float-left" name="form_data[picture_all]" />
                        <label for="data_picture_all" class="float-left">___USER_CHANGE_IN_ALL_ROOMS___</label>
                        <div class="clear"></div>
                      </div>
                    </div>
                  </fieldset>

                  <fieldset>
                    <legend>Kontakt</legend>

                    <div class="input_row">
                      <label for="data_mail">___USER_EMAIL___</label>
                      <input id="data_mail" type="text" class="mandatory size_200 float-left" name="form_data[mail]" value="{show var=$popup.form.user.mail}" />
                      <input id="data_mail_all" type="checkbox" class="float-left" name="form_data[mail_all]" />
                      <label for="data_mail_all" class="float-left">___USER_CHANGE_IN_ALL_ROOMS___</label>
                      <div class="clear"></div>
                    </div>

                    <div class="input_row">
                      <label for="data_telephone">___USER_TELEPHONE___:</label>
                      <input id="data_telephone" type="text" class="size_200 float-left" name="form_data[telephone]" value="{show var=$popup.form.user.telephone}" />
                      <input id="data_telephone_all" type="checkbox" class="float-left" name="form_data[telephone_all]" />
                      <label for="data_telephone_all" class="float-left">___USER_CHANGE_IN_ALL_ROOMS___</label>
                      <div class="clear"></div>
                    </div>

                    <div class="input_row">
                      <label for="data_cellularphone">___USER_CELLULARPHONE___:</label>
                      <input id="data_cellularphone" type="text" class="size_200 float-left" name="form_data[cellularphone]" value="{show var=$popup.form.user.cellularphone}" />
                      <input id="data_cellularphone_all" type="checkbox" class="float-left" name="form_data[cellularphone_all]" />
                      <label for="data_cellularphone_all" class="float-left">___USER_CHANGE_IN_ALL_ROOMS___</label>
                      <div class="clear"></div>
                    </div>
                  </fieldset>

                  <fieldset>
                    <legend>Adresse</legend>

                    <div class="input_row">
                      <label for="data_street">___USER_STREET___:</label>
                      <input id="data_street" type="text" class="size_200 float-left" name="form_data[street]" value="{show var=$popup.form.user.street}" />
                      <input id="data_street_all" type="checkbox" class="float-left" name="form_data[street_all]" />
                      <label for="data_street_all" class="float-left">___USER_CHANGE_IN_ALL_ROOMS___</label>
                      <div class="clear"></div>
                    </div>

                    <div class="input_row">
                      <label for="data_zipcode">___USER_ZIPCODE___:</label>
                      <input id="data_zipcode" type="text" class="size_200 float-left" name="form_data[zipcode]" value="{show var=$popup.form.user.zipcode}" />
                      <input id="data_zipcode_all" type="checkbox" class="float-left" name="form_data[zipcode_all]" />
                      <label for="data_zipcode_all" class="float-left">___USER_CHANGE_IN_ALL_ROOMS___</label>
                      <div class="clear"></div>
                    </div>

                    <div class="input_row">
                      <label for="data_city">___USER_CITY___:</label>
                      <input id="data_city" type="text" class="size_200 float-left" name="form_data[city]" value="{show var=$popup.form.user.city}" />
                      <input id="data_city_all" type="checkbox" class="float-left" name="form_data[city_all]" />
                      <label for="data_city_all" class="float-left">___USER_CHANGE_IN_ALL_ROOMS___</label>
                      <div class="clear"></div>
                    </div>

                    <div class="input_row">
                      <label for="data_room">___USER_ROOM___:</label>
                      <input id="data_room" type="text" class="size_200 float-left" name="form_data[room]" value="{show var=$popup.form.user.room}" />
                      <input id="data_room_all" type="checkbox" class="float-left" name="form_data[room_all]" />
                      <label for="data_room_all" class="float-left">___USER_CHANGE_IN_ALL_ROOMS___</label>
                      <div class="clear"></div>
                    </div>
                  </fieldset>

                  <fieldset>
                    <legend>Organisation</legend>

                    <div class="input_row">
                      <label for="data_organisation">___USER_ORGANISATION___:</label>
                      <input id="data_organisation" type="text" class="size_200 float-left" name="form_data[organisation]" value="{show var=$popup.form.user.organisation}" />
                      <input id="data_organisation_all" type="checkbox" class="float-left" name="form_data['organisation_all]"/>
                      <label for="data_organisation_all" class="float-left">___USER_CHANGE_IN_ALL_ROOMS___</label>
                      <div class="clear"></div>
                    </div>

                    <div class="input_row">
                      <label for="data_position">___USER_POSITION___:</label>
                      <input id="data_position" type="text" class="size_200 float-left" name="form_data[position]" value="{show var=$popup.form.user.position}" />
                      <input id="data_position_all" type="checkbox" class="float-left" name="form_data[position_all]" />
                      <label for="data_position_all" class="float-left">___USER_CHANGE_IN_ALL_ROOMS___</label>
                      <div class="clear"></div>
                    </div>
                  </fieldset>

                  <fieldset>
                    <legend>Messenger</legend>

                    <div class="input_row">
                      <div>
                        ___USER_MESSENGER_NUMBERS_TEXT___
                      </div>
                    </div>

                    <div class="input_row">
                      <label for="data_icq">___USER_ICQ___:</label>
                      <input id="data_icq" type="text" class="size_200 float-left" name="form_data[icq]" value="{show var=$popup.form.user.icq}" />
                      <div class="clear"></div>
                    </div>

                    <div class="input_row">
                      <label for="data_msn">___USER_MSN___:</label>
                      <input id="data_msn" type="text" class="size_200 float-left" name="form_data[msn]" value="{show var=$popup.form.user.msn}" />
                      <div class="clear"></div>
                    </div>

                    <div class="input_row">
                      <label for="data_skype">___USER_SKYPE___:</label>
                      <input id="data_skype" type="text" class="size_200 float-left" name="form_data[sykpe]" value="{show var=$popup.form.user.skype}" />
                      <div class="clear"></div>
                    </div>

                    <div class="input_row">
                      <label for="data_yahoo">___USER_YAHOO___:</label>
                      <input id="data_yahoo" type="text" class="size_200 float-left" name="form_data[yahoo]" value="{show var=$popup.form.user.yahoo}" />
                      <div class="clear"></div>
                    </div>

                    <div class="input_row">
                      <label for="data_jabber">___USER_JABBER___:</label>
                      <input id="data_jabber" type="text" class="size_200 float-left" name="form_data[jabber]" value="{show var=$popup.form.user.jabber}" />
                      <div class="clear"></div>
                    </div>

                    <div class="input_row">
                      <div class="input_container_180">
                        <input id="data_messenger_all" type="checkbox" class="float-left" name="form_data[messenger_all]" />
                        <label for="data_messenger_all" class="float-left">___USER_CHANGE_IN_ALL_ROOMS___</label>
                        <div class="clear"></div>
                      </div>
                    </div>
                  </fieldset>

                  <fieldset>
                    <legend>Sonstiges</legend>

                    <div class="input_row">
                      <label for="data_homepage">___USER_HOMEPAGE___:</label>
                      <input id="data_homepage" type="text" class="size_200 float-left" name="form_data[homepage]" value="{show var=$popup.form.user.homepage}" />
                      <input id="data_homepage_all" type="checkbox" class="float-left" name="form_data[homepage_all]" />
                      <label for="data_homepage_all" class="float-left">___USER_CHANGE_IN_ALL_ROOMS___</label>
                      <div class="clear"></div>
                    </div>

                    <div class="input_row">
                      <label for="description">___USER_DESCRIPTION___:</label>
                      <div class="clear"></div>
                    </div>

                    <div class="input_row">
                      <div class="editor_content">
                        <div id="description" class="ckeditor">{if isset($popup.form.user.description)}{$popup.form.user.description}{/if}</div>
                      </div>
                    </div>

                    <div class="input_row">
                      <input id="data_position_all" type="checkbox" class="float-left" name="form_data[description_all]" />
                      <label for="data_position_all" class="float-left">___USER_CHANGE_IN_ALL_ROOMS___</label>
                      <div class="clear"></div>
                    </div>
                  </fieldset>

                  <div class="input_row">
                      <input id="submit" class="submit popup_button" data-custom="part: 'user'" type="button" name="save" value="___PREFERENCES_SAVE_BUTTON___"/>
                  </div>
                </div>
              </div>

              <div class="tab hidden" id="newsletter">
                <div id="content_row_three">
                  <div class="input_row">
                    <label for="newsletter">___USER_STATUS___:</label>

                    <div class="input_container_180">
                      <input id="newsletter" type="radio" value="1" name="form_data[newsletter]"{if $popup.form.newsletter.newsletter == '1'} checked="checked"{/if} /> ___CONFIGURATION_NEWSLETTER_NONE___<br/>
                      <input type="radio" value="2" name="form_data[newsletter]"{if $popup.form.newsletter.newsletter == '2'} checked="checked"{/if} /> ___CONFIGURATION_NEWSLETTER_WEEKLY___<br/>
                      <input type="radio" value="3" name="form_data[newsletter]"{if $popup.form.newsletter.newsletter == '3'} checked="checked"{/if} /> ___CONFIGURATION_NEWSLETTER_DAILY___
                    </div>
                  </div>

                  <div class="input_row">
                    ___CONFIGURATION_NEWSLETTER_NOTE___
                  </div>

                  <div class="input_row">
                      <input id="submit" class="submit popup_button" data-custom="part: 'newsletter'" type="button" name="save" value="___PREFERENCES_SAVE_BUTTON___"/>
                  </div>
                </div>
              </div>


<!--
              <div class="tab hidden" id="cs_bar">
                <div id="content_row_three">

                  <fieldset>
                  	<p><strong>___CS_BAR_COMMING_SOON_INFO_TEXT___</strong></p>
                  </fieldset>
                  <fieldset>
                    <p>
                      <strong>___CS_BAR_WIDGETS___</strong>: ___CS_BAR_WIDGETS_DESC___
                    </p>
                    <div class="input_row_180">
                      <label for="widget_view">___CS_BAR_WIDGETS_VIEW___:</label>
                      <input id="show_widget_view" type="checkbox" name="form_data[show_widget_view]" value="yes"{if $popup.form.cs_bar.show_widget_view == 'yes'} checked="checked"{/if} />___COMMON_SHOW_ON_CS_BAR___
                      <div class="clear"></div>
                    </div>
                    {*
                    <div class="input_row_180">
                      <label for="widget_view">___PRIVATROOM_ROOMWIDE_SEARCH_BOX___:</label>
                      <input disabled="disabled"  id="show_roomwide_search" type="checkbox" name="form_data[show_roomwide_search]" value="yes"{if $popup.form.cs_bar.show_roomwide_search == 'yes'} checked="checked"{/if} />___COMMON_SHOW___
                      <div class="clear"></div>
                    </div>
                    <div class="input_row_180">
                      <label for="widget_view">___COMMON_NEWEST_ENTRIES___:</label>
                      <input disabled="disabled"  id="show_newest_entries" type="checkbox" name="form_data[show_newest_entries]" value="yes"{if $popup.form.cs_bar.show_newest_entries == 'yes'} checked="checked"{/if} />___COMMON_SHOW___
                      <div class="clear"></div>
                    </div>
                    <div class="input_row_180">
                      <label for="widget_view">___COMMON_ACTIV_ROOMS___:</label>
                      <input disabled="disabled" id="show_active_rooms" type="checkbox" name="form_data[show_active_rooms]" value="yes"{if $popup.form.cs_bar.show_active_rooms == 'yes'} checked="checked"{/if} />___COMMON_SHOW___
                      <div class="clear"></div>
                    </div>
                    <div class="input_row_180">
                      <label for="widget_view">___COMMON_RSS_TICKER___:</label>
                      <input disabled="disabled" id="rss_ticker" type="checkbox" name="form_data[rss_ticker]" value="yes"{if $popup.form.cs_bar.rss_ticker == 'yes'} checked="checked"{/if} />___COMMON_SHOW___
                      <div class="clear"></div>
                    </div>
                    <div class="input_row_180">
                      <label for="widget_view">___HOME_EXTRA_TOOLS___:</label>
                      <input disabled="disabled" id="show_extensions" type="checkbox" name="form_data[show_newest_entries]" value="yes"{if $popup.form.cs_bar.show_newest_entries == 'yes'} checked="checked"{/if} />___COMMON_SHOW___
                      ___CS_BAR_COMMING_SOON_2___
                      <div class="clear"></div>
                    </div>
                    *}
                  </fieldset>

                  <fieldset>
                    <p>
                      <strong>___CS_BAR_CALENDAR___</strong>: ___CS_BAR_CALENDAR_DESC___
                    </p>
                    <div class="input_row_180">
                      <label for="widget_calendar_view">___CS_BAR_CALENDAR_VIEW___:</label>
                      <input disabled="disabled" id="show_calendar_view" type="checkbox" name="form_data[show_calendar_view]" value="yes"{if $popup.form.cs_bar.show_calendar_view == 'yes'} checked="checked"{/if} />___COMMON_SHOW_ON_CS_BAR___
                      <div class="clear"></div>
                    </div>
                    <div class="input_row_180">
                      <label for="widget_view">___CS_BAR_COMMON_SHOW___:</label>
                      <input disabled="disabled" id="show_dates" type="checkbox" name="form_data[show_dates]" value="yes"{if $popup.form.cs_bar.show_dates == 'yes'} checked="checked"{/if} />___DATE_INDEX___
                      <input disabled="disabled" id="show_todos" type="checkbox" name="form_data[show_todos]" value="yes"{if $popup.form.cs_bar.show_todos == 'yes'} checked="checked"{/if} />___TODO_INDEX___
                      <input disabled="disabled" id="show_restrictions" type="checkbox" name="form_data[show_restrictions]" value="yes"{if $popup.form.cs_bar.show_restrictions == 'yes'} checked="checked"{/if} />___COMMON_RESTRICTIONS_SHORT___
                      <div class="clear"></div>
                    </div>
                  </fieldset>

                  <fieldset>
                    <p>
                      <strong>___CS_BAR_STACK___</strong>: ___CS_BAR_STACK_DESC___
                    </p>
                    <div class="input_row_180">
                      <label for="widget_stack_view">___CS_BAR_STACK_VIEW___:</label>
                      <input id="show_stack_view" type="checkbox" name="form_data[show_stack_view]" value="yes"{if $popup.form.cs_bar.show_stack_view == 'yes'} checked="checked"{/if} />___COMMON_SHOW_ON_CS_BAR___
                      <div class="clear"></div>
                    </div>
                 </fieldset>

                  <fieldset>
                    <p>
                      <strong>___CS_BAR_PORTFOLIO___</strong>: ___CS_BAR_PORTFOLIO_DESC___
                    </p>
                    <div class="input_row_180">
                      <label for="widget_portfolio_view">___CS_BAR_PORTFOLIO_VIEW___:</label>
                      <input disabled="disabled" id="show_portfolio_view" type="checkbox" name="form_data[show_portfolio_view]" value="yes"{if $popup.form.cs_bar.show_portfolio_view == 'yes'} checked="checked"{/if} />___COMMON_SHOW_ON_CS_BAR___
                      <div class="clear"></div>
                    </div>
                 </fieldset>

                  <div class="input_row">
                      <input id="submit" class="submit popup_button" data-custom="part: 'cs_bar'" type="button" name="save" value="___PREFERENCES_SAVE_BUTTON___"/>
                  </div>
                </div>
              </div>

-->

            </div>
          </div>
        </div>
      </div>
      <div class="clear"></div>
    </div>
  </div>
</div>
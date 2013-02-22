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
              <a href="user" class="pop_tab">___PROFILE_USER_DATA___</a>
              <a href="newsletter" class="pop_tab">___PROFILE_NEWSLETTER_DATA___</a>

              <div class="clear"> </div>
            </div>

            <div id="popup_tabcontent">
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

            </div>
          </div>
        </div>
      </div>
      <div class="clear"></div>
    </div>
  </div>
</div>
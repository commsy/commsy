                                 <label for="new_portal">___CS_BAR_CONNECTION_EDIT_NEW_PORTAL_TITLE___</label>
                                 <select name="form_data[new_portal]" size="1">
                                    <option value="-1">*___CS_BAR_CONNECTION_EDIT_NEW_PORTAL_CHOOSE___</option>
                                    <option value="-2" disabled="disabled">------------</option>
                                    {foreach $popup.server as $key => $portalarray}
                                       {if !empty($portalarray)}
                                          <option value="-2" disabled="disabled"></option>
                                          <option value="-2" disabled="disabled">{$key}</option>
                                          {foreach $portalarray as $portalinfo}
                                             <option value="{$portalinfo.server_id}_{$portalinfo.id}">- {$portalinfo.title}</option>
                                          {/foreach}
                                       {/if}
                                    {/foreach}
                                 </select>
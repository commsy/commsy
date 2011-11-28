{extends file="room_html.tpl"}

{block name=room_site_actions}
	<a href="" title="Ansicht drucken"><img src="{$basic.tpl_path}img/btn_print.gif" alt="drucken" /></a>
    <a href="" title="neue Diskussion anlegen"><img src="{$basic.tpl_path}img/btn_add_new.gif" alt="neu" /></a>
{/block}

{block name=room_navigation_rubric_title}
	___COMMON_{$room.rubric|upper}_INDEX___
	<span>(Eintr&auml;ge 1 bis 20 von 225)</span>
{/block}

{block name=room_main_content}
	<div id="full_width_content">
		<div class="content_item"> <!-- Start content_item -->
			<div class="table_head">
				<h3 class="w_295"><a href="" class="sort_none"><strong>Titel</strong></a></h3>
				<h3 class="w_85"><a href="" class="sort_none">Beitr&auml;ge</a></h3>
				<h3 class="w_80"><a href="" id="sort_up">bearbeitet</a></h3> <!-- id="sort_down" ist ebenfalls vorhanden -->
				<h3 class="w_135"><a href="" class="sort_none">von</a></h3>
				<h3><a href="" class="sort_none">Bewertung</a></h3>
				
				<div class="clear"> </div>
			</div>
			
			{foreach $room.list_content.items as $item }
				<div class="{if $item@iteration is odd}row_odd{else}row_even{/if}"> <!-- Start Reihe -->
					<div class="column_20">
						<p>
							<input type="checkbox" name="" value="" />
						</p>
					</div>
					
					<div class="column_244">
						<p>
							 <a href="">Cum sociis natoque penatibus</a>
						</p>
					</div>
					
					<div class="column_45">
						<p>
							<a href="" class="attachment">12</a>
						</p>
					</div>
					
					<div class="seperator">
						<div class="column_90">
							<p>4 (4)</p>
						</div>
					</div>
					
					<div class="seperator">
						<div class="column_90">
							<p>00.00.0000</p>
						</div>
						
						<div class="column_155">
							<p>
								<a href="">Michael Muster</a>
							</p>
						</div>
					</div>
					
					<div class="seperator">
						<div class="column_100">
							<p>
								<img src="{$basic.tpl_path}img/star_active.gif" alt="*" />
								<img src="{$basic.tpl_path}img/star_active.gif" alt="*" />
								<img src="{$basic.tpl_path}img/star_non_active.gif" alt="*" />
								<img src="{$basic.tpl_path}img/star_non_active.gif" alt="*" />
								<img src="{$basic.tpl_path}img/star_non_active.gif" alt="*" />
							</p>
						</div>
					</div>
					
					<div class="clear"> </div>
				</div> <!-- Ende Reihe -->
			{/foreach}
		</div> <!-- Ende content_item --> 
	
	<div class="content_item"> <!-- Start content_item -->
		<div class="item_info">
			<div class="ii_left">
			 	<div id="item_action">
			 		<input type="checkbox" name="" value="" /> alle
			 		
			 		<select name="" size="1">
				 		<option>Aktion w&auml;hlen</option>
				 		<option>Aktion 1</option>
				 		<option>Aktion 2</option>
				 		<option>Aktion 3</option>
				 		<option>Aktion 4</option>
				 	</select>
				 	
				 	<input type="image" src="{$basic.tpl_path}img/btn_go.gif" alt="absenden" />
				 </div>
			</div>
			
			<div class="ii_right">
				<p>0 Eintr&auml;ge ausgew&auml;hlt</p>
			</div>
			
			<div class="clear"> </div>
		</div>
	</div> <!-- Ende content_item -->

	<div class="content_item"> <!-- Start content_item -->
		<div class="item_info">
			<div class="ii_left">
				<p>Eintr&auml;ge pro Seite <a href=""><strong>20</strong></a>|<a href="">50</a>|<a href="">alle</a></p>
			</div>
			
			<div class="ii_right">
				<div id="item_navigation">
					<a href=""><img src="{$basic.tpl_path}img/btn_ar_start.gif" alt="Start" /></a>
					<a href=""><img src="{$basic.tpl_path}img/btn_ar_left.gif" alt="zur&uuml;ck" /></a>
					Seite 1/12
					<a href=""><img src="{$basic.tpl_path}img/btn_ar_right.gif" alt="weiter" /></a>
					<a href=""><img src="{$basic.tpl_path}img/btn_ar_end.gif" alt="Ende" /></a>
				</div>
			</div>
			
			<div class="clear"> </div>
		</div>
		
		<div class="clear"> </div>
	</div> <!-- Ende content_item -->
</div>
{/block}
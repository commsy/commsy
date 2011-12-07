{extends file="room_detail_html.tpl"}

{block name=room_detail_content}
	<div class="item_actions">
		<div id="top_item_actions">
			<a href=""><span class="edit_set"> &nbsp; </span></a>
			<a href=""><span class="details_ia"> &nbsp; </span></a>
			<a href=""><span class="ref_to_ia"> &nbsp; </span></a>
		</div>
	</div>
	
	<div class="item_body"> <!-- Start item body -->
		<h2>{$detail.content.discussion.title}</h2>
		<div class="clear"> </div>
		
		<div id="item_credits">
			<p id="ic_rating">
				{foreach $detail.content.discussion.assessments as $assessment}
					<img src="{$basic.tpl_path}img/star_{$assessment}.gif" alt="*" />
				{/foreach}
			</p>
			<p>
				___COMMON_CREATED_BY_UPPER___ <a href="">{$detail.content.discussion.creator}</a> ___DATES_ON_DAY___  {$detail.content.discussion.creation_date}
			</p>
			<div class="clear"> </div>
		</div>
		
		<div id="item_legend"> <!-- Start item_legend -->
			<div class="row_odd odd_sep_390">
				<div class="column_320">
					<p>
						1. <a href="">Cum sociis natoque penatibus et magnis</a>
					</p>
				</div>
				<div class="column_45">
					<p>
						<a href="" class="attachment">12</a>
					</p>
				</div>
				<div class="column_155">
					<p>
						<a href="">Michael Muster</a>
					</p>
				</div>
				<div class="column_155">
					<p>00.00.0000 00:00h</p>
				</div>
				<div class="clear"> </div>
			</div>
			
			<div class="row_even even_sep_390">
				<div class="column_320">
					<p>
						2. <a href="">Lorem ipsum dolor sit amet consectetuer</a>
					</p>
				</div>
				<div class="column_45">&nbsp;</div>
				<div class="column_155">
					<p>
						<a href="">John Doe</a>
					</p>
				</div>
				<div class="column_155">
					<p>00.00.0000 00:00h</p>
				</div>
				<div class="clear"> </div>
			</div>
			
			<div class="row_odd odd_sep_390">
				<div class="column_320">
					<p>
						3. <img src="{$basic.tpl_path}img/flag_neu.gif" alt="NEU"/> <a href="">Cum sociis natoque penatibus et magnis</a>
					</p>
				</div>
				<div class="column_45">
					<p>
						<a href="" class="attachment">5</a>
					</p>
				</div>
				<div class="column_155">
					<p>
						<a href="">Dennis Muster</a>
					</p>
				</div>
				<div class="column_155">
					<p>
						00.00.0000 00:00h
					</p>
				</div>
				<div class="clear"> </div>
			</div>
			
			<div class="row_even even_sep_390">
				<div class="column_320">
					<p>
						4. <img src="{$basic.tpl_path}img/flag_neu.gif" alt="NEU"/> <a href="">Lorem ipsum dolor sit amet consectetuer</a>
					</p>
				</div>
				<div class="column_45">&nbsp;</div>
				<div class="column_155">
					<p>
						<a href="">Silke Musterfrau</a>
					</p>
				</div>
				<div class="column_155">
					<p>00.00.0000 00:00h</p>
				</div>
				<div class="clear"> </div>
			</div>
		</div> <!-- Ende item_legend -->
	
	</div> <!-- Ende item body -->
	<div class="clear"> </div>
	
	<div class="item_actions">
		<a href=""><span class="edit_set"> &nbsp; </span></a>
		<a href=""><span class="details_ia"> &nbsp; </span></a>
	</div>
	
	<div class="item_body"> <!-- Start item body -->
		<div class="item_post">
			<div class="row_odd odd_sep_disdetail">
				<div class="column_80">
					<p>
						<a href="" title="Michael Muster"><img src="{$basic.tpl_path}img/person_01.jpg" alt="Michael Muster" /></a>
					</p>
				</div>
				
				<div class="column_510">
					<div class="post_content">
						<h4>1. <img src="{$basic.tpl_path}img/flag_neu.gif" alt="NEU"/> Cum sociis natoque penatibus et magnis</h4>
						<span><a href="">Michael Muster</a>, 00.00.0000 00:00h</span>
						<div class="editor_content">
							Lorem ipsum dolor sit amet, consectetuer adipiscing elit. Aenean commodo ligula eget dolor. Aenean massa. Cum sociis natoque penatibus et magnis dis parturient montes, nascetur ridiculus mus. Donec quam felis, ultricies nec, pellentesque eu, pretium quis, sem. Nulla consequat massa quis enim. Donec pede justo, fringilla vel, aliquet nec, vulputate eget, arcu. In enim justo, rhoncus ut, imperdiet a, venenatis vitae, justo. Nullam dictum felis eu pede mollis pretium. Integer tincidunt. Cras dapibus.
						</div>
					</div>
				</div>
				<div class="column_27">
					<p class="jump_up_down">
						<a href=""><img src="{$basic.tpl_path}img/btn_jump_up.gif" alt="hoch" /></a>
						<a href=""><img src="{$basic.tpl_path}img/btn_jump_down.gif" alt="runter" /></a>
					</p>
				</div>
				<div class="column_45">
					<p>
						<a href="" class="attachment">12</a>
					</p>
				</div>
				<div class="clear"> </div>
			</div>
		</div>
	</div> <!-- Ende item body -->
	<div class="clear"> </div>
	
	<div class="item_actions">&nbsp;</div>
	
	<div class="item_body"> <!-- Start item body -->
		<div class="item_post">
			<div id="item_postnew">
				<div class="column_80">
					<p>
						<a href="" title="Michael Muster"><img src="{$basic.tpl_path}img/person_01.jpg" alt="Michael Muster" /></a>
					</p>
				</div>
				
				<div class="column_590">
					<div class="post_content">
						<h4>5.</h4><input id="pn_title" type="text" name="" />
						<div class="editor_content">
							<img src="{$basic.tpl_path}img/editor.jpg" alt="" />
						</div>
					</div>
				</div>
				<div class="clear"> </div>
			</div>
		</div>
	</div> <!-- Ende item body -->
	<div class="clear"> </div>
	
	<div class="clear"> </div>
{/block}
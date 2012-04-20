<div id="popup_wrapper">
	<div id="popup_background"></div>

	<div class="tm_dropmenu hidden">
		<div class="tm_di_ground_solid">

			<div id="popup">

				<div id="popup_content">
					<div id="profile_content_row_three">
					
						<div class="tm_drop_item">
							<div class="tm_di_ground">
								<p>Sie sind hier:</p>
								{foreach $popup.breadcrumb as $crumb}
									{if !$crumb@first}
										<span class="tm_bcb_next">
									{/if}
			
									{if !$crumb@last}
										<a href="commsy.php?cid={$crumb.id}&mod=home&fct=index">{$crumb.title}</a>
									{else}
										<strong>{$crumb.title}</strong>
									{/if}
			
									{if !$crumb@first}
										</span>
									{/if}
								{/foreach}
							</div>
						</div>
						<div class="tm_drop_item">
							<div class="tm_di_ground">
								<p>Raum wechseln:</p>
								<a href=""><strong>CommSy Community</strong></a>
								<br/><a href="">Donec pede justo</a>
								<br/><a href="">Aenean vulputate</a>
							</div>
						</div>
							
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
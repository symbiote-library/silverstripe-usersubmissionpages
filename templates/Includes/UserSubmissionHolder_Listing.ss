<% if $Listing %>
	<section class="submission__list">
		<% loop $Listing %>
			<article class="submission__item" data-id="$ID">
				$TemplateHolderMarkup
			</article>
		<% end_loop %>
	</section>
<% else %>
	<p>No submissions available.</p>
<% end_if %>
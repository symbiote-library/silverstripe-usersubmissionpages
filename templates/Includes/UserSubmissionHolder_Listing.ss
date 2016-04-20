<% if $Listing %>
	<section class="submission">
		<div class="submission__list">
			<% loop $Listing %>
				<article class="submission__item" data-id="$ID">
					$TemplateHolderMarkup
				</article>
			<% end_loop %>
		</div>
		<div class="submission_pagination">
			<% include Pagination PaginatedPages=$Listing %>
		</div>
	</section>
<% else %>
	<p>No submissions available.</p>
<% end_if %>
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
	<% if $UserSubmissionSearchForm && $UserSubmissionSearchForm.HasSearched %>
		<p>Unable to find submissions that match your search.</p>
	<% else %>
		<p>No submissions available.</p>
	<% end_if %>
<% end_if %>
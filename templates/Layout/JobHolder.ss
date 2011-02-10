$Content

<% if ShowJobs %>
	<% if Jobs %>
		<ul id="JobListings">
			<% control Jobs %>
				<li class="$EvenOdd">
			 		<h3><a href="$Link">$Title.LimitCharacters(35)</a>
					<small>$LastEdited.Ago</small>
					</h3>
		
					<p class="companyInfo">
						<% if Company %><% if URL %><a href="$NiceURL">$Company</a>,<% else %>$Company,<% end_if %><% end_if %> $NiceLocation
					</p>
					<p class="listingType">$Type</p>
				</li>
			<% end_control %>
		</ul>
	<% else %>
		<p class="no-listing">Sorry no jobs available. <a href="$Link(post)">Add one</a></p>
	<% end_if %>
<% end_if %>
$Form
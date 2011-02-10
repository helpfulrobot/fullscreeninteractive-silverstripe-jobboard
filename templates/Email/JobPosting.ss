<% if FirstPost %>
	<% if Job %>
		<h2>Welcome to the Job Board</h2>

		<p>Well done on posting your first job!. You can view it online at <a href="$Job.AbsoluteLink">$Job.AbsoluteLink</a></p>
	<% end_if %>
	
	<h3>Account Details</h3>
	<p>The login details to edit and delete your listings are: <br />
		<strong>Username / Email:</strong> $Member.Email<br />
		<strong>Password:</strong> $Password</p>
<% else %>
	<h2>Thanks for your post to the Job Board</h2>

	<p>You can view it online at <a href="$Job.AbsoluteLink">$Job.AbsoluteLink</a></p>

	<h3>Listing Details</h3>
	<p>If you would like to remove your listing or edit the details you can make changes to it at <a href="$Job.AbsoluteLink(edit)/$Job.ID">$Job.AbsoluteLink(edit)/$Job.ID/a></p>
	<p><strong>Title:</strong> $Job.Title</p>
<% end_if %>
	
<% if Job %>
	<h3>Listing Details</h3>
	<p>If you would like to remove your listing or edit the details you can make changes to it at <a href="$Job.AbsoluteLink(edit)/$Job.ID">$Job.AbsoluteLink(edit)/$Job.ID</a></p>
	<p><strong>Title:</strong> $Job.Title</p>
<% end_if %>

<p>---<br />
Thanks,<br /></p>
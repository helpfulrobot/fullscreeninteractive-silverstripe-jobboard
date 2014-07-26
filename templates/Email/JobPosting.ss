<% if FirstPost %>
	<h2>Welcome to the Job Board</h2>
	
	<h3>Account Details</h3>
	<p>The login details to edit and delete your listings are: <br />
		<strong>Username / Email:</strong> $Member.Email<br />
		<strong>Password:</strong> $Password</p>
<% else %>
	<h2>Thanks for your post to the Job Board</h2>
<% end_if %>


<p>You can view the job online at <a href="$Job.AbsoluteLink?asp=1">$Job.AbsoluteLink</a>. It <strong>will not</strong> be publicly viewable until it has been moderated first.</p>

<h3>Listing Details</h3>

<p>If you would like to remove your listing or edit the details you can make changes to it at <a href="$Job.AbsoluteLink?asp=1">$Job.AbsoluteLink</a></p>

<p><strong>Title:</strong> $Job.Title</p>

<p>---<br />
Thanks<br /></p>
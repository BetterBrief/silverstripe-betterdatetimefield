<div id="$HolderID" class="field<% if extraClass %> $extraClass<% end_if %>">
	<% if Title %><label class="left" for="$ID">$Title</label><% end_if %>
	<div class="middleColumn">
		<% if $ShowDateFields %>
			$DateField
		<% end_if %>
		<% if $ShowTimeFields %>
			$TimeField
		<% end_if %>
	</div>
	<% if RightTitle %><label class="right" for="$ID">$RightTitle</label><% end_if %>
	<% if Message %><span class="message $MessageType">$Message</span><% end_if %>
	<% if Description %><span class="description">$Description</span><% end_if %>
</div>

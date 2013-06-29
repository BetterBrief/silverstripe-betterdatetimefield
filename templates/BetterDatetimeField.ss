<% if $ShowDateFields %>
	<% if $DateField.isComposite %>
		$DateField
	<% else %>
		$DateField.FieldHolder
	<% end_if %>
<% end_if %>
<% if $ShowTimeFields %>
	$TimeField
<% end_if %>

// Remove some elements from interface
jQuery(document).ready(function() {
	// Rows actions
	jQuery('.locked-content .row-actions .edit').remove();
	jQuery('.locked-content .row-actions .inline').remove();
	jQuery('.locked-content .row-actions .trash').remove();

	// Checkbox row
	jQuery('.locked-content .check-column').html('');

	// HTML to txt for title row
	jQuery('.locked-content .column-title strong a').attr('href', '#');
	
	// Terms edition
	jQuery('.locked-term-parent').closest('td.column-name').find('strong a').attr('href', '#');
	jQuery('.locked-term-parent').closest('td.column-name').prev('th').html('');
	jQuery('.locked-term-parent').closest('tr').addClass('locked-content')
});
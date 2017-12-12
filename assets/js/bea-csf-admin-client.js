// Remove some elements from interface
jQuery(document).ready(function () {
    // Terms edition
    jQuery('.locked-term-parent').closest('td.column-name').find('strong a').attr('href', '#');
    jQuery('.locked-term-parent').closest('td.column-name').prev('th').html('');
    jQuery('.locked-term-parent').closest('tr').addClass('locked-content');

    jQuery('.postbox span.screen-reader-text .bea-csf-acf-exclusion').remove();
});
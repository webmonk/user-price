jQuery( function($){
	$('.userprice a.add_option').live('click', function(){
		var size = jQuery('#price_options tr').size();
		
		// Add the row
		$('<tr class="price_option">\
			<td class="check-column"><input type="checkbox" style="margin: 0 0 0 8px;" name="select" /></td>\
			<td class="p_id">\
				<input type="text" class="text" name="item_number[' + size + ']" placeholder="ID" title="ID" size="16" />\
			</td>\
			<td class="price">\
				<input type="text" class="text" name="price[' + size + ']" title="Price" placeholder="Price" size="16" />\
			</td>\
			<td class="price_suffix">\
				<input type="text" class="text" name="price_suffix[' + size + ']" title="Price Suffix" placeholder="Price Suffix" size="16" />\
			</td>\
		</tr>').appendTo('#price_options');
			return false;
	});
	
	$('.userprice a.remove').live('click', function(){
		var answer = confirm("Delete selected User Price Option?")
		if (answer) {
			var $rates = $(this).closest('.userprice').find('tbody');
			
			$rates.find('tr td.check-column input:checked').each(function(i, el){
				$(el).closest('tr').find('input.text, input.checkbox, select.select').val('');
				$(el).closest('tr').remove();
			});
		}
		return false;
	});
});
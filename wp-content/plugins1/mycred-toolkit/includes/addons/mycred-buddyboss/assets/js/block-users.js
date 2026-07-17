jQuery(document).ready( function() {

	jQuery('.mycred-selectize').selectize({
		plugins: ['remove_button', 'drag_drop'],
		sortField: "text",
		delimiter: ",",
		create: false,
		persist: false,
    	placeholder: 'Select',
		maxItems: 10, 
  		maxOptions: 30, 
  		onChange(value) {

  		}
	});

	jQuery(".buddyboss-check-point-type").change(function(){
		
		if(this.checked) {
	        jQuery(this).closest('div.checkbox').find('input[type=hidden]').val(true) ;  
		}
		else   {
			jQuery(this).closest('div.checkbox').find('input[type=hidden]').val(false) ;
		}
    });

    jQuery(".buddyboss-forums-check-point-type").change(function(){
		
		if(this.checked) {
	        jQuery(this).closest('div.forums-checkbox').find('input[type=hidden]').val(true) ;  
		}
		else   {
			jQuery(this).closest('div.forums-checkbox').find('input[type=hidden]').val(false) ;
		}
    });

    jQuery(".buddyboss-activity-check-point-type").change(function(){
		
		if(this.checked) {
	        jQuery(this).closest('div.activity-checkbox').find('input[type=hidden]').val(true) ;  
		}
		else   {
			jQuery(this).closest('div.activity-checkbox').find('input[type=hidden]').val(false) ;
		}
    });

     jQuery(".buddyboss-avatar-check-point-type").change(function(){
		
		if(this.checked) {
	        jQuery(this).closest('div.avatar-checkbox').find('input[type=hidden]').val(true) ;  
		}
		else   {
			jQuery(this).closest('div.avatar-checkbox').find('input[type=hidden]').val(false) ;
		}
    });

} );




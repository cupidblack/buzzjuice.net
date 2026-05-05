jQuery( document ).ready( function(){

	 jQuery(document).on('click', '#mycred-pref-buddypress-charges-activity-ranks-and-badges-userbadges', function(){
	 	
        if(jQuery(this).prop("checked") == true){
            var button = jQuery('.userselectedoption').show();
        }
        else if(jQuery(this).prop("checked") == false){
            var button = jQuery('.userselectedoption').hide();
        }
    });

});
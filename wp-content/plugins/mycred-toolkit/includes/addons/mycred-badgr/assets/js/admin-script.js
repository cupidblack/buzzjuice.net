jQuery( document ).ready( function(){

	//Admin Badgr Login
	jQuery( document ).on( 'click', '#badgr-login', function( e ){
		e.preventDefault();
		
		var userName = jQuery( '#badgr-email' ).val();
		var userPassword = jQuery( '#badgr-password' ).val();
		var entityId = jQuery( '#badgr-entit-id' ).val();
		var issuerEntityId = jQuery( '#issuer-entity-id' ).val();

		
		jQuery.ajax({
			url: ajaxurl,
			data: {
				action: 'mycred_badgr_admin',
				user_name:userName,
				user_password:userPassword,
				entity_id:entityId,
				issuer_entity_id: issuerEntityId
			},
			beforeSend: function() {    
				jQuery('.mycred-switch-all-badges-icon').css("display", "inherit");
			},
			success:function( data ) {
				if( isJSON( data ) );
					data = JSON.parse( data );


				
				console.log( data );
				if( data['result'] == 'accessed' )
				{
					jQuery( '#badgr-access-token' ).val( data['access_token'] );
					jQuery( '#badgr-refresh-token' ).val( data['refresh_token'] );
					jQuery( '#badgr-entit-id' ).val( data['entity_id'] );
					jQuery( '#issuer-entity-id' ).val( data['issuer_entity_id'] );
					jQuery( '#submit' ).click();
				}
				else
					jQuery( '#response-message' ).html( data );
				
				jQuery('.mycred-switch-all-badges-icon').hide();
			}
		})
		
	} );

	//Admin Badgr Logout
	jQuery( document ).on( 'click', '#badgr-login-disconnect', function( e ){
		e.preventDefault();

		jQuery.ajax({
			url: ajaxurl,
			data: {
				action: 'badgr-login-disconnect',
			},
			beforeSend: function() {    
				jQuery('.mycred-switch-all-badges-icon').css("display", "inherit");
			},
			success:function( data ) {
				jQuery('.mycred-switch-all-badges-icon').hide();
				jQuery( '#submit' ).click();
			}
		});

	})
} );

function isJSON( text )
{
    if (typeof text!=="string")
	{
        return false;
    }
    try
	{
        JSON.parse( text );
        return true;
    }
    catch ( error ){
        return false;
    }
}
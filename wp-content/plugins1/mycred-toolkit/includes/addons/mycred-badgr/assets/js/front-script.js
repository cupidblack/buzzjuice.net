jQuery( document ).ready( function(){

    //User Login
	jQuery( document ).on( 'click', '#mycred-badgr-login', function( e ){

		e.preventDefault();

		var email = jQuery( '#mycred-br-user-email' ).val();

		var password = jQuery( '#mycred-br-user-password' ).val();

		var accessToken = jQuery( '#badgr-access-token' ).val();

		var refreshToken = jQuery( '#badgr-refresh-token' ).val();

		var entityId = jQuery( '#badgr-entit-id' ).val();

		jQuery.ajax({
			url: '/wp-admin/admin-ajax.php',		
			data: {
				action: 'mycred-badgr-user-login',
				email: email,
				password: password,
				access_token: accessToken,
				refresh_token: refreshToken,
				entity_id: entityId
			},
			beforeSend: function() {    
				jQuery('.mycred-ajax-switch').css("display", "inherit");
			},
			success: function( data ){
				if( isJSON( data ) );
					data = JSON.parse( data );

				if( data == 'Both fields are required' )
				{
					jQuery( '#response-message' ).html( data );
					jQuery('.mycred-ajax-switch').hide();
					return false;
				}

				if( data['result'] == 'accessed' )
				{
					jQuery( '#badgr-access-token' ).val( data['access_token'] );
					jQuery( '#badgr-refresh-token' ).val( data['refresh_token'] );
					jQuery( '#badgr-entit-id' ).val( data['entity_id'] );
					mycred_badgr_save_logins();
				}
				else
				{
					jQuery( '#response-message' ).html( data );
					jQuery('.mycred-ajax-switch').hide();
					return false;
				}
			}
		});
	} );

	//User Login Disconnect
	jQuery( document ).on( 'click', '#mycred-badgr-login-disconnect', function( e ){
		e.preventDefault();

		jQuery.ajax({
			url: '/wp-admin/admin-ajax.php',
			data: {
				action: 'mycred-badgr-user-login-disconnect',
			},
			beforeSend: function() {    
				jQuery('.mycred-ajax-switch').css("display", "inherit");
			},

			success:function( data ) {
				location.reload();
			}
		});
	} );

	//User badges sync
	jQuery( document ).on( 'click', '#mycred-badgr-user-sync', function( e ){
		e.preventDefault();
		
		jQuery.ajax({
			url: '/wp-admin/admin-ajax.php',
			data: {
				action: 'mycred-badgr-user-sync',
			},

			beforeSend: function() {    
				jQuery( '#mycred-badgr-user-sync' ).find( '.mycred-ajax-switch' ).css("display", "inherit");
			},

			success:function( data ) {
				jQuery( '#mycred-badgr-user-sync' ).find('.mycred-ajax-switch').hide();
			}
		})

	} );
} );

function mycred_badgr_save_logins()
{
	var email = jQuery( '#mycred-br-user-email' ).val();

	var password = jQuery( '#mycred-br-user-password' ).val();

	var accessToken = jQuery( '#badgr-access-token' ).val();

	var refreshToken = jQuery( '#badgr-refresh-token' ).val();

	var entityId = jQuery( '#badgr-entit-id' ).val();

	jQuery.ajax({
		url: '/wp-admin/admin-ajax.php',		
		data: {
			action: 'mycred-badgr-save-logins',
			email: email,
			password: password,
			access_token: accessToken,
			refresh_token: refreshToken,
			entity_id: entityId
		},

		success:function( data ) {
			jQuery('.mycred-ajax-switch').hide();
			location.reload();
		}
	})
}


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
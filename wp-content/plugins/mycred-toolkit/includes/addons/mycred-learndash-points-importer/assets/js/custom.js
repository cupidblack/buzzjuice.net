jQuery( document ).ready(function() {

    var prefix = 'mycred_learndash_points_importer_';
    var loop = 0;

    function mycred_learndash_points_importer_run( loop ) {

        var data = {
            action: prefix + 'import',
            loop: loop,
            // Tool data
            points_type: jQuery('#' + prefix + 'points_type').val(),
            workflow: jQuery('#' + prefix + 'workflow').val(),
        };

        jQuery.ajax({
            url: ajaxurl,
            method: 'POST',
            data: data,
            success: function( response ) {

                if( response.data.run_again !== undefined && response.data.run_again ) {
                    // If run again is set, we need to send again the same action

                    // Keep the spinner and add the server response
                    jQuery('#' + prefix + 'response').html(
                        '<span class="spinner is-active" style="float: none; margin: 0;"></span>'
                        + '<span style="display: inline-block; padding-left: 5px;">'
                            + ( response.data.message !== undefined ? response.data.message : response.data )
                        + '</span>'
                    );

                    loop++;

                    // Runs again the same process
                    mycred_learndash_points_importer_run( loop );
                } else {

                    // Remove the spinner
                    jQuery('#' + prefix + 'response').find('.spinner').remove();

                    // Restore the run import button
                    jQuery('#' + prefix + 'run').prop('disabled', false);

                    jQuery('#' + prefix + 'response').html(
                        '<span ' + ( ! response.success ? 'style="color: #a00;"' : '' ) + '>'
                            + response.data !== undefined ? response.data : 'Import finished succesfully!'
                        + '</span>' );
                }

            },
            error: function( response ) {

                jQuery('#' + prefix + 'response').append( '<br>'
                    + '<span style="color: #a00;">'
                        + response.data !== undefined ? response.data : 'Internal server error'
                    + '</span>' );

                return;
            }
        });
    }

    jQuery('#' + prefix + 'run').on('click', function(e) {
        e.preventDefault();

        var jQuerythis = jQuery(this);

        jQuerythis.prop('disabled', true);

        if( jQuery('#' + prefix + 'response').length ) {
            jQuery('#' + prefix + 'response').remove();
        }

        // Show the spinner
        jQuerythis.parent().append('<div id="' + prefix + 'response" style="display: inline-block; margin-left: 5px;"><span class="spinner is-active" style="float: none; margin: 0;"></span></div>');

        // On click, set this var to meet that is first time that it runs
        loop = 0;

        mycred_learndash_points_importer_run( loop );

    })

}) 
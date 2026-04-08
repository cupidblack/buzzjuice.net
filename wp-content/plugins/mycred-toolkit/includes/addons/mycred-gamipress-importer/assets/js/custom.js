jQuery(document).ready(function () {
    jQuery('.gi_import_button').on('click', function (e) {
        e.preventDefault();
        var id = jQuery(this).attr('id');
        jQuery.ajax({
            url: ajaxurl,
            data: {
                action: 'mycred_gi_import_data',
                check:id
            },
            type: 'POST',
            success:function(data) {
                jQuery('.gi_message').append('<p><span class="dashicons dashicons-yes"></span>'+data+'</p>');
            }
        })
        return false;
    })
})
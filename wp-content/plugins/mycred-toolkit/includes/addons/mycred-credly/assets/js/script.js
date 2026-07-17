/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

jQuery(document).ready(function ($) {
    $(document).on('click', '#mycred_credly_connect_badge', function (e) {
        e.preventDefault();
    
        var ele = $(this);
        ele.attr('disabled', 'disabled');
        ele.find('img').show();
    
        $('.overlay-credly-modal').fadeIn(300);

        $('.close-modal-btn').on('click', function () {
            $('.overlay-credly-modal').fadeOut(300); 
        });

        $(window).on('click', function (e) {
            if ($(e.target).is('.overlay-credly-modal')) {
                $('.overlay-credly-modal').fadeOut(300);
            }
        });
    
        $('.close-modal').on('click', function () {
            $('.overlay-credly-modal').fadeOut(300); 
        });
    
        $(window).on('click', function (e) {
            if ($(e.target).is('.overlay-credly-modal')) {
                $('.overlay-credly-modal').fadeOut(300);
            }
        });
    
        $.ajax({
            url: mycred_credly.ajaxurl, 
            type: 'post',
            data: {
                'action': 'get-mycred-credly-badges-list',
                'nonce': mycred_credly.nonce 
            },
            beforeSend: function() {
                $('#mycred-credly-badge-container').html(`
                    <div id="loading-spinner" style="text-align: center; margin: auto;">
                        <img src="${mycred_credly.loading}" alt="Loading..." style="width: 50px; height: 50px;">
                    </div>
                `);
            },
            success: function (response) {
                if (response.success === true) {
                    $('#mycred-credly-badge-container').empty(); 
    
                    if (Array.isArray(response.data.data) && response.data.data.length) {
                        response.data.data.forEach(function (element) {
                            if (element.image_url && element.name && element.description) {
                                var badgeHtml = `
                                    <div class="mycred-credly-badge-item" style="margin-bottom: 20px; display: flex; align-items: center;">
                                        <div class="mycred-credly-badge-image" >   
                                            <img src="${element.image_url}" alt="${element.name}" style="width: 100px; height: 100px; margin-right: 10px;">
                                        </div>    
                                     <div class="mycred-credly-badge-content">
                                            <h3>${element.name}</h3>
                                            <p>${element.description}</p>
                                            <button class="button sync-badge-button" data-badge-id="${element.id}">
                                                Sync
                                            </button>
                                        </div>
                                    </div>
                                `;
                                $('#mycred-credly-badge-container').append(badgeHtml);
                            } else {
                                console.log('Missing required fields:', element);
                            }
                        });
    
                        $('#mycred-credly-badge-container').on('click', '.sync-badge-button', function () {
                            var badgeId = $(this).data('badge-id');
                            var badgeItem = $(this).closest('.mycred-credly-badge-item');
                            var badgeName = badgeItem.find('h3').text();
                            var badgeDescription = badgeItem.find('p').text();
                            var badgeImage = badgeItem.find('img').attr('src');
    
                            $(this).attr('disabled', 'disabled');
                            $(this).addClass( 'loading' );
    
                            $.ajax({
                                url: mycred_credly.ajaxurl, 
                                type: 'post',
                                data: {
                                    'action': 'sync_credly_badge',
                                    'badge_id': badgeId,
                                    'badge_title': badgeName,
                                    'badge_desc': badgeDescription,
                                    'badge_img': badgeImage,
                                    'nonce': mycred_credly.nonce 
                                },
                                success: function (response) {
                                    if (response.success === true) {
                                        location.reload();
                                    } else {
                                        alert(response.data.message || 'Failed to sync badge.');
                                        $('.sync-badge-button').removeClass( 'loading' );
                                        $('.sync-badge-button').removeAttr('disabled');
                                    }
                                },
                                error: function (jqXHR, textStatus, errorThrown) {
                                    alert('Error syncing badge: ' + textStatus + ' - ' + errorThrown);
                                }.bind(this)
                            });
                        });
                    } else {
                        alert('No badges found in Credly.');
                        $('.overlay-credly-modal').fadeOut(300);
                    }
                } else {
                    alert('Failed to retrieve badges from Credly.');
                    $('.overlay-credly-modal').fadeOut(300);
                }
                ele.removeAttr('disabled');
                ele.find('img').hide();
            },
            error: function (jqXHR, textStatus, errorThrown) {
                alert('Error: ' + textStatus + ' - ' + errorThrown);
                ele.removeAttr('disabled');
                ele.find('img').hide();
            }
        });
    });    
});



(function( $, window, document, undefined ) {
	if( $('.extended-widget-opts-tabs').length > 0 ){
		extended_widget_opts_init( '', 'loaded' );
	}

	$('.widget-liquid-right .widget-opts-color').wpColorPicker();

	$(document).ready(function(){
		$(".widget-liquid-right .widget, .inactive-sidebar .widget, #accordion-panel-widgets .customize-control-widget_form").each(function (i, widget) {
	    	extended_widget_opts_init( '', 'loaded' );
	  	});
	  	$(document).on('widget-added', function(event, widget) {
		    extended_widget_opts_init( widget, 'added' );

		});
		$(document).on('widget-updated', function(event, widget) {
			extended_widget_opts_init( widget, 'updated' );
		});
		$(document).on( 'click', '.extended-widget-opts-inner-lists h4',function(){
			getid = $(this).attr('id');
			$('.extended-widget-opts-inner-lists .'+ getid).slideToggle(250);
		} );
		$(document).on( 'click', '.widget-opts-toggler-note',function(e){
			$(this).parent('p').parent('.widget-opts-logic').find('.widget-opts-toggle-note').slideToggle(250);

			e.preventDefault();
			e.stopPropagation();
		} );

		//admin settings
		$(document).on( 'click', '.opts-add-class-btn',function(e){
			getVal = $('.opts-add-class-txtfld').val();
			var fname = 'extwopts_class_settings[classlists][]';
			if( $(this).hasClass('widgetopts-add-class-btn') ){
				fname = 'classes[classlists][]';
			}
			if( getVal.length > 0 ){
				$('#opts-predefined-classes ul').append('<li><input type="hidden" name="'+ fname +'" value="'+ getVal +'" /><span class"opts-li-value">'+ getVal +'</span> <a href="#" class="opts-remove-class-btn"><span class="dashicons dashicons-dismiss"></span></a></li>');
				$('.opts-add-class-txtfld').val('');
			}

			e.preventDefault();
			e.stopPropagation();
		} );
		$(document).on( 'click', '.opts-remove-class-btn',function(e){
			$(this).parent('li').fadeOut('fast',function(){
				$(this).remove();
			});
			e.preventDefault();
			e.stopPropagation();
		} );


		//Image Uploader
		var file_frame;

	    jQuery('body').on('click','.extended_widget_opts-bg_uploader', function( event ){
	        event.preventDefault();
	        var widget_id = jQuery(this).attr('data-widget-id');

	        // Create the media frame.
	        file_frame = wp.media.frames.file_frame = wp.media({
	          title: jQuery( this ).data( 'uploader_title' ),
	          button: {
	            text: jQuery( this ).data( 'uploader_button_text' ),
	          },
	          multiple: false  // Set to true to allow multiple files to be selected
	        });

	        // When an image is selected, run a callback.
	        file_frame.on( 'select', function() {
	          // We set multiple to false so only get one image from the uploader
	          attachment = file_frame.state().get('selection').first().toJSON();
	          jQuery('#extended-widget-opts-tab-'+ widget_id +'-styling .extended_widget_opts-bg-image').val(attachment.url).trigger('change');
	        });

	        // Finally, open the modal
	        file_frame.open();
	    });

	    jQuery('body').on('click','.extended_widget_opts-remove_image', function( event ){
	        var widget_id = jQuery(this).attr('data-widget-id');
	        jQuery('#extended-widget-opts-tab-'+ widget_id +'-styling .extended_widget_opts-bg-image').val('').trigger('change');
	        event.preventDefault();
	    });

		if( $('.wp-admin.widgets-php .wrap a.page-title-action').length > 0 ){
			$('.wp-admin.widgets-php .wrap a.page-title-action').after('<a href="'+ widgetopts10n.opts_page +'" class="page-title-action hide-if-no-customize widgetopts-super">'+ widgetopts10n.translation.manage_settings +'</a>');
		}

		//add live filter
		if ( typeof $.fn.liveFilter !== 'undefined' && $( '#widgetopts-widgets-search' ).length > 0 ) {
			// Add separator to distinguish between visible and hidden widgets
			$('.widget:last-of-type').after('<div class="widgetopts-separator" />');

			// Add data attribute for order to each widget
			$('#widgets-left .widget').each(function() {
				var index = $(this).index() + 1;
				$(this).attr( 'data-widget-index', index );
			});

			// Add liveFilter : credits to https://wordpress.org/plugins/widget-search-filter/ plugin
			$('#widgets-left').liveFilter('#widgetopts-widgets-search', '.widget', {
				filterChildSelector: '.widget-title h4, .widget-title h3',
				after: function(contains, containsNot) {

					// Move all hidden widgets to end.
					containsNot.each(function() {
						$(this).insertAfter($(this).parent().find('.widgetopts-separator'));
					});

					// Sort all visible widgets by original index
					contains.sort(function(a,b) {
						return a.getAttribute('data-widget-index') - b.getAttribute('data-widget-index');
					});

					// Move all visible back
					contains.each(function() {
						$(this).insertBefore($(this).parent().find('.widgetopts-separator'));
					});

				}
			});

			//add clear search
			$( '#wpbody-content' ).on( 'keyup', '.widgetopts-widgets-search', function(e){
				p = $(this).parent().find( '.widgetopts-clear-results' );
				if ( '' !== $(this).val() ) {
					p.addClass( 'widgetopts-is-visible' );
				}else{
					p.removeClass( 'widgetopts-is-visible' );
				}
			} );

			$( '#wpbody-content' ).on( 'click', '.widgetopts-clear-results', function(e){
				s = $(this).parent().find( '.widgetopts-widgets-search' );
				s.val( '' ).focus().trigger( 'keyup' );

				if( s.attr( 'id' ) == 'widgetopts-search-chooser' ){
					$( '.widgets-chooser-sidebars li:not(:first)' ).removeClass( 'widgets-chooser-selected' );
				}

				e.preventDefault();
				e.stopPropagation();
				return false;
			} );

			//add sidebar chooser search field
			$('.widgets-chooser').prepend( widgetopts10n.search_form );
			//live filter
			$('.widgets-chooser').liveFilter('#widgetopts-search-chooser', '.widgets-chooser-sidebars li', {
				// filterChildSelector: 'li',
				after: function( contains, containsNot ) {
					//hide
					containsNot.each(function() {
						$(this).addClass( 'widgetopts-is-hidden' ).removeClass( 'widgets-chooser-selected' );
					});
					contains.each(function() {
						$(this).removeClass( 'widgetopts-is-hidden' ).removeClass( 'widgets-chooser-selected' );
					});
					if( contains.length > 0 ){
						$( contains[0] ).addClass( 'widgets-chooser-selected' );
					}

				}
			});

		}

	});

	function extended_widget_opts_init( widget, action ){
		selected 			= 0;
		selected_styling 	= 0;
		selected_visibility = 0;
		selected_settings 	= 0;
		in_customizer 		= false;
		// check for wp.customize return boolean
	    if ( typeof wp !== 'undefined' ) {
	        in_customizer =  typeof wp.customize !== 'undefined' ? true : false;
	    }
		if( ''	!=	widget ){
			if( $( '#' + widget.attr('id') ).find('#extended-widget-opts-selectedtab').length > 0 ){
				selected = $( '#' + widget.attr('id') ).find('#extended-widget-opts-selectedtab').val();
				selected = parseInt( selected );
			}

			if( $( '#' + widget.attr('id') ).find('#extended-widget-opts-styling-selectedtab').length > 0 ){
				selected_styling = $( '#' + widget.attr('id') ).find('#extended-widget-opts-styling-selectedtab').val();
				selected_styling = parseInt( selected_styling );
			}

			if( $( '#' + widget.attr('id') ).find('#extended-widget-opts-visibility-selectedtab').length > 0 ){
				selected_visibility = $( '#' + widget.attr('id') ).find('#extended-widget-opts-visibility-selectedtab').val();
				selected_visibility = parseInt( selected_visibility );
			}

			if( $( '#' + widget.attr('id') ).find('#extended-widget-opts-visibility-selectedtab').length > 0 ){
				selected_settings = $( '#' + widget.attr('id') ).find('#extended-widget-opts-settings-selectedtab').val();
				selected_settings = parseInt( selected_settings );
			}

			if( $( '#' + widget.attr('id') ).find('.widget-opts-color').length > 0 ){
				if ( in_customizer ) {
					$( '#' + widget.attr('id') ).find('.widget-opts-color').wpColorPicker({
						change: _.throttle( function () { $(this).trigger('change'); }, 1000, {leading: false} )
					});
				}else{
					$( '#' + widget.attr('id') ).find('.widget-opts-color').wpColorPicker();
				}
			}
			// console.log( in_customizer );
		}
		if( action == 'added' ){
			selected 			= 0;
			selected_styling 	= 0;
			selected_visibility = 0;
			selected_settings 	= 0;
		}

	    if( '' != widget ){
	    	if( $( '#' + widget.attr('id') ).find('.extended-widget-opts-tabs').length > 0 ){
	    		$( '#' + widget.attr('id') ).find('.extended-widget-opts-tabs').tabs({ active: selected });
	    	}
	    	if( $( '#' + widget.attr('id') ).find('.extended-widget-opts-styling-tabs').length > 0 ){
	    		$( '#' + widget.attr('id') ).find('.extended-widget-opts-styling-tabs').tabs({ active: selected_styling });
	    	}
	    	if( $( '#' + widget.attr('id') ).find('.extended-widget-opts-visibility-tabs').length > 0 ){
	    		$( '#' + widget.attr('id') ).find('.extended-widget-opts-visibility-tabs').tabs({ active: selected_visibility });
	    	}
	    	if( $( '#' + widget.attr('id') ).find('.extended-widget-opts-settings-tabs').length > 0 ){
	    		$( '#' + widget.attr('id') ).find('.extended-widget-opts-settings-tabs').tabs({ active: selected_settings });
	    	}
	    }else{
	    	$('.extended-widget-opts-tabs').tabs({ active: selected });
	    	$('.extended-widget-opts-styling-tabs').tabs({ active: selected_styling });
	    	$('.extended-widget-opts-visibility-tabs').tabs({ active: selected_visibility });
	    	$('.extended-widget-opts-settings-tabs').tabs({ active: selected_settings });
	    }

	    $('.extended-widget-opts-tabs').click('tabsselect', function (event, ui) {
			if( $(this).find('#extended-widget-opts-selectedtab').length > 0 ){
				$(this).find('#extended-widget-opts-selectedtab').val( $(this).tabs('option', 'active') );
			}
		});

		$('.extended-widget-opts-styling-tabs').click('tabsselect', function (event, ui) {
			if( $(this).find('#extended-widget-opts-styling-selectedtab').length > 0 ){
				$(this).find('#extended-widget-opts-styling-selectedtab').val( $(this).tabs('option', 'active') );
			}
		});

		$('.extended-widget-opts-visibility-tabs').click('tabsselect', function (event, ui) {
			if( $(this).find('#extended-widget-opts-visibility-selectedtab').length > 0 ){
				$(this).find('#extended-widget-opts-visibility-selectedtab').val( $(this).tabs('option', 'active') );
			}
		});

		$('.extended-widget-opts-settings-tabs').click('tabsselect', function (event, ui) {
			if( $(this).find('#extended-widget-opts-settings-selectedtab').length > 0 ){
				$(this).find('#extended-widget-opts-settings-selectedtab').val( $(this).tabs('option', 'active') );
			}
		});

    	$('.extended-widget-opts-date').datepicker({
		    //comment the beforeShow handler if you want to see the ugly overlay
		    beforeShow: function() {
		        setTimeout(function(){
		            $('.ui-datepicker').css('z-index', 99999999999999);
		        }, 0);
		    }
		});
	}
})( jQuery, window, document );

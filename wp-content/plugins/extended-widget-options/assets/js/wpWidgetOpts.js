/*global ajaxurl, isRtl */
var wpWidgetOpts;
(function($) {
	var $document = $( document );

wpWidgetOpts = {
	/**
	 * A closed Sidebar that gets a Widget dragged over it.
	 *
	 * @var element|null
	 */
	hoveredSidebar: null,

	init : function() {
		var self  			= this,
			title 			= $( '.wp-admin.widgets-php .wrap a.page-title-action' ),
			tabs  			= $( '.extended-widget-opts-tabs' ),
			chooser 		= $( '.widgetsopts-chooser' ),
			selectSidebar 	= chooser.find( '.widgetopts-chooser-sidebars' );
			// ta = chooser.find('.widgets-chooser-sidebars'),
			// sidebars = $('div.widgets-sortables'),
			// isRTL = !! ( 'undefined' !== typeof isRtl && isRtl );

			if( tabs.length > 0 ){
				self.loaded( '', 'loaded' );
			}

			$('.widget-liquid-right .widget-opts-color').wpColorPicker({
				change: _.throttle( function () { $(this).trigger('change'); }, 1000, {leading: false} )
			});

			//runs on customizer
			$( '.widget-liquid-right .widget, .inactive-sidebar .widget, #accordion-panel-widgets .customize-control-widget_form' ).each(function (i, widget) {
				self.loaded( '', 'loaded' );
			});

			//fires when widget added
			$document.on( 'widget-added', function( event, widget ) {
			    self.loaded( widget, 'added' );
			});

			//fires when widget updated
			$document.on( 'widget-updated', function( event, widget ) {
			    self.loaded( widget, 'updated' );
			});

			//toggle accordions
			$document.on( 'click', '.extended-widget-opts-inner-lists h4',function(){
				var getid = $(this).attr('id');
				$( '.extended-widget-opts-inner-lists .'+ getid ).slideToggle(250);
			} );

			//toggle widget logic notice
			$document.on( 'click', '.widget-opts-toggler-note',function(e){
				$( this ).parent( 'p' ).parent( '.widget-opts-logic' ).find( '.widget-opts-toggle-note' ).slideToggle( 250 );
				e.preventDefault();
				e.stopPropagation();
			} );

			//image upload fields
			self.image_upload();

			//add link to settings page on title
			if( title.length > 0 ){
				title.after('<a href="'+ widgetopts10n.opts_page +'" class="page-title-action hide-if-no-customize widgetopts-super">'+ widgetopts10n.translation.manage_settings +'</a>');
			}

			//live search filter
			self.live_search();

			//append move and clone button to .widget-control-actions
			$( '.widget-control-actions .alignleft .widget-control-remove' ).after( widgetopts10n.controls );

			//chooser for move and clone action
			self.do_chooser( chooser, selectSidebar );

			//add sidebar options
			self.sidebarOptions();
			self.removeSidebarWidgets();
			self.initAuthorDropdown('');
			self.initRolesDropdown('');
			self.initPageDropdown('');
			self.initTaxonomyDropdown('');
	},
	loaded : function( widget, action ){
		var widget_id,
		selected 			= 0,
		selected_styling 	= 0,
		selected_main 		= 0,
		selected_visibility = 0,
		selected_settings 	= 0,
		in_customizer 		= false,
		colorbox 			= '.widget-opts-color',
		tabs 				= '.extended-widget-opts-tabs',
		styling_tabs 		= '.extended-widget-opts-styling-tabs',
		visibility_main 	= '.extended-widget-opts-visibility-m-tabs',
		visibility_tabs 	= '.extended-widget-opts-visibility-tabs',
		settings_tabs 		= '.extended-widget-opts-settings-tabs',
		selectedtab			= '#extended-widget-opts-selectedtab',
		selectedstyling		= '#extended-widget-opts-styling-selectedtab',
		selectedmain		= '#extended-widget-opts-visibility-m-selectedtab',
		selectedvisibility	= '#extended-widget-opts-visibility-selectedtab',
		selectedsettings	= '#extended-widget-opts-settings-selectedtab';

		// check for wp.customize return boolean
	    if ( typeof wp !== 'undefined' ) {
	        in_customizer =  typeof wp.customize !== 'undefined' ? true : false;
	    }
		if( ''	!=	widget ){
			widget_id = '#' + widget.attr('id');

			if( $( widget_id ).find( selectedtab ).length > 0 ){
				selected = $( '#' + widget.attr('id') ).find( selectedtab ).val();
				selected = parseInt( selected );
			}

			if( $( widget_id ).find( selectedstyling ).length > 0 ){
				selected_styling = $( '#' + widget.attr('id') ).find( selectedstyling ).val();
				selected_styling = parseInt( selected_styling );
			}

			if( $( widget_id ).find( selectedvisibility ).length > 0 ){
				selected_visibility = $( '#' + widget.attr('id') ).find( selectedvisibility ).val();
				selected_visibility = parseInt( selected_visibility );
			}

			if( $( widget_id ).find( selectedmain ).length > 0 ){
				selected_main = $( '#' + widget.attr('id') ).find( selectedmain ).val();
				selected_main = parseInt( selected_main );
			}

			if( $( widget_id ).find( selectedsettings ).length > 0 ){
				selected_settings = $( '#' + widget.attr('id') ).find( selectedsettings ).val();
				selected_settings = parseInt( selected_settings );
			}

			if( $( widget_id ).find( colorbox ).length > 0 ){
				if ( in_customizer ) {
					$( widget_id ).find( colorbox ).wpColorPicker({
						change: _.throttle( function () { $(this).trigger('change'); }, 1000, {leading: false} )
					});
				}else{
					$( widget_id ).find( colorbox ).wpColorPicker({
						change: _.throttle( function () { $(this).trigger('change'); }, 1000, {leading: false} )
					});
				}
			}
			// console.log( in_customizer );
		}
		if( action == 'added' ){
			selected 			= 0;
			selected_main 		= 0,
			selected_styling 	= 0;
			selected_visibility = 0;
			selected_settings 	= 0;
		}
		if( action == 'updated' ){

		}

	    if( '' != widget ){
	    	if( $( widget_id ).find( tabs ).length > 0 ){
	    		$( widget_id ).find( tabs ).tabs({ active: selected });
	    	}
	    	if( $( widget_id ).find( visibility_main ).length > 0 ){
	    		$( widget_id ).find( visibility_main ).tabs({ active: selected_main });
	    	}
	    	if( $( widget_id ).find( styling_tabs ).length > 0 ){
	    		$( widget_id ).find( styling_tabs ).tabs({ active: selected_styling });
	    	}
	    	if( $( widget_id ).find( visibility_tabs ).length > 0 ){
	    		$( widget_id ).find( visibility_tabs ).tabs({ active: selected_visibility });
	    	}
	    	if( $( widget_id ).find( settings_tabs ).length > 0 ){
	    		$( widget_id ).find( settings_tabs ).tabs({ active: selected_settings });
	    	}
	    }else{
	    	$( tabs ).tabs({ active: selected });
	    	$( visibility_main ).tabs({ active: selected_main });
	    	$( styling_tabs ).tabs({ active: selected_styling });
	    	$( visibility_tabs ).tabs({ active: selected_visibility });
	    	$( settings_tabs ).tabs({ active: selected_settings });
	    }

	    $( tabs ).click('tabsselect', function (event, ui) {
			if( $(this).find( selectedtab ).length > 0 ){
				$(this).find( selectedtab ).val( $(this).tabs('option', 'active') );
			}
		});

		$( visibility_main ).click('tabsselect', function (event, ui) {
			if( $(this).find( selectedmain ).length > 0 ){
				$(this).find( selectedmain ).val( $(this).tabs('option', 'active') );
			}
		});

		$( styling_tabs ).click('tabsselect', function (event, ui) {
			if( $(this).find( selectedstyling ).length > 0 ){
				$(this).find( selectedstyling ).val( $(this).tabs('option', 'active') );
			}
		});

		$( visibility_tabs ).click('tabsselect', function (event, ui) {
			if( $(this).find( selectedvisibility ).length > 0 ){
				$(this).find( selectedvisibility ).val( $(this).tabs('option', 'active') );
			}
		});

		$( settings_tabs ).click('tabsselect', function (event, ui) {
			if( $(this).find( selectedsettings ).length > 0 ){
				$(this).find( selectedsettings ).val( $(this).tabs('option', 'active') );
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

		this.initAuthorDropdown(widget_id);
		this.initRolesDropdown(widget_id);
		this.initPageDropdown(widget_id);
		this.initTaxonomyDropdown(widget_id);
	},
	image_upload : function(){
		var file_frame;

	    $document.on( 'click', '.extended_widget_opts-bg_uploader', function( event ){
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
	          jQuery( '#extended-widget-opts-tab-'+ widget_id +'-styling .extended_widget_opts-bg-image' ).val(attachment.url).trigger('change');
	        });

	        // Finally, open the modal
	        file_frame.open();
	    });

	    $document.on( 'click', '.extended_widget_opts-remove_image', function( event ){
	        var widget_id = jQuery(this).attr('data-widget-id');
	        jQuery( '#extended-widget-opts-tab-'+ widget_id +'-styling .extended_widget_opts-bg-image' ).val('').trigger('change');
	        event.preventDefault();
	    });
	},
	live_search : function(){
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
				}else if( s.hasClass('widgetsopts-widgets-search') ){
					$( '.widgetopts-chooser-sidebars li:not(:first)' ).removeClass( 'widgetopts-chooser-selected' );
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
	},
	do_chooser : function( chooser, selectSidebar ){
		var self = this;

		//add sidebar lists on chooser
		$( '#widgets-right .widgets-holder-wrap' ).each( function( index, element ) {
			var $element 	= $( element ),
				name 		= $element.find( '.sidebar-name h2' ).text(),
				id 			= $element.find( '.widgets-sortables' ).attr( 'id' ),
				li 			= $('<li tabindex="0">').text( name.trim() );

			if ( index === 0 ) {
				li.addClass( 'widgetopts-chooser-selected' );
			}

			selectSidebar.append( li );
			li.attr( 'data-sidebarId', id );
		});

		//do click
		$document.on( 'click', '.widgetopts-control', function(e){
			var lbl = $(this).text(),
			action  = $( this ).attr( 'data-action' );

			if( $(this).hasClass( 'widgetopts-control-open' ) ){
				self.closeChooser();
				$( '.widgetopts-control-open' ).removeClass( 'widgetopts-control-open' );
			}else{

				chooser.find( '.widgetopts-chooser-action span' ).text( lbl );
				chooser.find( '.widgetopts-chooser-action' ).attr( 'data-action', action );
	            $(this).parents('.widget-control-actions').find('.clear').after( chooser );

				chooser.slideDown( 300, function() {
					selectSidebar.find('.widgets-chooser-selected').focus();
				});
				$( '.widgetopts-control-open' ).removeClass( 'widgetopts-control-open' );
				$(this).addClass( 'widgetopts-control-open' );

				self.chooserSearch();
			}

            e.preventDefault();
        } );

		//add selected on click
		$document.on( 'click', '.widgetopts-chooser-sidebars li', function(e){
            selectSidebar.find('.widgetopts-chooser-selected').removeClass( 'widgetopts-chooser-selected' );
			$(this).addClass( 'widgetopts-chooser-selected' );
        } );

		//do action
		$document.on( 'click', '.widgetsopts-chooser .widgetopts-chooser-action', function(e){
            var $container 	= $( 'html,body' ),
			$action 		= $( this ).attr( 'data-action' ),
			parentSidebar 	= $( this ).parents('.widgets-sortables').attr('id'),
            widgetID 		= $( this ).parents('.widget').attr('id'),
			$widget			= $( '#'+ widgetID );
            sidebarID 		= $( '.widgetopts-chooser-selected' ).attr('data-sidebarId');
			// console.log( $action + ' ' + parentSidebar +' ' + widgetID + ' ' + sidebarID);
			//remove chooser
			$( '#'+ widgetID + ' .widgetsopts-chooser' ).remove();
			$widget.find(' .widgetopts-control-open' ).removeClass( 'widgetopts-control-open' );

			switch ( $action ) {
				case 'move':
					$( '#' + parentSidebar ).find( '#' + widgetID ).appendTo( '#' + sidebarID );

					$('#' + sidebarID).sortable('refresh');
		            $widget.addClass( 'widgetopts-move-ds' );
		            $( '#' + sidebarID ).parent('.widgets-holder-wrap').removeClass( 'closed' );
					wpWidgets.save( $( '#' + widgetID ), 0, 0, 1 );
					break;

				case 'clone':
					var cloned			= $widget.clone();
					var id_base 		= $widget.find( '.id_base' ).val();
					var widget_id 		= $widget.find( '.widget-id' ).val();
					var number 			= $widget.find( 'input[name="widget_number"]' ).val();
					var get_id_base 	= $( '#widgets-left #widget-list input[class="id_base"][value="'+ id_base +'"]' );
					var widget_item 	= get_id_base.parents( '.widget' );
					var attrID 			= widget_item.attr( 'id' );
					var form 			= get_id_base.parent( 'form' );
					var widget_number	= form.find( 'input.widget_number' ).val();
					var multi_number	= parseInt( form.find( 'input.multi_number' ).val() );
					var new_attr_id		= attrID.replace( '__i__', multi_number );

					//change values
					cloned.attr( 'id', new_attr_id );
					cloned.find( 'input.widget_number' ).val( multi_number );
					cloned.find( 'input.add_new' ).val( 'multi' );
					cloned.find('.widget-content').find('input,select,textarea').each(function() {
						if($(this).attr('name')){
							$(this).attr('name', $(this).attr('name').replace( id_base + '[' + number + ']', id_base + '[' + multi_number + ']' ));
							$(this).attr('name', $(this).attr('name').replace( id_base + '-' + number, id_base + '-' + multi_number ));
						}
						if($(this).attr('id')){
							$(this).attr('id', $(this).attr('id').replace( id_base + '-' + number, id_base + '-' + multi_number ));
						}
					});

					//change values
					cloned.find( 'input[name="extended_widget_opts_name"]' ).val( 'extended_widget_opts-'+ id_base +'-'+ multi_number );
					cloned.find( 'input[value="'+ id_base + '-' + number +'"]' ).val( id_base + '-' + multi_number );
					cloned.find( '.widgetops-sc-opts code' ).text( '[do_widget id="'+ id_base +'-'+ multi_number +'"]' );
					cloned.find( '.widget-control-edit' ).attr( 'href' , cloned.find( '.widget-control-edit' ).attr( 'href' ).replace( id_base + '-' + number, id_base + '-' + multi_number ) );

					//remove text editor
					if( cloned.find( '.text-widget-fields' ).length > 0 ){
						cloned.find( '.text-widget-fields' ).remove();
					}

					if( cloned.find( 'a.wp-color-result' ).length > 0 ){
						cloned.find( 'a.wp-color-result' ).remove();
					}
					// if( cloned.find('.extended-widget-opts-date').length > 0 ){
					// 	cloned.find('.extended-widget-opts-date').datepicker('refresh');
					// }

					//add class for scrolling
					cloned.addClass( 'widgetopts-move-ds' );

					//close origin
					$widget.find( '.widget-inside' ).slideUp( 'fast', function() {
						$widget.attr( 'style', '' );
						$widget.removeClass( 'open' );
					});

					$( cloned ).appendTo( '#' + sidebarID );
					$('#' + sidebarID).sortable('refresh');


					$( '#' + sidebarID ).parent('.widgets-holder-wrap').removeClass( 'closed' );

					wpWidgets.save( $( '#' + new_attr_id ), 0, 0, 1 );
					$( '#' + new_attr_id ).find('input.add_new').val('');
					$document.trigger( 'widget-added', [ cloned ] );

					//update from values
					form.find( 'input.multi_number' ).val( multi_number + 1 );

					// n++;
					break;

				default:
					break;

			}

			var $scrollTo = $( '.widgetopts-move-ds' );

            $container.animate({ scrollTop: $scrollTo.offset().top - ( $container.offset().top + $container.scrollTop() + 60 ) }, 200 );
			$( '.widgetopts-move-ds' ).removeClass( '.widgetopts-move-ds' );
            e.preventDefault();
        } );

		//cancel chooser
		$document.on( 'click', '.widgetsopts-chooser .widgetsopts-chooser-cancel', function(e){
			self.closeChooser( chooser );
			e.preventDefault();
		} );
	},
	closeChooser : function( chooser ) {
		var self = this;

		$( '.widgetsopts-chooser' ).slideUp( 200, function() {
			$( '.widgetopts-control' ).removeClass( 'widgetopts-control-open' );
			$( '#wpbody-content' ).append( this );
		});
	},
	chooserSearch : function(){
		//add livefilter
		if( $( '#widgetsopts-widgets-search' ).length > 0 ){
			$('.widgetsopts-chooser').liveFilter('#widgetsopts-widgets-search', '.widgetopts-chooser-sidebars li', {
				// filterChildSelector: 'li',
				after: function( contains, containsNot ) {
					//hide
					containsNot.each(function() {
						$(this).addClass( 'widgetopts-is-hidden' ).removeClass( 'widgetopts-chooser-selected' );
					});
					contains.each(function() {
						$(this).removeClass( 'widgetopts-is-hidden' ).removeClass( 'widgetopts-chooser-selected' );
					});
					if( contains.length > 0 ){
						$( contains[0] ).addClass( 'widgetopts-chooser-selected' );
					}

				}
			});
		}
	},
	sidebarOptions : function(){
		var self = this;
		if( widgetopts10n.sidebaropts.length > 0 ){
			$( '#widgets-right .widgets-holder-wrap' ).each( function( index, element ) {
				var sidebar_opts_h2 = $(this).find('.widgets-sortables h2').text();
				dl_link = widgetopts10n.sidebaropts.replace( '__sidebaropts__', $(this).find('.widgets-sortables').attr('id') );
				dl_link = dl_link.replace( '__sidebar_opts__', sidebar_opts_h2.trim() );
				$(this).append( dl_link );
			});
		}
	},
	removeSidebarWidgets : function(){
		var self = this;
		var $container 	= $( 'html,body' );
		$document.on( 'click', '.sidebaropts-clear', function(e){
			//show confirmation
			$(this).parent().find( '.sidebaropts-confirm' ).addClass( 'sidebaropts-confirmed' );
			$(this).parent().find( '.sidebaropts-confirm' ).slideToggle(250);

			e.preventDefault();
		});

		$document.on( 'click', '.sidebaropts-confirmed .button', function(e){
			sidebar_id = $(this).parent().parent().parent().find('.widgets-sortables');
			
			if( $(this).hasClass( 'button-primary' ) ){
				var $scrollTo = sidebar_id;

				$(this).parent().slideToggle(50);
				$container.animate({ scrollTop: $scrollTo.offset().top - 50 }, 200 );

				sidebar_id.find( '.widget' ).each( function( index, element ) {
					$( element ).fadeOut();
					wpWidgets.save( $( element ), 1, 1, 0 );
				});

			}else{
				$(this).parent().slideToggle(250);
			}

			e.preventDefault();
		});
	},
	initAuthorDropdown : function(widget_id) {
		var args = {
			ajax: {
		    	url: widgetopts10n.ajax_url,
		    	dataType: 'json',
		    	delay: 250,
		    	type: 'POST',
		    	data: function (params) {
			    	var query = {
			        	action: 'widgetopts_ajax_author_search',
			        	term: params.term
			      	}

			      	return query;
			    }
		  	},
		  	placeholder: 'Search for Authors by Name or Email',
		  	minimumInputLength: 3,
		  	language: {
		        searching: function() {
		            return 'Searching...';
		        }
		    },
		}

		if ( widget_id != '' ) {
			$( widget_id ).find( '.extended-widget-opts-tabcontent-roles .extended-widget-opts-select2-dropdown' ).select2(args);
		}
		else {
			$( '.widget-liquid-right .extended-widget-opts-tabcontent-roles .extended-widget-opts-select2-dropdown' ).select2(args);
		}
	},
	initRolesDropdown : function(widget_id) {
		var args = {
			ajax: {
		    	url: widgetopts10n.ajax_url,
		    	dataType: 'json',
		    	delay: 250,
		    	type: 'POST',
		    	data: function (params) {
			    	var query = {
			        	action: 'widgetopts_ajax_roles_search',
			        	term: params.term
			      	}

			      	return query;
			    }
		  	},
		  	placeholder: 'Search for Roles',
		  	minimumInputLength: 3,
		  	language: {
		        searching: function() {
		            return 'Searching...';
		        }
		    },
		}

		if ( widget_id != '' ) {
			$( widget_id ).find( '.extended-widget-opts-tabcontent-roles .extended-widget-opts-roles-dropdown' ).select2(args);
		}
		else {
			$( '.widget-liquid-right .extended-widget-opts-tabcontent-roles .extended-widget-opts-roles-dropdown' ).select2(args);
		}
	},
	initPageDropdown : function(widget_id) {
		var args = {
			ajax: {
		    	url: widgetopts10n.ajax_url,
		    	dataType: 'json',
		    	delay: 250,
		    	type: 'POST',
		    	data: function (params) {
			    	var query = {
			        	action: 'widgetopts_ajax_page_search',
			        	term: params.term
			      	}

			      	return query;
			    }
		  	},
		  	placeholder: 'Search for Pages',
		  	minimumInputLength: 3,
		  	language: {
		        searching: function() {
		            return 'Searching...';
		        }
		    },
		}

		if ( widget_id != '' ) {
			$( widget_id ).find( '.extended-widget-opts-select2-page-dropdown' ).select2(args);
		}
		else {
			$( '.widget-liquid-right .extended-widget-opts-select2-page-dropdown' ).select2(args);
		}
	},
	initTaxonomyDropdown : function(widget_id) {
		var args = {
			ajax: {
		    	url: widgetopts10n.ajax_url,
		    	dataType: 'json',
		    	delay: 250,
		    	type: 'POST',
		    	data: function (params) {
			    	var query = {
			        	action: 'widgetopts_ajax_taxonomy_search',
			        	term: params.term,
			        	taxonomy: $(this).data('taxonomy')
			      	}

			      	return query;
			    }
		  	},
		  	placeholder: 'Search for Terms',
		  	minimumInputLength: 3,
		  	language: {
		        searching: function() {
		            return 'Searching...';
		        }
		    },
		}

		if ( widget_id != '' ) {
			$( widget_id ).find( '.extended-widget-opts-select2-taxonomy-dropdown' ).select2(args);
		}
		else {
			$( '.widget-liquid-right .extended-widget-opts-select2-taxonomy-dropdown' ).select2(args);
		}
	}
};

$document.ready( function(){ wpWidgetOpts.init(); } );

})(jQuery);

"use strict";

var widgetopts_fe = {
	init: function() {
		var self 	= this;
		self.defaults = {
			'topSpacing' 	: 0,
			'adminbar' 		: 0,
			'elBefore' 		: 0,
			'lastPos' 		: 0,
			'endFixed' 		: varWidgetOpts.shallNotFixed,
			'margin_top' 	: parseInt( varWidgetOpts.margin_top ),
			'disable_width' : parseInt( varWidgetOpts.disable_width ),
			'disable_height': parseInt( varWidgetOpts.disable_height )
		};

		if( jQuery('#wpadminbar').length > 0 ){
			self.defaults.adminbar = jQuery('#wpadminbar').outerHeight();
		}

		if( jQuery( self.defaults.endFixed ).length > 0 ){
			self.defaults.fixBoundary = jQuery( self.defaults.endFixed ).offset().top + 10;
		}

		self.bindEvents();
		self.clones = self.createSpacer();
		self.onScrollHandler();
		self.animationHandler();
		self.linksHandler();
		window.addEventListener( 'resize', function() {
						self.onResizeHandler();
					}, false );
    },

	winHeight: function(){
		return parseInt( jQuery(window).height() );
	},

	docHeight: function(){
		return parseInt( jQuery(document).height() );
	},

	scrollY: function() {
		return window.pageYOffset || parseInt( jQuery(window).scrollTop() );
	},

	bindEvents: function() {
		var $container = jQuery( 'body' );

	},

	createSpacer: function( e ){
		var self = this,
			clones = {},
			getid, elem, prent, pos, h, w, m, d, rect;
		if( jQuery( '.widgetopts-fixed-this' ).length > 0 ){
			jQuery( '.widgetopts-fixed-this' ).each( function( i ){
				elem 	= jQuery(this);
				prent 	= elem.parent();

				if( prent.hasClass( 'widgetopts-fixed-this' ) ){
					return;
				}

				getid 	= elem.attr('id');
				pos 	= elem.position();
				h 		= elem.outerHeight();
				rect 	= elem[0].getBoundingClientRect();
				w 		= rect.width;
				m 		= parseInt( elem.css('margin-bottom') );
				d 		= prent.attr('data-sticky-spacer');
				
				pos.left = rect.left;
				pos.top = rect.left;

				if( pos.left < rect.left && elem.hasClass( 'elementor-element' ) ){
					pos.left = rect.left;
				}
				if( pos.top < rect.left && elem.hasClass( 'elementor-element' ) ){
					pos.top = rect.left;
				}
				// console.log( rect  );
				// console.log( pos  );

				if( typeof getid == 'undefined' ){
					getid = 'widgeopts-holder-' + i;
					elem.attr( 'id', getid );
				}

				prent.attr('data-sticky-height', 0);

				if( typeof d !== typeof undefined && d !== false ){
					prent.attr('data-sticky-spacer', parseInt(d) + parseInt( prent.attr('data-sticky-handler' )) );
				}else{
					prent.attr('data-sticky-spacer', 0 );
					prent.attr('data-sticky-handler', h + m );
				}

				clones[ getid ] = { 'top' : pos.top, 'left': pos.left, 'width' : w, 'height' : h, 'spacing' : parseInt( prent.attr('data-sticky-spacer') ), 'margin': m };

				jQuery( '<div>', {
						'id' 	: getid + '_spacer',
					    'class' : elem.attr('class') + ' widgetopts_spacer',
					    css 	: { 'display': 'none', 'height' : h }
					} ).insertBefore( '#' + getid );
				if( !prent.hasClass('widgetopts-sticky-parent') ){
					prent.addClass('widgetopts-sticky-parent');
				}
			} );
		}

		return clones;
	},

	onScrollHandler: function(){
		var self = this, t, st, m = 0, sp = 0, fh = 0, bott = 0, el, elem, spacer, prent, mb, ct = 0, fs = [], ft = 0, tt = 0, sfh = self.defaults.adminbar + self.defaults.margin_top;

		function sticky(){
			//sticky element onScroll
			if( !jQuery.isEmptyObject( self.clones ) && ( self.defaults.disable_width > 0 && window.innerWidth > self.defaults.disable_width )
		 		 && ( self.defaults.disable_height > 0 && window.innerHeight > self.defaults.disable_height )){
				jQuery.each( self.clones, function( k, clone ){
					el 		= '#' + k;
					fs[ct]	= k;
					elem 	= jQuery( el );
					prent 	= elem.parent();
					spacer 	= jQuery( el + '_spacer' );
					mb 		= parseInt( elem.css('margin-bottom') );
					//do offset fix
					if( typeof spacer.offset() != 'undefined' && spacer.offset().top != clone.top && !spacer.hasClass('widgetopts_scrolled')  ){
						m 			 = m + clone.margin;
						clone.top 	 = elem.offset().top;
						clone.left 	 = elem.offset().left;
						clone.height = elem.outerHeight();
						clone.width  = elem[0].getBoundingClientRect().width;
						spacer.addClass('widgetopts_scrolled');

						// console.log( clone.width );

						if( jQuery( self.defaults.endFixed ).length > 0 ){
							self.defaults.fixBoundary = jQuery( self.defaults.endFixed ).offset().top;
						}
					}

					sfh += clone.height + clone.margin;
					fh = parseInt( prent.attr('data-sticky-height') );
					// st = ( clone.top - clone.spacing ) - ( self.defaults.adminbar + self.defaults.margin_top);
					st = ( clone.top - ( clone.spacing ) ) - ( self.defaults.adminbar + self.defaults.margin_top );
					if( ct > 0 ){
						st = st - self.clones[ fs[ ct- 1 ] ].spacing;
					}
					// console.log( clone.spacing );
					if( self.scrollY() >= st ){
						if( !elem.hasClass('widgetopts_stuck') ){
							fh = fh + clone.height + self.defaults.margin_top;
							prent.attr('data-sticky-height', fh);
						}
						// if( ct > 0 ){
						// 	ft = clone.spacing + self.defaults.margin_top + self.defaults.adminbar;
						// 	ft = sfh - ( clone.height + clone.margin );
						// 	console.log( sfh );
						// }else{
						// 	ft = self.defaults.margin_top + self.defaults.adminbar;
						// 	// console.log( ft );
						// }

						ft = sfh - ( clone.height + clone.margin );

						elem.addClass('widgetopts_stuck').css({ 'position': 'fixed', 'z-index': 90, 'top': ft , 'left' : clone.left, 'width': clone.width });
						spacer.show().css({ 'visibility' : 'hidden' });
						ct++;
					}else if( self.scrollY() < st ){
						if( elem.hasClass('widgetopts_stuck') ){
							fh = fh - clone.height - self.defaults.margin_top;
							prent.attr('data-sticky-height', fh);
						}
						spacer.hide();
						elem.removeClass('widgetopts_stuck').css({ 'position' : 'relative' ,'top' : '', 'left': '' });
					}
					if( ( ( self.defaults.fixBoundary ) - fh ) <= ( ( self.scrollY() - clone.margin ) + self.defaults.adminbar + mb ) ){
						bott = self.docHeight() - self.defaults.fixBoundary - self.defaults.margin_top;
						sp 	 = self.docHeight() - ( self.scrollY() + fh + m ); // remaining bottom pace

						// if( k != fs[ 0 ] ){
						// 	tt = ( clone.spacing ) + ( sp-bott ) ;
						// }
						// else{
						// 	tt = ( clone.spacing ) + ( sp-bott );
						// }
						// console.log(  sp-bott );
						tt = ( sfh - ( clone.height + clone.margin ) ) + ( sp-bott );
						elem.css({ 'top' :  tt  });
					}

				});
				ct = 0;
				sfh = self.defaults.adminbar + self.defaults.margin_top;
			}
		}

		sticky();

		window.addEventListener( 'scroll', function(){
			//animation onScroll
			if( jQuery('.widgetopts-animate').length > 0 ){
				// console.log('yo!');
				self.animationHandler();
			}

			sticky();

		} );
	},

	onResizeHandler: function(){
		var self = this;

		function delayed() {
			if( ( self.defaults.disable_width > 0 && window.innerWidth < self.defaults.disable_width )
			 || ( self.defaults.disable_height > 0 && window.innerHeight < self.defaults.disable_height )){
				 self.resetFixed(); //reset all fixed widgets
			 }else{
				 self.resetPosition();
			 }
			self.resizeTimeout = null;
		}
		if ( this.resizeTimeout ) {
			clearTimeout( this.resizeTimeout );
		}
		this.resizeTimeout = setTimeout( delayed, 0 );
	},

	resetFixed: function( el ){
		jQuery( '.widgetopts_spacer' ).hide();
		if( el ){

		}else{
			el = jQuery( '.widgetopts-fixed-this' );
		}

		el.css({ 'position' : 'relative', 'top' : '', 'left' : '', 'width' : ''  }).removeClass('widgetopts_stuck');
	},

	resetPosition: function(){
		var getid, l, w, h, t, spacer, el, self = this;
		self = this;
		jQuery( '.widgetopts_spacer' ).remove();
		jQuery( '.widgetopts-sticky-parent' ).removeAttr('data-sticky-height data-sticky-spacer data-sticky-handler');
		self.resetFixed();
		self.clones = self.createSpacer();
		self.onScrollHandler();
	},

	animationHandler: function(){
		var self = this, el, ev, anim, speed, delay, of, elID;
		jQuery('.widgetopts-animate').each(function(i){
			el 		= jQuery(this);
			elID 	= jQuery(this).attr('id');
			ev 		= el.attr('data-animation-event');
			anim 	= el.attr('data-animation-type');
			speed 	= parseFloat( el.attr('data-animation-speed') ).toFixed(2);
			delay 	= parseFloat( el.attr('data-animation-delay') ).toFixed(2);
			of 		= parseInt( el.attr('data-animation-offset') );

			if( el.hasClass('widgetopts-SO') ){
				if( typeof SOWidgetOpts[ elID ] != "undefined"){
					if( typeof SOWidgetOpts[ elID ].animation != "undefined"){
						anim 	= SOWidgetOpts[ elID ].animation;
						ev 	 	= SOWidgetOpts[ elID ].event;
						speed 	= SOWidgetOpts[ elID ].speed;
						delay 	= SOWidgetOpts[ elID ].delay;
						of 		= SOWidgetOpts[ elID ].offset;
					}
				}
			}

			if( speed > 0 ){
				el.css({ 'animation-duration' : speed + 's', '-webkit-animation-duration' : speed + 's' });
			}
			if( delay > 0 ){
				el.css({ 'animation-delay' : delay + 's', '-webkit-animation-delay' : delay + 's' });
			}
			if( isNaN( of ) ) of = 0;
			switch (ev) {
				case 'onScreen':
					if( self.inViewport( el, 0.7, of ) && !el.hasClass('animated') ){
						el.addClass( 'animated '+ anim );
					}
					break;
				case 'enters':
					if( self.inViewport( el, 0, of ) && !el.hasClass('animated') ){
						el.addClass( 'animated '+ anim );
					}
					break;
				default:
					if( !el.hasClass('animated') ){
						el.addClass( 'animated '+ anim );
					}
					break;

			}
		});
	},

	linksHandler: function(){
		var self = this, el, elID, t, n;
		if( jQuery('.widgetopts-SO-linked').length > 0 ){
			jQuery('.widgetopts-SO-linked').each(function(){
				t = ''; n = '';
				el 		= jQuery(this);
				elID 	= jQuery(this).attr('id');
				if( typeof SOWidgetOptsURL[ elID ] != "undefined"){

					if( typeof SOWidgetOptsURL[ elID ].targets != "undefined" && '1' == SOWidgetOptsURL[ elID ].targets ){
						t = 'target="_blank"';
					}

					if( typeof SOWidgetOptsURL[ elID ].nofollow != "undefined" && '1' == SOWidgetOptsURL[ elID ].nofollow ){
						n = 'rel="nofollow"';
					}

					el.prepend( '<a href="'+ SOWidgetOptsURL[ elID ].url +'" '+ t +' '+ n +' class="widgetopts-custom-wlink"></a>' );
				}
			});
		}
	},

	inViewport: function( el, h, of ) {
		var self 		= this,
			scrolled 	= self.scrollY(),
			viewP 		= self.winHeight(),
			of 			= of || 0,
			elH 		= jQuery( el ).height(),
			viewed 		= scrolled + viewP,
			elTop 		= jQuery( el ).offset().top + of,
			elBottom 	= elTop + elH,
			h 			= h || 0;

		return ( elTop + elH * h ) <= viewed && ( elBottom - elH * h ) >= scrolled;
	}

}

jQuery(window).on("load", function () {
	widgetopts_fe.init()
});

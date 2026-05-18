const { __, _x, _n, _nx } = wp.i18n;

jQuery(
	function($){

		$( '#mycred-badge-setup' ).on( 'click', '#badges-add-new-level', function(e){

			var dataLevel = $('#badge-levels .badge-level:last').data( 'level' );
			var levelKey  = $('.mwp-badge-coupon-reward-item').length;
			var label     = $('#badge-levels .badge-level:last .label-field > input').attr('placeholder');

			$('.mwp-badge-coupon-reward-wrapper').append(`
				<div class="mwp-badge-coupon-reward-item" data-level="${dataLevel}">
					<div class="mwp-badge-coupon-reward-header">${label}</div>
					<div class="mwp-badge-coupon-reward-body">
						<div class="row">
						    <div class="col-lg-4 col-md-4 col-sm-12">
						        <div class="form-group">
						            <label for="discount-mycred-discount-type">${__('Discount Type', 'mycred-woocommerce-plus')}</label>
						            <select name="woo_discount[${levelKey}][discount_type]" id="woo-discount-${levelKey}-discount-type" class="mycred-ui-form form-control">
						            	<option value="fixed" selected="selected">Fixed Discount</option>
						            	<option value="percent">Percentage Discount</option>
						            </select>
						        </div>
						    </div>
						    <div class="col-lg-4 col-md-4 col-sm-12">
						        <div class="form-group">
						            <label for="discount-mycred-coupon-code-rank">${__('Coupon code', 'mycred-woocommerce-plus')}</label>
						            <input type="text" name="woo_discount[${levelKey}][mycred_coupon_code_badge]" id="woo-discount-${levelKey}-mycred-coupon-code-badge" class="mycred-ui-form form-control">
						        </div>
						    </div>
						    <div class="col-lg-4 col-md-4 col-sm-12">
						        <div class="form-group">
						            <label for="discount-mycred-discount-bagde-rank">${__('Amount', 'mycred-woocommerce-plus')}</label>
						            <input type="number" name="woo_discount[${levelKey}][discount_amount]" id="woo-discount-${levelKey}-discount_amount" class="mycred-ui-form form-control" min="0" value="0">
						        </div>
						    </div>
						</div>
					</div>
				</div>
			`);

		});

		$( '#mycred-badge-setup' ).on( 'keyup', '.badge-level .label-field > input', function(e){

			var levelKey = $( this ).closest('.badge-level').data( 'level' );
			var label    = $(this).val();
			var ele      = $('.mwp-badge-coupon-reward-item[data-level="' + levelKey + '"]').find('.mwp-badge-coupon-reward-header');

			if ( ! label ) {

				ele.html( $(this).attr('placeholder') );

			}
			else {
				
				ele.html( label );
			
			}


		});

		$( '#mycred-badge-setup' ).on( 'click', 'button.remove-badge-level', function(e){

			var leveltoremove = $( this ).data( 'level' );

			if ( ! $('.mwp-badge-coupon-reward-item[data-level="' + leveltoremove + '"]').length ) {
				return false;
			}

			$('.mwp-badge-coupon-reward-item[data-level="' + leveltoremove + '"]').slideUp().remove();

		});
		
	}
);

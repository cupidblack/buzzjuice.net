<script>
function Wo_CheckOutCard(type, description, amount, payment_type) {
		description = "Wallet replenishment";
		amount = $('#amount').val() * 100;

		if (payment_type == 'bitcoin') {
			$('.btn-bitcoin').attr('disabled', true).text("<?php echo $wo["lang"]["please_wait"]?>");
			$('#pay-go-pro').modal({
				show: false
			});
		} else if (payment_type == 'credit_card') {
			$('.btn-cart').attr('disabled', true);
		} else if (payment_type == 'alipay') {
			$('.btn-alipay').attr('disabled', true);
		}
		var img = 'star';
		if (type == 1) {
			img = 'star';
		} else if (type == 2) {
			img = 'hot';
		} else if (type == 3) {
			img = 'ultima';
		} else if (type == 4) {
			img = 'vip';
		}
		if (payment_type != 'bank_payment' && payment_type != 'checkout' && payment_type != 'bitcoin') {
			var stripe = Stripe('<?php echo $wo['config']['stripe_id'];?>');
			$.post(Wo_Ajax_Requests_File() + '?f=stripe&s=session', {amount: $('#amount').val(),type:'wallet',payment_type:payment_type}, function(data, textStatus, xhr) {
				if (data.status == 200) {
					return stripe.redirectToCheckout({ sessionId: data.sessionId });
				}
				else{
			    	$('.pay-go-pro-alert').html("<div class='alert alert-danger'>"+data.message+"</div>");
					setTimeout(function () {
						$('.pay-go-pro-alert').html("");
					},3000);
			    }
			    if (payment_type == 'credit_card') {
			    	$('.btn-cart').removeAttr('disabled');
			    }
			    if (payment_type == 'alipay') {
				    $('.btn-alipay').removeAttr('disabled');
				}
			});
		}
		if (payment_type == 'bitcoin') {
			if( $('#amount').val() <= 0 ){
				$('#pay-go-pro').modal('hide');
				alert('You must enter value greater than ZERO');
				return false;
			}else{
				$.get(Wo_Ajax_Requests_File() + '?f=pay_with_bitcoin&s=pay&amount=' + $('#amount').val(), function (data) {
					if (data.status == 200) {
						$(data.html).appendTo('body').submit();
					}
					else{
			        	$('.pay-go-pro-alert').html("<div class='alert alert-danger'>"+data.message+"</div>");
						setTimeout(function () {
							$('.pay-go-pro-alert').html("");
						},3000);
			        }
				});
			}

		} else if (payment_type == 'bank_payment') {
	    	$('#configreset').click();
	    	$(".prv-img").html('<div class="thumbnail-rendderer"><div><h4 class="bold"><?php echo $wo['lang']['drop_img_here']; ?></h4><div class="error-text-renderer"></div><div><span><?php echo $wo['lang']['or']; ?></span><p><?php echo $wo['lang']['browse_to_upload']; ?></p></div></div> </div>');
			$("#blog-alert").html('');
	    	$('#bank_transfer_des').val('Add to balance');
	    	$('#bank_transfer_price').val($('#amount').val());
	    	$('#pay-go-pro').modal('hide');
			$('#pay_modal_wallet').modal('hide');
	    	$('#bank_transfer_modal').modal({
	             show: true
	            });


		} else if (payment_type == 'checkout') {
			$("#2checkout_alert_wallet").html('');
			$('#pay-go-pro').modal('hide');
			$('#pay_modal_wallet').modal('hide');
	    	$('#2checkout_wallet_modal').modal({
	            show: true
	        });
		}
		$(window).on('popstate', function() {
		handler.close();
		});
	}
</script>
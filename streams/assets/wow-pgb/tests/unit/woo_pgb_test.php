<?php
use PHPUnit\Framework\TestCase;

class WooPgbTest extends TestCase {
    public function testCreateVirtualProduct() {
        // Assume create_virtual_product() and other necessary functions are defined elsewhere
        create_virtual_product();
        $product_id = get_option('wo_wonder_digital_product_id');
        $this->assertNotEmpty($product_id, "Product ID should not be empty");
    }

    public function testInjectDynamicPriceAndTitle() {
        // Mock WooCommerce cart object
        $cart = new stdClass();
        $cart->cart_contents = [
            [
                'product_id' => get_option('wo_wonder_digital_product_id'),
                'woo_dynamic_price' => 100.0,
                'woo_product_name' => 'Test Product'
            ]
        ];
        inject_dynamic_price_and_title($cart);
        foreach ($cart->cart_contents as $cart_item) {
            $this->assertEquals(100.0, $cart_item['data']->get_price(), "Price should be 100.0");
            $this->assertEquals('Test Product', $cart_item['data']->get_name(), "Product name should be 'Test Product'");
        }
    }

    public function testWebhookHandler() {
        // Mock webhook data
        $input = json_encode([
            'event_type' => 'order.updated',
            'data' => [
                'id' => '12345',
                'status' => 'completed',
                'transaction_id' => '67890'
            ]
        ]);
        $secret = 'test_secret';
        $signature = hash_hmac('sha256', $input, $secret);

        $_SERVER['HTTP_X_WC_WEBHOOK_SIGNATURE'] = $signature;
        $_POST = $input;

        ob_start();
        require 'assets/woo-pgb/woo-pgb_webhook.php';
        $output = ob_get_clean();

        $this->assertStringContainsString('Payment completed', $output, "Webhook handler should return 'Payment completed'");
    }
}
?>
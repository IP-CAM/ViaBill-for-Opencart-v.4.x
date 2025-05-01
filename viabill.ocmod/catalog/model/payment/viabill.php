<?php
/**
 * Catalog Model - ViaBill Payments
 * Handles payment method display and processing in the storefront
 */
namespace Opencart\Catalog\Model\Extension\Viabill\Payment;

class Viabill extends \Opencart\System\Engine\Model {
    /**
     * Determine if this payment method should be available
     *
     * @param array $address Customer's address information
     * @return array Payment method details if available, empty array otherwise
     */
    public function getMethods(array $address = []): array {
        // Load language file for text strings
        $this->load->language('extension/viabill/payment/viabill');

        // Check if module is enabled
        if (!$this->config->get('payment_viabill_status')) {
            return [];
        }

        // Check if cart has subscription products (if you don't support subscriptions)
        if (method_exists($this->cart, 'hasSubscription') && $this->cart->hasSubscription()) {
            return [];
        }

        // Get the configured geo zone
        $geo_zone_id = $this->config->get('payment_viabill_geo_zone_id');
        $status = true;

        // Check if geo zone is set and address is provided
        /*
        if ($geo_zone_id && !empty($address)) {
            $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "zone_to_geo_zone WHERE geo_zone_id = '" . (int)$geo_zone_id . "' AND country_id = '" . (int)$address['country_id'] . "' AND (zone_id = '" . (int)$address['zone_id'] . "' OR zone_id = '0')");

            if (!$query->num_rows) {
                $status = false;
            }
        }
        */

        // Define method data
        $method_data = [];

        if ($status) {
            $method_data = [
                'code'       => 'viabill',
                'name'      => $this->language->get('text_title'),
                'terms'      => '',
                'test_mode_warning' => 'Test mode is enabled. This payment will be processed in the ViaBill sandbox environment.',
                'sort_order' => $this->config->get('payment_viabill_sort_order'),
                'option'     => [
                    'viabill_bnpl' => [
                        'code'  => 'viabill.viabill_bnpl',
                        'name'  => $this->language->get('text_bnpl_title')
                    ],
                    'viabill_tbyb' => [
                        'code'  => 'viabill.viabill_tbyb',
                        'name'  => $this->language->get('text_tbyb_title')
                    ]
                ]
            ];
            
        }

        return $method_data;
    }

    /**
     * Get ViaBill transaction details for an order
     *
     * @param int $order_id The order ID
     * @return array Transaction details
     */
    public function getTransaction(int $order_id): array {
        $query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "viabill_transaction` WHERE `order_id` = '" . (int)$order_id . "' LIMIT 1");

        if ($query->num_rows) {
            return $query->row;
        } else {
            return [];
        }
    }    

    /**
     * Update transaction amounts for an order
     *
     * @param int $order_id The order ID
     * @param float $captured_amount The total captured amount
     * @param float $refunded_amount The total refunded amount
     * @return void
     */
    public function updateTransactionAmounts(int $order_id, float $captured_amount, float $refunded_amount): void {
        $this->db->query("UPDATE `" . DB_PREFIX . "viabill_transaction` SET 
            `captured_amount` = '" . (float)$captured_amount . "', 
            `refunded_amount` = '" . (float)$refunded_amount . "', 
            `date_modified` = NOW() 
            WHERE `order_id` = '" . (int)$order_id . "'");
    }
}

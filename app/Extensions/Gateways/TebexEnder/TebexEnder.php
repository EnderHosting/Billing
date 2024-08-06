<?php

namespace App\Extensions\Gateways\TebexEnder;

use App\Classes\Extensions\Gateway;

class TebexEnder extends Gateway
{
    private $config;

    /**
    * Get the extension metadata
    * 
    * @return array
    */
    public function getMetadata()
    {
        return [
            'display_name' => 'TebexEnder',
            'version' => '1.0.0',
            'author' => 'EnderHosting',
            'website' => 'https://enderhosting.com.mx',
        ];
    }

    /**
     * Constructor
     * 
     * @param array $config
     */
    public function __construct($config)
    {
        $this->config = $config;
    }

    /**
     * Get all the configuration for the extension
     * 
     * @return array
     */
    public function getConfig()
    {
        return [
            [
                'name' => 'tebex_api_key',
                'friendlyName' => 'Tebex API Key (Private Key)',
                'type' => 'text',
                'description' => 'Your Tebex API Key (Private Key)',
                'required' => true,
            ],
            [
                'name' => 'tebex_secret_key',
                'friendlyName' => 'Tebex Secret Key (Private Key)',
                'type' => 'text',
                'description' => 'Your Tebex Secret Key (Private Key)',
                'required' => true,
            ],
            [
                'name' => 'tebex_test_mode',
                'friendlyName' => 'Tebex Test Mode',
                'type' => 'boolean',
                'description' => 'Enable or disable Tebex test mode',
                'required' => false,
            ],
            [
                'name' => 'tebex_test_key',
                'friendlyName' => 'Tebex Test Key',
                'type' => 'text',
                'description' => 'Your Tebex Test Key (Use only for testing)',
                'required' => false,
            ],
        ];
    }
    
    /**
     * Get the URL to redirect to
     * 
     * @param int $total
     * @param array $products
     * @param int $invoiceId
     * @return string
     */
    public function pay($total, $products, $invoiceId)
    {
        $url = $this->config['tebex_test_mode'] ? "https://plugin.tebex.io/checkout/test" : "https://plugin.tebex.io/checkout";
        
        $data = [
            'price' => $total,
            'currency' => 'USD', // o la moneda que prefieras
            'customer' => [
                'name' => 'Customer Name', // Obtén los datos reales del cliente
                'email' => 'customer@example.com', // Obtén el correo real del cliente
            ],
            'products' => $products,
            'invoice_id' => $invoiceId
        ];

        $options = [
            'http' => [
                'header'  => "Content-type: application/json\r\n" .
                             "X-Tebex-Secret: " . $this->config['tebex_api_key'] . "\r\n",
                'method'  => 'POST',
                'content' => json_encode($data),
            ],
        ];

        $context  = stream_context_create($options);
        $result = @file_get_contents($url, false, $context);

        if ($result === FALSE) {
            // Handle error
            $error = error_get_last();
            error_log('Error fetching data from Tebex API: ' . print_r($error, true));
            return 'Error: Unable to process payment. Please try again later.';
        }

        $response = json_decode($result, true);

        // Log the response for debugging
        error_log('Tebex API response: ' . print_r($response, true));

        // Check if the URL is valid
        if (!isset($response['url']) || empty($response['url'])) {
            error_log('Invalid URL in Tebex API response: ' . print_r($response, true));
            return 'Error: Invalid response from payment gateway.';
        }

        $redirectUrl = $response['url'];

        // Validate the URL format
        if (filter_var($redirectUrl, FILTER_VALIDATE_URL) === false) {
            error_log('Invalid URL format: ' . $redirectUrl);
            return 'Error: Invalid response from payment gateway.';
        }

        return $redirectUrl;
    }
}

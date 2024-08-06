<?php

namespace App\Extensions\Servers\VoIPSell;

use App\Classes\Extensions\Server;
use Vonage\Numbers\Filter\AvailableNumbers;
use Vonage\Entity\IterableAPICollection;
use App\Helpers\ExtensionHelper;
use App\Models\Product;

class VoIPSell extends Server
{

    /**
    * Get the extension metadata
    * 
    * @return array
    */
    public function getMetadata()
    {
        return [
            'display_name' => 'VoIPSell',
            'version' => '1.0.0',
            'author' => 'Geoorloofd',
            'website' => 'https://paymenter.org',
        ];
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
                'name' => 'key',
                'friendlyName' => 'Vonage/Nexmo API Key',
                'type' => 'text',
                'required' => true,
            ],
            [
                'name' => 'secret',
                'friendlyName' => 'Vonage/Nexmo API Secret',
                'type' => 'text',
                'required' => true,
            ],
        ];
    }
	    private function config($key): ?string
    {
        $config = ExtensionHelper::getConfig('VoIPSell', $key);
        if ($config) {
            return $config;
        }

        return null;
    }

    /**
     * Get product config
     * 
     * @param array $options
     * @return array
     */
	 

    public function getProductConfig($options)
    {
        return [
            [
                'name' => 'countrycode',
                'friendlyName' => 'Country Code (NL, BE, US, ...)',
                'type' => 'text',
                'required' => true,
            ],
			[
                'name' => 'type',
                'friendlyName' => 'Mobile, Landline or Toll-Free',
                'type' => 'dropdown',
                'options' => [
                    [
                        'name' => 'Mobile',
                        'value' => 'mobile-lvn'
                    ],
					[
                        'name' => 'Landline',
                        'value' => 'landline'
                    ],
					[
                        'name' => 'Toll-Free',
                        'value' => 'landline-toll-free'
                    ]
                ],
                'required' => true,
            ],
			[
                'name' => 'features',
                'friendlyName' => 'Features the number must have!',
                'type' => 'dropdown',
                'options' => [
                    [
                        'name' => 'SMS',
                        'value' => 'SMS'
                    ],
					[
                        'name' => 'Voice',
                        'value' => 'VOICE'
                    ],
					[
                        'name' => 'Both',
                        'value' => 'SMS,VOICE'
                    ]
                ],
                'required' => true,
            ],
			[
                'name' => 'prefix',
                'friendlyName' => 'Prefix (Without +)',
				'description' => 'Example for Belgium: 32. Example for The Netherlands: 31',
                'type' => 'text',
                'required' => true,
            ],
        ];    
		}
    public function getUserConfig(Product $product)
    {
		$currentConfig = $product->settings;
		$basic = new \Vonage\Client\Credentials\Basic($this->config('key'), $this->config('secret'));
		$client = new \Vonage\Client($basic);
		        $numberList = [];
		 /** @var IterableAPICollection $response */
		$filter = new AvailableNumbers([
			"pattern" => (string) $product->settings()->get()->where('name', 'prefix')->first()->value,
			"search_pattern" => (int) 1,
			"type" => $product->settings()->get()->where('name', 'type')->first()->value,
			"features" => $product->settings()->get()->where('name', 'features')->first()->value,
				]);
			$response = $client->numbers()->searchAvailable($product->settings()->get()->where('name', 'countrycode')->first()->value, $filter);

			foreach ($response as $number) {
            $numberList[] = [
                'name' => $number->getMsisdn(),
                'value' => $number->getMsisdn(),
            ];
			} 
        return [
            [
                'name' => 'number',
                'friendlyName' => 'Phone Number',
                'type' => 'dropdown',
                'options' => $numberList,
                'required' => true,
				],
        ];    
		}
		

    /**
     * Create a server
     * 
     * @param User $user
     * @param array $params
     * @param Order $order
     * @param OrderProduct $orderProduct
     * @param array $configurableOptions
     * @return bool
     */
    public function createServer($user, $params, $order, $orderProduct, $configurableOptions)
    {
		$basic = new \Vonage\Client\Credentials\Basic($this->config('key'), $this->config('secret'));
		$client = new \Vonage\Client($basic);
		try {
			$client->numbers()->purchase($params['config']['number'], $params['countrycode']);
			} catch (Exception $e) {
			echo "Error purchasing number";
			}

		}

    /**
     * Suspend a server
     * 
     * @param User $user
     * @param array $params
     * @param Order $order
     * @param OrderProduct $orderProduct
     * @param array $configurableOptions
     * @return bool
     */
    public function suspendServer($user, $params, $order, $orderProduct, $configurableOptions)
    {
        return false;
    }

    /**
     * Unsuspend a server
     * 
     * @param User $user
     * @param array $params
     * @param Order $order
     * @param OrderProduct $orderProduct
     * @param array $configurableOptions
     * @return bool
     */
    public function unsuspendServer($user, $params, $order, $orderProduct, $configurableOptions)
    {
        return false;
    }

    /**
     * Terminate a server
     * 
     * @param User $user
     * @param array $params
     * @param Order $order
     * @param OrderProduct $orderProduct
     * @param array $configurableOptions
     * @return bool
     */
    public function terminateServer($user, $params, $order, $orderProduct, $configurableOptions)
    {
		$basic = new \Vonage\Client\Credentials\Basic($this->config('key'), $this->config('secret'));
		$client = new \Vonage\Client($basic);
		try {
			$client->numbers()->cancel($params['config']['number']);
			} catch (Exception $e) {
			echo "Error purchasing number";
			}
			}
			    public function getCustomPages($user, $params, $order, $product, $configurableOptions)
    {
		$userConfig = $params['config'];
		        return [
				 'name' => 'VoIPSell',
				 'data' => [
					'userConfig' => $userConfig,
				 ],
				 'template' => 'voipsell::sip-details'

            
 ];
	}
}
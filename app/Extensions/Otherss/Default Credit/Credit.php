<?php

namespace App\Extensions\Events\Credit;

use App\Classes\Extensions\Event;



class Credit extends Event
{   
    /**
    * Get the extension metadata
    * 
    * @return array
    */
    public function getMetadata()
    {
        return [
            'display_name' => 'Credit',
            'version' => '1.0.0',
            'author' => 'at0mweb',
            'website' => 'https://github.com/at0mweb',
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
                'name' => 'start_credit',
                'type' => 'number',
                'friendlyName' => 'Start Credit',
                'required' => true,
            ]
        ];
    }



}

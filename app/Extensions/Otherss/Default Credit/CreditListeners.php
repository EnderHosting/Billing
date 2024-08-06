<?php

namespace App\Extensions\Events\Credit;

use App\Events\User\UserCreated;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Helpers\ExtensionHelper;
use Illuminate\Support\Facades\DB;

class CreditListeners
{

    public function set_balance(UserCreated $event): void
    {
            $user = $event->user;
            $user_id = $user->id;
            
            $credits = ExtensionHelper::getConfig('Credit', 'start_credit');
    
            $update = DB::table('users')->where('id', $user_id)->increment('credits', $credits);
    
       
    }

    public function subscribe(): array
    {
        return [
             UserCreated::class => 'set_balance',
        ];
    }


}

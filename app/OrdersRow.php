<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class OrdersRow extends Model
{
    public function campaignsReference()
    {
        return $this->hasOne('App\CampaignsReference', 'id', 'camp_ref_id');
    }

    public function clients()
    {
        return $this->hasOne('App\Client');
    }
}

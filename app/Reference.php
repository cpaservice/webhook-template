<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Reference extends Model
{
    public function campaignsReferences()
    {
        return $this->hasMany('App\CampaignsReference');
    }
}

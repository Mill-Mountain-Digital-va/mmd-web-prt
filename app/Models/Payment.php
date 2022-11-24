<?php

namespace App\Models;

use App\Models\Course;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\File;

class Payment extends Model
{
    use SoftDeletes;

    /**
     * Perform any actions required after the model boots.
     *
     * @return void
     */
    protected static function booted()
    {
        
    }

    protected $guarded = [];

    public function subscriptions(){
        return $this->hasMany(Subscription::class);
    }


}

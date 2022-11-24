<?php

namespace App\Models;

use App\Models\Payment;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\File;

class Subscription extends Model
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

    public function payments(){
        return $this->hasMany(Payment::class);
    }


}

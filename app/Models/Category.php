<?php

namespace App\Models;

use App\Models\Course;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\File;

class Category extends Model
{
    use SoftDeletes;

    
    protected $appends = ['image_url'];

    /**
     * Perform any actions required after the model boots.
     *
     * @return void
     */
    protected static function booted()
    {
        static::deleting(function ($category) { // before delete() method call this
            if ($category->isForceDeleting()) {
                if (File::exists(public_path('/storage/uploads/' . $category->image))) {
                    File::delete(public_path('/storage/uploads/' . $category->image));
                    File::delete(public_path('/storage/uploads/thumb/' . $category->image));
                }
            }
        });

        Static::deleted(function($category){
            if($category->courses->count()){
                $category->courses()->delete();
            }
            if($category->bundles->count()){
                $category->bundles()->delete();
            }
            if($category->blogs->count()){
                $category->blogs()->delete();
            }
            if($category->faqs->count()){
                $category->faqs()->delete();
            }
        });
    }

    protected $guarded = [];

    public function getImageUrlAttribute()
    {
        if ($this->image != null) {
            return url('storage/uploads/'.$this->image);
        }
        return NULL;
    }

    public function courses(){
        return $this->hasMany(Course::class);
    }

    public function bundles(){
        return $this->hasMany(Bundle::class);
    }

    public function blogs(){
        return $this->hasMany(Blog::class);
    }

    public function faqs(){
        return $this->hasMany(Faq::class);
    }


}

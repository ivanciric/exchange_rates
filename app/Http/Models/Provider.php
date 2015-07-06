<?php
/**
 * Created by PhpStorm.
 * User: ivan
 * Date: 4/17/15
 * Time: 1:10 PM
 */

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;

class Provider extends Model{

    protected $table = 'providers';

    protected $guarded = ['id'];

    public function resource()
    {
        return $this->belongsTo('Resource', 'resource_id');
    }

    public function rates()
    {
        return $this->hasMany('Rate', 'provider_id');
    }

}
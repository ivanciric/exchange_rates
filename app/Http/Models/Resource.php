<?php
/**
 * Created by PhpStorm.
 * User: ivan
 * Date: 4/17/15
 * Time: 1:10 PM
 */

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;

class Resource extends Model{

    protected $table = 'resources';

    protected $guarded = ['id'];

    public function providers()
    {
        return $this->hasMany('Provider', 'resource_id');
    }

}
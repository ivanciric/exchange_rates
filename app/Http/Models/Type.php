<?php
/**
 * Created by PhpStorm.
 * User: ivan
 * Date: 4/17/15
 * Time: 1:10 PM
 */

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;

class Type extends Model{

    protected $table = 'types';

    protected $guarded = ['id'];

    public function rates()
    {
        return $this->hasMany('Rate', 'type_id');
    }

}
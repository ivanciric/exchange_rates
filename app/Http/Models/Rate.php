<?php
/**
 * Created by PhpStorm.
 * User: ivan
 * Date: 4/17/15
 * Time: 1:10 PM
 */

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;

class Rate extends Model{

    protected $table = 'rates';

    protected $guarded = ['id'];

    public function provider()
    {
        return $this->belongsTo('Provider', 'provider_id');
    }

    public function type()
    {
        return $this->belongsTo('Type', 'type_id');
    }

    public function pair()
    {
        return $this->belongsTo('Pair', 'pair_id');
    }


}
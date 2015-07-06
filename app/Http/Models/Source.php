<?php
/**
 * Created by PhpStorm.
 * User: ivan
 * Date: 4/17/15
 * Time: 1:10 PM
 */

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;
use App\Http\Interfaces\SourceState;

class Source extends Model implements SourceState{

    public static function getSourceType($res, $pair_from){

        $type = self::checkDataSource($res, $pair_from);

        if($type){

            return $type;
        }

        exit('data error.');

    }

    public static function determineResource($provider_code){

        $res = false;

        if ($provider_code != 'NARBAN' && $provider_code != 'GOOGLE') {

            $res = 'kamatica';

        }elseif($provider_code == 'GOOGLE'){

            $res = 'google';

        }elseif($provider_code == 'NARBAN'){

            $res = 'narban';
        }

        return $res;
    }

    public static function checkDataSource($res, $pair_from){

        $source_type = false;

        if ($res == 'kamatica' && $pair_from == 'RSD') {

            $source_type = 'kamatica';

        }elseif ($res == 'google' && $pair_from != 'RSD') {

            $source_type = 'google';

        }elseif($res == 'narban' && $pair_from == 'RSD'){

            $source_type = 'narban';
        }

        return $source_type;

    }

}
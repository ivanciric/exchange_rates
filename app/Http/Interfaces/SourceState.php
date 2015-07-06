<?php
/**
 * Created by PhpStorm.
 * User: ivan
 * Date: 7/6/15
 * Time: 11:11 AM
 */

namespace App\Http\Interfaces;


interface SourceState {

    public static function checkDataSource($res, $pair_from);

}
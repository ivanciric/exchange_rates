<?php
/**
 * Created by PhpStorm.
 * User: Ivan Ciric
 * Date: 4/17/15
 * Time: 1:10 PM
 */

namespace App\Http\Models;

use Illuminate\Support\Facades\App;
use Illuminate\Database\Eloquent\Model;
use League\Flysystem\Exception;

class Worker
{

    private static $resources = [
        'kamatica' => "http://www.kamatica.com/kursna-lista/",
        'nbs' => 'http://www.nbs.rs/internet/cirilica/scripts/ondate.html',
    ];

    public static function obtain_data()
    {

        $all_providers = Provider::all();

        foreach ($all_providers as $provider) {

            $res = Source::determineResource($provider->code);

            $base = self::$resources['kamatica']; // to be continued...

            $all_pairs = Pair::all();

            foreach ($all_pairs as $pair) {

                self::get_pair_rate($res, $base, $pair, $provider);
            }
        }
    }

    public static function return_data($from, $to, $type, $provider_code, $date)
    {

        if(!$date){
            $date = date('Y-m-d');
        }

        $provider = Provider::where('code', '=', $provider_code)->first();

        if(!$provider){
            exit('Provider not found...');
        }

        $pair = Pair::where('from', '=', $from)
            ->where('to', '=', $to)
            ->first();

        if(!$pair){
            exit('Pair not found...');
        }

        $rate_type = Type::where('name', '=', $type)->first();

        if(!$rate_type){
            exit('Rate type not found...');
        }

        $rate = Rate::where('provider_id', '=', $provider->id)
            ->where('type_id', '=', $rate_type->id)
            ->where('pair_id', '=', $pair->id)
            ->where('date', '=', $date)
            ->first();


        if($rate){

            exit($rate->rate);

        }else{

            foreach(range(1,999) as $minus_days){

                // try previous days until success
                $rate = Rate::where('provider_id', '=', $provider->id)
                    ->where('type_id', '=', $rate_type->id)
                    ->where('pair_id', '=', $pair->id)
                    ->where('date', '=', date('Y-m-d', strtotime($date." -$minus_days days")))
                    ->first();

                if($rate){
                    exit($rate->rate);
                }
            }

            // if something is really wrong and nothing is found in previous 999 days...
            exit('no data');
        }
    }

    public static function get_pair_rate($res, $base = false, $pair = false, $provider = false)
    {

        $source_type = Source::getSourceType($res, $pair->from);

        if ($source_type = 'kamatica') {

            $url = "http://www.kamatica.com/scripts/menjac-kurs-data.php?menjac={$provider->alias}&sEcho=1&iColumns=6&sColumns=&iDisplayStart=0&iDisplayLength=-1&mDataProp_0=0&mDataProp_1=1&mDataProp_2=2&mDataProp_3=3&mDataProp_4=4&mDataProp_5=5&iSortingCols=1&iSortCol_0=0&sSortDir_0=asc&bSortable_0=false&bSortable_1=false&bSortable_2=false&bSortable_3=false&bSortable_4=false&bSortable_5=false";

            $data = json_decode(file_get_contents($url));

            if ($data && isset($data->aaData)) {

                self::handle_remote_data($res, $source_type, $pair, $provider, $data);
            }

        } elseif ($source_type = 'google') {

            $provider = Provider::where('code', '=', 'GOOGLE')->first();

            $url = "https://www.google.com/finance/converter?a=1&from={$pair->to}&to={$pair->from}";

            $data = file_get_contents($url);

            $dom = new \Yangqi\Htmldom\Htmldom($data);

            $divs = $dom->find('div[id=currency_converter_result]');

            foreach ($divs as $d) {

                $span = $d->find('span', 0);

                $exchange_data = trim(substr(trim($span->plaintext), 0, -3));

                self::handle_remote_data($res, $source_type, $pair, $provider, $exchange_data);

            }

        }elseif($source_type = 'narban'){

            $date = date('d.m.Y.');
            $year = date('Y');

            $provider = Provider::where('code', '=', 'NARBAN')->first();

            $url = "http://www.nbs.rs/kursnaListaModul/efektivniStraniNovac.faces?date=$date&listno=&year=$year&listtype=1&lang=sr";

            $data = file_get_contents($url);

            $dom = new \Yangqi\Htmldom\Htmldom($data);

            $tables = $dom->find('table');

            $exchange_data = [
                'buy' => false,
                'sell' => false,
                'mid' => false,
            ];

            foreach($tables[1]->find('tr') as $tr){

                $tds = $tr->find('td');
                foreach($tds as $td){

                    if($td->plaintext == $pair->to){

                        $exchange_data['buy'] = floatval($tds[4]->plaintext);
                        $exchange_data['sell'] = floatval($tds[5]->plaintext);
                        $exchange_data['mid'] = round(($exchange_data['buy'] + $exchange_data['sell']) / 2, 4);
                    }
                }
            }

            self::handle_remote_data($res, $source_type, $pair, $provider, $exchange_data);
        }
    }

    public static function handle_remote_data($res, $source_type, $pair, $provider, $data)
    {

        if ($res == 'kamatica' && $source_type == 'kamatica') {

            foreach ($data->aaData as $currency_data) {

                if ($currency_data[4] == $pair->to) {

                    $curr_types = [
                        'buy' => $currency_data[6],
                        'mid' => $currency_data[7],
                        'sell' => $currency_data[8],
                    ];

                    $all_types = Type::all();

                    foreach ($all_types as $type) {

                        self::checkAndCreate($provider->id, $type->id, $pair->id, $curr_types[$type->name]);
                    }
                }
            }

        } elseif ($res == 'google' && $source_type == 'google') {

            $curr_types = [
                'buy' => null,
                'mid' => $data,
                'sell' => null,
            ];

            $all_types = Type::all();

            foreach ($all_types as $type) {

                if ($type->name == 'mid') {

                    self::checkAndCreate($provider->id, $type->id, $pair->id, $curr_types[$type->name]);
                }
            }

        }elseif ($res == 'narban' && $source_type == 'narban') {

            $curr_types = [
                'buy' => $data['buy'],
                'mid' => $data['mid'],
                'sell' => $data['sell'],
            ];

            $all_types = Type::all();

            foreach ($all_types as $type) {

                self::checkAndCreate($provider->id, $type->id, $pair->id, $curr_types[$type->name]);
            }
        }
    }

    private static function checkAndCreate($provider_id, $type_id, $pair_id, $rate){

        $rate_data = [
            'provider_id' => $provider_id,
            'type_id' => $type_id,
            'pair_id' => $pair_id,
            'rate' => $rate,
            'date' => date('Y-m-d'),
        ];

        $check = Rate::where('provider_id', '=', $provider_id)
            ->where('type_id', '=', $type_id)
            ->where('pair_id', '=', $pair_id)
            ->where('date', '=', date('Y-m-d'))
            ->first();

        if (!$check) {
            Rate::create($rate_data);
        }
    }
}
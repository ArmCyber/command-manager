<?php

namespace Zakhayko\CommandManager\Models;

use Illuminate\Database\Eloquent\Model;

class DoneCommand extends Model
{
    protected $dates = [
        'done_at'
    ];

    public static function getFromKeys(array $keys){
        return self::whereIn('key', $keys)->pluck('key')->toArray();
    }

    public static function getBatch() {
        $max = (int) self::max('batch');
        return $max+1;
    }

    public static function insertDoneCommands($data){
        self::insert($data);
    }
}

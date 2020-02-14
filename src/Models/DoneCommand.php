<?php

namespace Zakhayko\CommandManager\Models;

use Illuminate\Database\Eloquent\Model;

class DoneCommand extends Model
{
    protected $dates = [
        'done_at'
    ];

    public static function getFromKeys(array $keys){
        return self::whereIn('key', $keys)->pluck('id')->toArray();
    }
}

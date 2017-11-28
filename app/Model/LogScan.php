<?php

namespace App\Model;


class LogScan extends Model
{
    static protected $table = 'log‐scan';
    static protected $id_field = 'id';
    static protected $fields = [
        'id',
        'account',
        'datetime',
        'followers'
    ];
}
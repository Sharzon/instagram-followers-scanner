<?php

namespace App\Model;


class Action extends Model
{
    static protected $table = 'action';
    static protected $id_field = 'id';
    static protected $fields = [
        'id',
        'datetime',
        'post_id',
        'followers',
        'other_users'
    ];
}
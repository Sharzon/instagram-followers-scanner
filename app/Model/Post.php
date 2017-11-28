<?php

namespace App\Model;


use App\DB;

class Post extends Model
{
    static protected $table = 'posts';
    static protected $id_field = 'id';
    static protected $fields = [
        'id',
        'post_id',
        'account',
        'actions'
    ];

    static public function checkIfExistsWithPostId($post_id)
    {
        $pdo = DB::getPDO();

        $query = 'SELECT COUNT(*) FROM `'.static::$table.'` WHERE post_id = ?';

        $stmt = $pdo->prepare($query);
        $stmt->execute([ $post_id ]);

        return $stmt->fetchColumn();
    }
}
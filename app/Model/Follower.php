<?php

namespace App\Model;


use App\DB;

class Follower extends Model
{
    static protected $table = 'followers';
    static protected $id_field = ['id', 'account'];
    static protected $fields = [
        'id',
        'account',
        'date_in',
        'date_out',
        'active'
    ];

    static public function getByAccount($account, $active = null)
    {
        $query = "SELECT * FROM ".self::$table." WHERE account = :account";

        $input_parameters = ['account' => $account];
        if ($active != null) {
            $query .= " AND active = :active";
            $input_parameters['active'] = $active;
        }

        $pdo = DB::getPDO();
        $stmt = $pdo->prepare($query);
        $stmt->execute($input_parameters);

        $followers = [];

        while ($raw_follower = $stmt->fetch()) {
            $follower = new self;

            foreach ($raw_follower as $field => $value) {
                $follower[$field] = $value;
            }

            $followers[] = $follower;
        }

        return $followers;
    }

    static public function getFollowersCount($account)
    {
        $query = "SELECT COUNT(*) FROM ".self::$table." WHERE account = ? AND active = 1";
        $pdo = DB::getPDO();
        $stmt = $pdo->prepare($query);
        $stmt->execute([ $account ]);

        return $stmt->fetchColumn();
    }
}
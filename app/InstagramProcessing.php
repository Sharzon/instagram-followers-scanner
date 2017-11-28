<?php
/**
 * Created by PhpStorm.
 * User: sharzon
 * Date: 26.11.17
 * Time: 15:16
 */

namespace App;

use App\Model\Action;
use App\Model\Follower;
use App\Model\LogScan;
use App\Model\Post;
use InstagramScraper\Instagram;
use Carbon\Carbon;

class InstagramProcessing
{
    const USERNAME = 'instagram_username';
    const PASSWORD = 'instagram_password';

    static $instagram;

    static public function getInstagramObject()
    {
        if (self::$instagram) {
            return self::$instagram;
        }

        $instagram = Instagram::withCredentials(
            self::USERNAME,
            self::PASSWORD,
            getcwd().'/cache'
        );
        $instagram->login();
        sleep(2);

        self::$instagram = $instagram;

        return $instagram;
    }

    static public function getRawFollowers($account_name)
    {
        $instagram = self::getInstagramObject();

        $account = $instagram->getAccount($account_name);
        sleep(1);

        $followers_count = $account->getFollowedByCount();
        $followers = $instagram->getFollowers(
            $account->getId(),
            $followers_count,
            $followers_count >= 500 ? 500 : $followers_count,
            true
        );

        return $followers;
    }

    static public function getRawImagePosts($account_name)
    {
        $instagram = self::getInstagramObject();

        $posts = [];
        $has_next_page = true;
        $max_id = '';

        while ($has_next_page && count($posts) < 30) {
            $pagination_array = $instagram->getPaginateMedias($account_name, $max_id);

            foreach ($pagination_array['medias'] as $media) {
                if (count($posts) >= 30) {
                    break;
                }

                if ($media->getType() == 'image' || $media->getType() == 'sidecar') {
                    $posts[] = $media;
                }
            }

            $has_next_page = $pagination_array['hasNextPage'];
            $max_id = $pagination_array['maxId'];
        }

        return $posts;
    }

    static public function getRawLikes($raw_post)
    {
        $instagram = self::getInstagramObject();
        $likes = $instagram->getMediaLikesByCode($raw_post->getShortCode(), $raw_post->getLikesCount());

        return $likes;
    }

    static public function initAccount($account_name)
    {
        $raw_followers = self::getRawFollowers($account_name);

        foreach ($raw_followers as $raw_follower) {
            $follower = new Follower();

            $follower['id'] = $raw_follower['username'];
            $follower['account'] = $account_name;
            $follower['date_in'] = Carbon::today()->toDateString();
            $follower['active'] = 1;

            $follower->save();
        }

        self::scanPosts($account_name);

        self::logScanning($account_name);
    }

    static public function scanAccount($account_name)
    {
        $raw_followers = self::getRawFollowers($account_name);
        $raw_followers_by_id = [];
        $prev_followers = Follower::getByAccount($account_name, 0);
        $prev_followers_by_id = [];

        foreach ($prev_followers as $prev_follower) {
            $prev_followers_by_id[$prev_follower['id']] = $prev_follower;
        }

        foreach ($raw_followers as $raw_follower) {
            $username = $raw_follower['username'];
            $raw_followers_by_id[$username] = $raw_follower;

            if (array_key_exists($username, $prev_followers_by_id)) {
                if (!$prev_followers_by_id[$username]['active']) {
                    $prev_followers_by_id[$username]['date_out'] = null;
                    $prev_followers_by_id[$username]['active'] = 1;

                    $prev_followers_by_id[$username]->save();
                }
                unset($prev_followers_by_id[$username]);
            } else {
                $follower = new Follower();

                $follower['id'] = $raw_follower['username'];
                $follower['account'] = $account_name;
                $follower['date_in'] = Carbon::today()->toDateString();
                $follower['active'] = 1;

                $follower->save();
            }
        }

        foreach ($prev_followers_by_id as $prev_follower) {
            $prev_follower['date_out'] = Carbon::today()->toDateString();
            $prev_follower['active'] = 0;

            $prev_follower->save();
        }


        self::scanPosts($account_name);

        self::logScanning($account_name);
    }

    static public function scanPosts($account_name)
    {
        $raw_posts = self::getRawImagePosts($account_name);

        if (Post::checkIfExistsWithPostId($raw_posts[0]->getShortCode())) {
            return;
        }

        $last_post = $raw_posts[0];

        $post = new Post;

        $post['post_id'] = $last_post->getShortCode();
        $post['account'] = $account_name;

        $action = new Action;

        $action['followers'] = 0;
        $action['other_users'] = 0;


        foreach ($raw_posts as $raw_post) {
            $raw_likes = self::getRawLikes($raw_post);

            foreach ($raw_likes as $raw_like) {
                $exists = Follower::checkIfExists([
                    'account' => $account_name,
                    'id' => $raw_like->getUserName()
                ]);

                if ($exists) {
                    $action['followers'] = $action['followers'] + 1;
                } else {
                    $action['other_users'] = $action['other_users'] + 1;
                }
            }
        }

        $post['actions'] = $action['followers'] + $action['other_users'];
        $post->save();

        $action['post_id'] = DB::getPDO()->lastInsertId();
        $action['datetime'] = Carbon::now()->toDateTimeString();

        $action->save();
    }

    static public function logScanning($account_name)
    {
        $log_scan = new LogScan;

        $log_scan['datetime'] = Carbon::now()->toDateTimeString();
        $log_scan['account'] = $account_name;
        $log_scan['followers'] = Follower::getFollowersCount($account_name);

        $log_scan->save();
    }
}

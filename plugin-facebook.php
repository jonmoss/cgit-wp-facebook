<?php

/*

Plugin Name: Castlegate IT WP Facebook Feed
Plugin URI: http://github.com/castlegateit/cgit-wp-breadcrumb
Description: Facebook feed plugin for WordPress.
Version: 0.1
Author: Castlegate IT
Author URI: http://www.castlegateit.co.uk/
License: MIT

*/
require_once dirname( __FILE__ ) . '/functions.php';
if (! defined('CGIT_FACEBOOK_APPID')||! defined('CGIT_FACEBOOK_USERID') ||! defined('CGIT_FACEBOOK_SECRET') ||! defined('CGIT_FACEBOOK_TOKEN') ||! defined('CGIT_FACEBOOK_URL')){
    if (function_exists(add_action)){
        add_action('admin_notices', 'cgit_facebook_notice_constants');
    }
    else {
        echo "ERROR: One or more constants undefined. Please see README.md!";
        echo "ERROR: Dumping constant values.";
        print_r(array ('APPID' => CGIT_FACEBOOK_APPID, 'TOKEN' => CGIT_FACEBOOK_TOKEN, 'SECRET' => CGIT_FACEBOOK_SECRET, 'USERID' => CGIT_FACEBOOK_USERID, 'URL' => CGIT_FACEBOOK_URL));
    }
}

if (!file_exists(dirname(__FILE__) . '/facebook-php-sdk-v4/autoload.php')){
    throw new Exception("Error: No Facebook SDK detected, please pull latest Facebook SDK.", 1);

}

if (!file_exists(dirname(__FILE__) . '/utilphp/util.php')){
    throw new Exception("Error: No utilphp detected, please pull latest util.php.", 1);

}

require_once(dirname(__FILE__) . '/facebook-php-sdk-v4/autoload.php');
use \Facebook\FacebookRequest;
use \Facebook\FacebookSession;
use \Facebook\GraphPage;
require_once(dirname(__FILE__) . '/utilphp/util.php');



function cgit_get_facebook_feed($softLimit = 3, $typesOf = false)
{
    if (!$typesOf){
        $typesOf = array('photo');
    }
    FacebookSession::setDefaultApplication(CGIT_FACEBOOK_APPID, CGIT_FACEBOOK_SECRET);
    //    FacebookSession::enableAppSecretProof(false);
    $access_token = CGIT_FACEBOOK_APPID;
    $access_token .= '|';
    $access_token .= CGIT_FACEBOOK_TOKEN;
    $userID = CGIT_FACEBOOK_USERID;

    // Create session an request
    $session = new FacebookSession($access_token);
    $request = new FacebookRequest($session, 'GET', '/'.$userID.'/posts?fields=id,name,picture,type,link,message,created_time');

    // Get data back
    $response = $request->execute();
    $page_data = $response->getGraphObject(GraphPage::className());


    // Retrieve up to $softLimit of photo posts
    $array_of_posts = array();

    foreach($page_data->getPropertyAsArray('data') as $post) {

        if (count($array_of_posts) >= $softLimit) {
            break;
        }

        // Only get statues or photos posted by the userID's profile
        if (substr($post->getProperty('id'), 0, strlen($userID)) == $userID &&
                in_array($post->getProperty('type'), $typesOf)
        ) {
            // Save an bunch of information from this post, we'll use this later on for rendering
            $array_of_posts[] = array (
                    'title' => $post->getProperty('name'),
                    'desc' => $post->getProperty('message'),
                    'image' => $post->getProperty('picture'),
                    'link' => $post->getProperty('link'),
                    'date' => $post->getProperty('created_time'),
                    'type' => $post->getProperty('type')
            );
        }
    }

    return $array_of_posts;
}

function cgit_facebook_renderer($array_of_posts, $trimOutput = true) {
    // Generate markup for the feed
    $feed_string = '';
    $i = 0;
    foreach ($array_of_posts as $single_post) {

        $title = htmlentities($single_post['title']);
        $link = htmlentities($single_post['link']);
        $description = util::linkify($single_post['desc']);
        $date = date('l F d, Y', strtotime($single_post['date']));

        preg_match('/<a[^>]+>\s*<img[^>]+>\s*<\/a>/', $description, $matched);
        $description = preg_replace('/<br ?\/?>/', '', $description);
        $description = preg_replace('/<a[^>]*><img[^>]*><\/a>/', '', $description);
        if ($trimOutput){
            if (strlen($description) > 140) {
                $description = util::safe_truncate($description, 140);
            }
        }
        $image = $single_post['image'];
        if ($image){
            $imagelink = '<a href="'. $link .'"><img src="'. $image .'"/></a>';
        }
        if (!$image){
            $imagelink = '';
        }

        $feed_string .= '<div class="feed-item">';
        $feed_string .= '<span class="fb-date">'.$date.'</span><span class="line-span"><hr /></span>';
        $feed_string .= '<div class="facebook-wrapper"><p>'.$imagelink;
        $feed_string .= '<span class="fb-desc">'.$description.'</span></p></div>';
        $feed_string .= '</div>';

    }

    return $feed_string;
}

function cgit_get_cached_facebook_feed($softLimit = 3, $typesOf = false, $trimOutput = false)
{
    if (!$typesOf) {
        $typesOf = array('photo');
    }
    // Server cache settings

    $cache_file = dirname(__FILE__) . '/cgit-cache/facebook-cache['.$softLimit.'].html';
    $cache_time = 10*60; // 10 minutes

    // Generate output based on settings
    $feed = '';
    // If recent cached version, use that
    if(file_exists($cache_file) && time() - filemtime($cache_file) < $cache_time) {
        $feed = unserialize(file_get_contents($cache_file));
        // Else, try to get feed from Twitter
    } else {
        $feed = cgit_get_facebook_feed($softLimit, $typesOf, $trimOutput);
        // If feed available, use that
        if($feed) {
            file_put_contents($cache_file, maybe_serialize($feed));
            // Else, check for any cached version
        } elseif(file_exists($cache_file)) {
            $feed = maybe_unserialize(file_get_contents($cache_file));
        }
    }
    return $feed;
}

// Returns cleaned output for doing custom rendering if you need it.
function cgit_facebook_cleaned_output($softLimit = 3, $typesOf = false, $trimOutput = false) {

    if (!$typesOf) {
        $typesOf = array('photo');
    }

    $dataDirty = cgit_get_facebook_feed($softLimit, $typesOf, $trimOutput);
    $dataClean = array();
    $c = 0;

    foreach ($dataDirty as $data) {
        $dataClean[$c]['Title']  = htmlentities($data['title']);


        $dataClean[$c]['Date'] = date('l F d, Y', strtotime($data['date']));
        $description = util::linkify($data['desc']);

        preg_match('/<a[^>]+>\s*<img[^>]+>\s*<\/a>/', $description, $matched);
        $description = preg_replace('/<br ?\/?>/', '', $description);
        $description = preg_replace('/<a[^>]*><img[^>]*><\/a>/', '', $description);

        $dataClean[$c]['Description'] = $description;
        $dataClean[$c]['Type'] = $data['type'];

        if ($data['image']){
            $dataClean[$c]['Image'] = $data['image'];
        }
        if ($data['link']) {
            $dataClean[$c]['Link'] = htmlentities($data['link']);
        }


        $c++;
    }

    return $dataClean;

}

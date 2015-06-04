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
    add_action('admin_notices', 'cgit_facebook_notice_constants');
}

require_once(dirname(__FILE__) . '/vendor/facebook-php-sdk-v4-4.0-dev/autoload.php');
use \Facebook\FacebookRequest;
use \Facebook\FacebookSession;
use \Facebook\GraphPage;
require_once(dirname(__FILE__) . '/vendor/utilphp-1.0.7/util.php');



function get_facebook_feed($softLimit = 3, $typesOf = false)
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
    $request = new FacebookRequest($session, 'GET', '/'.$userID.'/feed');

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
        if ($post->getProperty('from')->getProperty('id') == $userID &&
                in_array($post->getProperty('type'), $typesOf)
        ) {
            // Save an bunch of information from this post, we'll use this later on for rendering
            $array_of_posts[] = array (
                    'title' => $post->getProperty('name'),
                    'desc' => $post->getProperty('message'),
                    'image' => $post->getProperty('picture'),
                    'link' => $post->getProperty('link'),
                    'date' => $post->getProperty('created_time')
            );
        }
    }

    return $array_of_posts;
}

function cgit_facebook_renderer($array_of_posts, $trimOutput = true){
    // Generate markup for the feed
    $feed_string = '';
    foreach ($array_of_posts as $single_post)
    {
        $title = htmlentities($single_post['title']);
        $link = htmlentities($single_post['link']);
        $description = util::linkify($single_post['desc']);
        $date = date('l F d, Y', strtotime($single_post['date']));

        preg_match('/<a[^>]+>\s*<img[^>]+>\s*<\/a>/', $description, $matched);
        $description = preg_replace('/<br ?\/?>/', '', $description);
        $description = preg_replace('/<a[^>]*><img[^>]*><\/a>/', '', $description);
        $description = str_replace(CGIT_FACEBOOK_URL, ' ', $description);
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

        //$feed_string .= '<p><strong><a href="'.$link.'" title="'.$title.'">'.$title.'</a></strong></p>';
        $feed_string .= '<div class="feed-item clearfix">';
        $feed_string .= '<span class="fb-date">'.$date.'</span><span class="line-span"><hr /></span>';
        $feed_string .= '<div class="facebook-wrapper clearfix"><p>'.$imagelink;
        $feed_string .= '<span>'.$description.'</span></p></div>';
        $feed_string .= '</div>';
            }

    return $feed_string;
}

function get_cached_facebook_feed($softLimit = 3, $typesOf = false, $trimOutput = false)
{
    if (!$typesOf) {
        $typesOf = array('photo');
    }
    // Server cache settings
    $cache_file = ABSPATH . '../cache/facebook-cache['.$softLimit.'].html';
    $cache_time = 10*60; // 10 minutes

    // Generate output based on settings
    $feed = '';
    // If recent cached version, use that
    if(file_exists($cache_file) && time() - filemtime($cache_file) < $cache_time) {
        $feed = unserialize(file_get_contents($cache_file));
        // Else, try to get feed from Twitter
    } else {
        $feed = get_facebook_feed($softLimit, $typesOf, $trimOutput);
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

function cgit_facebook_feed_shortcode ($atts) {

    $defaults = array(
        'limit'     => 3,
        'types'     => false,
        'trimOutput'=> true
    );

    $atts = shortcode_atts($defaults, $atts);

    return cgit_facebook_renderer(get_cached_facebook_feed($atts['limit'], $atts['types'], $att['trimOutput']));

}

add_shortcode('facebook_feed', 'cgit_facebook_feed_shortcode');
?>

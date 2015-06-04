# Castlegate IT WP Facebook Feed #

Castlegate IT WP Facebook Feed is a Facebook feed plugin for WordPress. It provides functions for displaying a Facebook feed on a page, supporting caching. It requires at least PHP 5.3, Facebook's PHP SDK v4, and utilPHP.

## Facebook Apps ##

You'll need to log in to developers.facebook.com and create an app for your website.
My Apps -> New App -> Website is the standard process for pretty much everything.
You can skip the quick start as soon as facebook gives you the option; it's not important for our use.

## Basic usage ##

The plugin requires some setup. In wp-config, the following constants must be defined:

*CGIT_FACEBOOK_APPID is the App ID from your app's dashboard.  
*CGIT_FACEBOOK_SECRET is the 'App Secret' from your app's dashboard  
*CGIT_FACEBOOK_USERID can be gotten most easily by just typing the facebook page URL into http://findmyfacebookid.com/  
*CGIT_FACEBOOK_TOKEN is gotten from developers.facebook.com -> Tools and Support -> Access Token Tool (use the App Token) - you need everything AFTER the pipe, so "672440359555259|2ZphKT83MH4uOlcNPsYtXdhMcKU" -> 2ZphKT83MH4uOlcNPsYtXdhMcKU

This can be found using the facebook profile URL.

The function `get_cached_facebook_feed()` can be used to fetch a Facebook feed, using a cached file to store results for 10 minutes to prevent excessive API calls.

## Parameters ##

The function `get_facebook_feed()` can be called directly if you wish to bypass the cache for some reason. It takes the same arguments.

The function has two optional arguments.
$softLimit will allow you to specify a number of posts to be returned. It will default to 3.
$typesOf allows you to specify as an array the kinds of posts you want to return. $typesOf will default to photo.

The possible types are:
    link, status, photo, video, offer

    get_cached_facebook_feed($softLimit, $typesOf);

You can also use these arguments with the shortcode:

    [facebook_feed limit="example", types="array('example')"]

# Castlegate IT WP Facebook Feed #

Castlegate IT WP Facebook Feed is a Facebook feed plugin for WordPress. It provides functions for displaying a Facebook feed on a page, supporting caching.

## Requirements ##

This plugin requires at least PHP 5.3.

It also requires Facebook's PHP SDK v4, and utilPHP;

They are included as submodules, so the easiest way to get them is to pull the repository.

## Facebook Apps ##

You'll need to log in to developers.facebook.com and create an app for your website.
My Apps -> New App -> Website is the standard process for pretty much everything.
You can skip the quick start as soon as facebook gives you the option; it's not important for our use.

## Configuration ##

The plugin requires some setup. In wp-config, the following constants must be defined:

*CGIT_FACEBOOK_APPID is the App ID from your app's dashboard.
*CGIT_FACEBOOK_SECRET is the 'App Secret' from your app's dashboard
*CGIT_FACEBOOK_USERID can be gotten most easily by just typing the facebook page URL into http://findmyfacebookid.com/
*CGIT_FACEBOOK_TOKEN is gotten from developers.facebook.com -> Tools and Support -> Access Token Tool (use the App Token) - you need everything AFTER the pipe, so "672440359555259|2ZphKT83MH4uOlcNPsYtXdhMcKU" -> 2ZphKT83MH4uOlcNPsYtXdhMcKU

This can be found using the facebook profile URL.

## Functions ##

The function `cgit_get_cached_facebook_feed()` returns postdata from the user's posts. Caching is for 1 hour to prevent excessive calls to the API.

The function `cgit_get_facebook_feed()` can be called directly if you wish to bypass the cache for some reason.

The function `cgit_facebook_renderer` accepts either of the two above functions and renders them with basic HTML.

The function `cgit_facebook_cleaned_output()` returns cleaned data for each post as an array, for writing custom HTML.

## Basic Usage ##

The function `get_cached_facebook_feed()` can be used to fetch a Facebook feed, using a cached file to store results for 10 minutes to prevent excessive API calls.

To render the feed with the built-in settings:

cgit_facebook_renderer(get_facebook_feed());

This will render 3 posts of type 'photo' (So we're sure to get thumbnails - the standard format in a footer or sidebar).

## Custom Usage ##

You can also use the standard number and type settings, but write custom HTML, custom setting and the default HTML, or customise everything.

### Custom HTML ###

cgit_facebook_cleaned_output()

This function returns a cleaned version of the data from each post, neatly sorted into an array. This includes data on the type of
each post, so that you can write different logic for different post-types if you want to.

You will have to write your own HTML around the data, however.

### Parameters ###

cgit_facebook_cleaned_output, cgit_get_facebook_feed() and cgit_cached_facebook_feed(); all take the same arguments, and will
pass these arguments to one another as necessary.

$softLimit will allow you to specify a number of posts to be parsed. It will default to 3.
$typesOf allows you to specify as an array the kinds of posts you want to return. $typesOf will default to photo.
$trimOutput allows you to limit the description to 140 characters (for matching with Twitter feeds).

The possible types are:
    link, status, photo, video, offer

Useful settings are generally
array('status', 'photo') or array('photo', 'video')

You can also use these arguments with the shortcode:

    [facebook_feed limit="example", types="array('example')"]

The shortcode uses caching.


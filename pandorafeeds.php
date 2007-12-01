<?php

/*
Plugin Name: Pandora Feeds for Wordpress Plugin
Plugin URI: http://www.weinschenker.name/pandorafeeds
Description: This plugin lets you embed feeds from Pandora into your WordPress blog.
Version:  0.5.0.3
Author: Jan Weinschenker
Author URI: http://www.weinschenker.name


   $Id: pandorafeeds.php,v 1.7 2007/06/08 19:51:01 acubens Exp $

    Pandora and the Music Genome Project are registered trademarks of Pandora Media, Inc.
    
    Plugin: Copyright 2006  Jan Weinschenker  (email: pandorafeeds@weinschenker.name)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

    The Author wishes to thank:
        - Jean-Paul Franssen for developing the Pandora widget, which inspired me to
          make this plugin.
          visit: http://www.rgb255.nl/blog/2007/02/06/wordpress-pandora-widget/
        - Steve Smith for developing the Feedburner Feed Replacement, from which
          I have learned how to code WP-plugins.
          visit: http://orderedlist.com/wordpress-plugins/feedburner-plugin/
        - The people of Pandora for making this wonderful service of theirs!
*/

// include wp rss functions
require_once (ABSPATH . WPINC . '/rss.php');

add_option('pandorafeeds_settings', $data, 'Pandora Feed Plugin Options');

/**
 * Register with options-page
 */
function register_with_options() {
	if (function_exists('add_options_page')) {
		add_options_page('Pandora Feeds', 'Pandora Feeds', 8, basename(__FILE__), 'pandorafeeds_options_subpanel');
	}
}

/**
 * This function renders the options-subpanel of this plugin. The form is used to store
 * the user's pandora-account-data in the database.
 */
function pandorafeeds_options_subpanel() {
	global $_POST, $wp_rewrite;

	/* This array contains all settings for this plugin. */
	$data = array (
		'account-name' => ''
	);
	/* Get settings from database via WordPress framework */
	$pandorafeeds_settings = get_option('pandorafeeds_settings');
	$pandorafeeds_flash = "";

	if (pandorafeeds_user_is_authorized()) {
		if (isset ($_POST['account-name'])) {
			$pandorafeeds_settings['account-name'] = $_POST['account-name'];
			update_option('pandorafeeds_settings', $pandorafeeds_settings);
			$pandorafeeds_flash = "Your settings have been saved.";
		}
	} else {
		$pandorafeeds_flash = "You don't have enough access rights.";
	}

	if ($pandorafeeds_flash != '')
		echo '<div id="message"class="updated fade"><p>' . $pandorafeeds_flash . '</p></div>';

	echo '<div class="wrap">';
	echo '<h2>Set Up Your Pandora-feeds</h2>';
	echo '<form action="" method="post">
				    <input type="hidden" name="redirect" value="true" />
				    <p>Enter your Pandora-account here. It must be the <em>local</em> part of the email-address with which you registered with Pandora.</p>
				    <p>For example, if you have registered with <b>john.doe@example.com</b>, you should enter <b>john.doe</b> (<a href="http://www.weinschenker.name/pandorafeeds#Account" title="visit Plugin-page">more information about account-names</a>).</p>
				    <p><label for="id">Pandora-account: </label><input type="text" id="account-name" name="account-name" value="' . htmlentities($pandorafeeds_settings['account-name']) . '" size="75" /></p>
				    <p><input type="submit" value="Save" /></p></form>';
	echo '</div>';
}

/**
 * Check if the current user is allowed to activate plugins.
 */
function pandorafeeds_user_is_authorized() {
	global $user_level;
	if (function_exists("current_user_can")) {
		return current_user_can('activate_plugins');
	} else {
		return $user_level > 5;
	}
}

/**
 * This function fetches a feed containing the pandora-staions of the user. 
 * 
 * It returns HTML-listitems (<li> ... </li>) containing the name of the station and 
 * links back to the site pandora has for it.
 *
 * Attribute:
 * $nrItems - the maximum number of items, this feed should return. 
 *            Set to -1 for complete feed.
 */
function pandorafeeds_display_user_stations($nrItems) {
	$rss = pandorafeeds_get_feed('stations');
	if (is_array($rss->items)) {
		$rss->items = array_slice($rss->items, 0, $nrItems);
		foreach ($rss->items as $nr => $item) {
			while (strstr($item['link'], 'http') != $item['link'])
				// get data from feed
				// Big thanks go to Jean-Paul Franssen!!!
				// http://www.rgb255.nl/blog/2007/02/06/wordpress-pandora-widget/
				$item['link'] = substr($item['link'], 1);
			$link = wp_specialchars(strip_tags($item['link']), 1);
			$title = wp_specialchars(strip_tags($item['title']), 1);
			$desc = wp_specialchars(strip_tags($item['description']), 1);
			
			$oddOrEven='';	
			if (!pandorafeeds_is_even($nr)){ $oddOrEven='odd';}
			else {$oddOrEven='even';};?>			
			<li class="pandorafeeds-stations <?php echo $oddOrEven; ?>">
				<a class="pandorafeeds-stations" href="<?php echo $link; ?>" title="<?php echo $title; ?>">
					<?php echo $title; ?>
				</a>
			</li>			
			<?php
		}
	} else {
		pandorafeeds_feed_probably_down();
	}
}

/**
 * This function fetches a feed containing the bookmarked artists of the user. 
 * 
 * It returns HTML-listitems (<li> ... </li>) containing the name of the artist and 
 * links back to the backstage site pandora has for it.
 *
 * Attribute:
 * $nrItems - the maximum number of items, this feed should return. 
 *            Set to -1 for complete feed.
 */
function pandorafeeds_display_bookmarked_artists($nrItems) {
	$rss = pandorafeeds_get_feed('favoriteartists');
	if (is_array($rss->items)) {
		$rss->items = array_slice($rss->items, 0, $nrItems);
		foreach ($rss->items as $nr => $item) {
			while (strstr($item['link'], 'http') != $item['link'])
				// get data from feed
				// Big thanks go to Jean-Paul Franssen!!!
				// http://www.rgb255.nl/blog/2007/02/06/wordpress-pandora-widget/
				$item['link'] = substr($item['link'], 1);
			$link = wp_specialchars(strip_tags($item['link']), 1);
			$title = wp_specialchars(strip_tags($item['title']), 1);
			
			$oddOrEven='';	
			if (!pandorafeeds_is_even($nr)){ $oddOrEven='odd';}
			else {$oddOrEven='even';}?>
			
			<li class="pandorafeeds-favoriteartists  <?php echo $oddOrEven ;?>">
				<a class="pandorafeeds-favoriteartists " href="<?php echo $link; ?>"
					title="<?php echo $title; ?>">
					<?php echo $title; ?>
				</a>
			</li>
			
			<?php
		}
	} else {
		pandorafeeds_feed_probably_down();
	}
}

/**
 * This function fetches a feed containing the bookmarked songs of the user. 
 * 
 * The feed-contents will be forwarded to the rendering-function. 
 * See function pandorafeeds_render_songs($rss, $nrItems, $showCover)
 *
 * Attributes:
 * $nrItems     - the maximum number of items, this feed should return. 
 *                Set to -1 for complete feed.
 * $showCover   - Show am image of the CD-Cover
 *                Set to true (show the image) or to false (show no image)
 */
function pandorafeeds_display_bookmarked_songs($nrItems, $showCover) {
	$rss = pandorafeeds_get_feed('favorites');
	pandorafeeds_render_songs($rss, $nrItems, $showCover);
}

/**
 * This function fetches the feed from Yahoo!Pipes. WP's MagpieRSS facility caches 
 * feeds in the database. So hopefully, not every request will fetch the feed 
 * directly from pandora.
 * 
 * The feed-contents will be forwarded to the rendering-function. 
 * See function pandorafeeds_render_songs($rss, $nrItems, $showCover)
 *
 * Attributes:
 * $pipeUrl		- the URL of the published Yahoo!Pipe
 * 
 * $nrItems     - the maximum number of items, this feed should return. 
 *                Set to -1 for complete feed.
 * $showCover   - Show am image of the CD-Cover
 *                Set to true (show the image) or to false (show no image)
 */
function pandorafeeds_display_bookmarked_songs_viaPipes($pipeUrl, $nrItems, $showCover) {
	// return feed-object
	$rss = fetch_rss($pipeUrl);
	pandorafeeds_render_songs($rss, $nrItems, $showCover);
}

/**
 * This function renders the bookmarked songs of the user. 
 * 
 * It returns HTML-listitems (<li> ... </li>) containing a cover-image of the 
 * song's cd, song-title and artist's name. The artist links back to the
 * backstage-site pandora has for this song.
 *
 * Attributes:
 * $rss			- the feed-object. Must be passed to the function by the 
 * 				  calling funtion.
 * 
 * $nrItems     - the maximum number of items, this feed should return. 
 *                Set to -1 for complete feed.
 * $showCover   - Show am image of the CD-Cover
 *                Set to true (show the image) or to false (show no image)
 */
function pandorafeeds_render_songs($rss, $nrItems, $showCover) {

	if (is_array($rss->items)) {
		$rss->items = array_slice($rss->items, 0, $nrItems);
		foreach ($rss->items as $nr => $item) {
			while (strstr($item['link'], 'http') != $item['link'])
				$item['link'] = substr($item['link'], 1);
			$link = wp_specialchars(strip_tags($item['link']), 1);
			$title = wp_specialchars(strip_tags($item['title']), 1);
			$desc = wp_specialchars(strip_tags($item['description']), 1);

			// get data from feed
			// Big thanks go to Jean-Paul Franssen!!!
			// http://www.rgb255.nl/blog/2007/02/06/wordpress-pandora-widget/
			if (preg_match('/http:\/\/www.pandora.com\/art.*\.jpg/', $item['atom_content'], $matches)) {
				$image_url = $matches[0];
			} else {
				$image_url = "http://www.pandora.com/images/no_album_art.jpg";
			}
			$track = wp_specialchars(strip_tags($item['dc']['track_title']), 1);
			$artist = wp_specialchars(strip_tags($item['dc']['artist_title']), 1);
			
			$oddOrEven='';	
			if (!pandorafeeds_is_even($nr)){ $oddOrEven='odd';}
			else {$oddOrEven='even';}
			
			// ############ begin rendering ############ 
			?>
			<li class="pandorafeeds-favorites <?php echo $oddOrEven ;?>">
            <?php if ($showCover){ ?>
                <img class="pandorafeeds-favorites" src="<?php echo $image_url ;?>"
                	title="<?php echo $track ;?> - <?php echo $artist; ?>" 
                	alt="<?php echo $track;?> - <?php echo $artist; ?>" />
            <?php } ?>
                <a class="pandorafeeds-favorites" href="<?php echo $link; ?>" 
                	title="<?php echo $desc; ?>"><?php echo $track;?></a>
                	by <?php echo $artist; ?>
			</li>
            <?php
            // ############ ended rendering ############ 
            
            
		}
	} else {
		pandorafeeds_feed_probably_down();
	}
}

/**
 * This function fetches the feed from pandora. WP's MagpieRSS facility caches 
 * feeds in the database. So hopefully, not every request will fetch the feed 
 * directly from pandora.
 */
function pandorafeeds_get_feed($mode) {

	// get options from WordPress.
	$pandorafeeds_settings = get_option('pandorafeeds_settings');
	$username = $pandorafeeds_settings['account-name'];
	//r eturn feed-object
	$feedUrl = "http://www.pandora.com/feeds/people/" . $username . "/" . $mode . ".xml";
	$rss = fetch_rss($feedUrl);
	return $rss;
}

/**
 * Displays an error-message if the plugin cannot download the feed.
 */
function pandorafeeds_feed_probably_down() {
	echo __('<li>Could not retrieve feed-data from <a href="http://www.pandora.com/">Pandora</a>. The service might be down.</li>');
}

add_action('admin_menu', 'register_with_options');


function pandorafeeds_is_even($number) {
   return $number & 1; // 0 = even, 1 = odd
}
?>

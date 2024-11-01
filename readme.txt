=== WPMC ===
Authors: Karsten Eichentopf
Contributors: Karsten Eichentopf
Donate link: http://www.towerdefensehq.de/
Tags: MyMiniCity, Mini City, Mini, City
Requires at least: 2.3.2
Tested up to: 2.3.2
Stable tag: 1

Plugin that creates automatic links  to you MyMiniCity and displays its data.

== Description ==

Plugin that creates automatic links  to you MyMiniCity and displays its data.
Many settings and editable output.

The link to you city will be altered in regards of the city needs. Those needs are configurated by the thresholds.
For example: If you set an industry threshold of 5 unemployment up to 5% will be tolerated. But after this the industry link will be used to direct people to your city.

The html output for content pages can be custimised in anyway you want. Just use the variables and build the html as you like.

The varaibles available can be found in the backend.

This plugin caches your cities XML. It will update **only every 5 minutes**. This is a caching mechanism to avoid calling to a different webpage everytime a page from your blog is loaded.

If you just want a simple link to your city you can use:

**{minicity_link}**

If you want the full html output use:

**{minicity}**

For php integration use:

**`WPMC_getLink()`**

This function will return the link to you city. Do something like this:

`if(function_exists('WPMC_getLink')){
	echo '<a href="'.WPMC_getLink().'">MyCity</a>';
}`

**`WPMC_displayCity()`**

This function will output the whole html. Use it like this:
`if(function_exists('WPMC_displayCity')){
	echo WPMC_displayCity();
}`

You can pass a different custom html to this function like this:
`if(function_exists('WPMC_displayCity')){
	echo WPMC_displayCity('<h1>%name%</h1>');
}`

== Installation ==

1. Upload `plugin-name.php` to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to the options section. Take a look at the settings and enter at least your MyMiniCity Name.
4. Place **{minicity}** in your pages or posts

== Frequently Asked Questions ==

None right now

== Screenshots ==

1. /tags/1/wpmc.jpg

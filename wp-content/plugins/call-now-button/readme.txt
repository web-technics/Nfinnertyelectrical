=== Call Now Button ===
Contributors: jgrietveld, jasperroel
Donate link: https://callnowbutton.com/donate/
Tags: call button, click to call, convert, call now button, contact button
Requires at least: 3.9
Requires PHP: 5.4
Tested up to: 6.0
Stable tag: 1.1.7
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

The web's #1 click to call button for your website! A very simple but powerful plugin that adds a Call Now Button to your website for your mobile visitors (only for responsive websites).

== Description ==

###What does the plugin do?

This plugin places a Call Now Button (click-to-call button) to the bottom of the screen which is **only visible for your mobile visitors**. Because your mobile visitors already have a phone in their hands this plugin will allow them to call you with one simple touch of the button.
No more navigating to the contact page and no more complicated copy/pasting or memorizing the phone number!

###Could not be easier!
The settings are very easy: enable and enter your phone number. That's it!

If you want to add some text to your button, that's possible. Entering text is fully optional - leaving it empty will show a nice circular phone button to your visitors (take a look at the screenshots).

###Need more control?
Under the **Presentation tab** you can change the color of the button, move it to a different location on the screen and limit the pages on which the button should be visible.

Under the **Settings menu** you'll find a bunch of features that allow you to enable click tracking in Google Analytics, fire a conversion tag so a call is registered as a conversion in Google Ads, adjust the size of the button or move the button further backwards in case you want something else to sit on top of it (e.g. your privacy notice).

###Need even more control?
Easily upgrade the plugin to Premium to add additional features such as multiple buttons, additional actions such as SMS/Text, Email, WhatsApp, Maps and links, a button scheduler, more advanced page selection options, Buttonbars and Multibuttons, a live preview window and much much more!



== Installation ==

1. From your WordPress Dashboard go to 'Plugins' > 'Add new' and search for 'Call Now Button'.
2. Click 'Install Now' under the title of the Call Now Button plugin
3. Click activate
4. Go to 'Settings' > 'Call Now Button' and check the box to activate the button and enter your phone number.
5. Click 'Save' and you're done!

Or:

1. Upload the `call-now-button`-folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to 'Settings' > 'Call Now Button' and check the box to activate the button and enter your phone number.
4. Click 'Save' and your Call Now Button is live!


== Frequently Asked Questions ==

= Where can I enter my phone number? =

In the Settings section on your WordPress Dashboard you'll find a new addition: Call Now Button. Click on it to go to the plugin settings.

= Can I add text to the button? =

Yes, it is possible to add text to your button. You can chose to show the text inside the full width button across the bottom or top of the screen. It's also possible to add a text bubble next to the circular button. This works for all placements with the exception of the center button positions at the top an bottom of the screen.

= I don't see the button on my website but I'm looking at it with my mobile phone. Why? =

First step is to check if the button is enabled - this is the first checkbox on the settings page.

Next thing is to make sure that your theme is responsive. Responsive means that the website adapts to the size of the screen it's being viewed on. Simply put, if you need to zoom in to be able to read the text of your website on your mobile phone, the plugin will not work.

Last thing to check is if you have a caching plugin installed. Caching plugins make your website faster by showing a copy of your website instead of the actual website. As a consequence you will not see the Call Now Button because the copy was made before the button was installed. To fix this, go to your cache plugin settings page and empty the cache (sometimes called flush the cache). After doing this you should see the button. Do the same thing each time you make changes to the button.

= My website is responsive but I don't see the button

Check if you have any caching plugins active on your website. Your website is likely cached and you're looking at an older copy of your website. Delete/empty the cache and reload your website.

= I updated the button but I'm not seeing the changes on my website

You have a caching plugin active on your website and you are looking at a cached version of your website. Delete/empty the cache and reload your website.

= I only see the button on some pages of my website

You have a caching plugin active on your website and the cached pages are showing an older version of your website. Delete/empty the cache and reload the pages.

Another option could be that you have set the Call Now Button to only appear on specific pages. Go into the Call Now Button settings, open the Presentation tab and check that the field next to Limit appearance is empty.

= Do I have to add a country code to my phone number? =

You don't have to, but I recommend that you do to increase your options internationally.

= Do I start the number with + or 00? =

In most countries you'll be fine using either but there are some exceptions. To be on the safe side I would recommend using the plus sign.

= Can I change the appearance of the Call Now Button? =

Yes! You can easily change the color of the button and make it sit in any of the 8 preset locations across the screen of a phone. You also have the option to spread it out over the full bottom or top of the phone screen.

= I only want to show the button on a few pages. Is that possible?

Yes, you can enter the IDs of posts and pages you wish to include or exclude.

= I need way more flexibility! Isn't there a PRO version that I can use? =

Yes, you can upgrade to Premium to enable tons of extra features. Checkout [callnowbutton.com](https://callnowbutton.com/) to see what's included or just give it a try.


== Screenshots ==

1. 3 variations of the Call Now Button
2. The basic settings
3. The presentation settings
4. The settings


== Changelog ==
= 1.1.7 =
* Fix for websites using WP Rocket LazyLoad

= 1.1.6 =
* Improved WP Rocket cache handling
* Improved WP Super Cache handling
* Fix for resizing of full width button
* Small U/I improvements

= 1.1.5 =
* Fix for W3 Total Cache lazy-loading breaking the button styling

= 1.1.4 =
* New phone icon on call button
* Sunsetted the original (classic) corner button
* Added Facebook Messenger, Telegram and Signal buttons (Premium)
* New icons for link (calendar) and anchor (up arrow) buttons (Premium)
* Anchor button now has smooth scroll (Premium)
* Location button now has directions (Premium)
* WhatsApp modal improvement (multiple speech bubbles, animated conversation and notification count) (Premium)

= 1.1.3 =
* Bugfixes

= 1.1.2 =
* Bugfixes

= 1.1.1 =
* Fix for storing profile info

= 1.1.0 =
* Full code refactor
* Conditions now in WordPress table (Premium)
* Warning notification when debug mode is active (Premium)
* Added upgrade success page
* Minor bug fixes
* Performance improvements

= 1.0.8  =
* WhatsApp chat modal (Premium)
* Multiple buttons on a single page (Premium)
* Icon selection (Premium)
* Drag and drop action sorting (Premium)
* Time selector in the preview (Premium)
* Button animations (Premium)
* Set link target (Premium)
* Display setting shown in button overview (Premium)

= 1.0.7  =
* Bugfix for Localization

= 1.0.6  =
* Adding SMS support to Premium
* More intuitive scheduler in Premium
* Better timezone checks
* Other small fixes and improvements

= 1.0.5  =
* Preview improvements
* Back link when editing actions
* Some bug, style and copy fixes

= 1.0.4  =
* Live button preview in Premium
* Easy activation of Premium via email
* Tab switching while editing
* Bugfix for timezones in scheduler
* Update notice stays visible till closed

= 1.0.3  =
* Fixes a plugin upgrade bug

= 1.0.2  =
* Fixes a domain name bug which caused a very sticky warning notice during the upgrade process

= 1.0.1  =
* Fix upgrade flow

= 1.0.0  =
* Introducing Premium
* UI improvements

= 0.5.0  =
* Button enablement is back in the creation view
* Small UI improvements
* Backend improvements

= 0.4.7  =
* Added notice bar for live buttons without a phone number entered

= 0.4.6  =
* Fixed an upgrade regression (caused by the previous bugfix) which forced the position to become a boolean in certain scenarios

= 0.4.5  =
* Fixed an upgrade regression which forced the position to be FULL in certain scenarios
* Removed HTML element that could overlay the label for certain buttons

= 0.4.4  =
* UI improvements

= 0.4.3  =
* Critical fix

= 0.4.2  =
* Button styling adjustments
* Security improvements (input sanitization, output escaping)

= 0.4.0  =
* Tabbed admin interface
* Google Ads conversion tracking
* Text bubbles for standard circular buttons
* 6 additional button placements
* Hide button on the front page
* Change the color of the icon
* Small design changes
* Other small plugin improvements

= 0.3.6  =
* Validation fixes
* Zoom controls icon size in Full Width buttons

= 0.3.5  =
* Small JS fix

= 0.3.4  =
* Bug fix for function causing 500 error on some versions of PHP
* Added feature to increase or decrease the button size
* Added feature to change the z-index of the button with a slider

= 0.3.3  =
* Check for active caching plugin
* Added links from plugins page to settings page

= 0.3.2  =
* Option to hide icon in text button
* Fix for gtag tracking code
* JS fix
* Fix for iOS border bug

= 0.3.1  =
* Small bug fix

= 0.3  =
* Added the option to add text to your button
* Added option to either include or exclude certain posts and pages to show the button on (this used to be just exclude)
* Some small design changes to the button

= 0.2.1  =
* Fix for conflict with certain 3rd party plugins

= 0.2.0  =
* New circular button design
* Option to revert to the old button design
* Classic button design still available through advanced settings
* Added middle button position
* Added admin notices for clarity
* Added link to Google Analytics integration manual
* More contact links to support and feature requests
* Some small design tweaks to admin screen

= 0.1.3  =
* Click tracking added for Universal Analytics
* Phone icon now SVG so super crisp on high pixel density screens (e.g. Retina screens)
* SVG icon embedded in code so no more http requests

= 0.1.2  =
* Transparent button fix
* Small debug fixes

= 0.1.1  =
* JavaScript fix (needed for Advanced Settings)

= 0.1.0 =
* Change the color of the button
* Change the appearance of the button
* Track button clicks via Google Analytics
* Limit the appearance to specific pages

= 0.0.1 =
* First time launch

== Upgrade Notice ==
= 1.0.6 =
* Upgrade now to keep your Call Now Button up to date. The update contains some small UI improvements and we've added SMS/Text support to the Premium version. Maybe give Premium Free a go!

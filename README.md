# GO Recent Tweets
Add a 'Recent Tweets' widget to your WordPress website. Uses the most recent Twitter API to retrieve recent (re)tweets, then caches them in order to reduce server load.

## Installation
Download the zip file and unpack into @wp-content/plugins/@. Go to https://apps.twitter.com/, log in with your Twitter username and password, and choose 'Create New App'. Fill out the form and hit 'Create your Twitter application'.

After doing so, click the newly created app and go to the 'Keys and Access Tokens' tab. At the same time, open up the admin section of your WordPress installation and navigate to Settings > Recent Tweets.

Copy-paste (from the Twitter Application management screen) your consumer API key and secret as well as your access token and access token secret into the relevant fields on the WordPress Recent Tweets settings page. Fill out your Twitter username and the other options, too. Hit 'Save Changes'.

Now, within WordPress, head over to Appearance > Widgets and add a Recent Tweets widget to any sidebar or widgetized area. Feel free to rename the widget (or even fully remove its title). That's it!

If you ever want to change these settings or somehow force-refresh the list of tweets on your WordPress site (after you've removed an embarrassing tweet, perhaps), simply use the 'Clear Cache' button. Tweets are cached for 12 hours, after all (in order to speed up your site's load time).
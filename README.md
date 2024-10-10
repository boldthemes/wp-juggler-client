# WP Juggler Client #

> [!IMPORTANT]
> This repository hosts WP Juggler Client plugin. For WP Juggler [Getting Started Guide](https://github.com/boldthemes/wp-juggler-server/?tab=readme-ov-file#getting-started-guide) or WP Juggler Server plugin, visit this repository: [WP Juggler Server plugin](https://github.com/boldthemes/wp-juggler-server)

WP Juggler is your ultimate solution for effectively managing an unlimited portfolio of WordPress sites from a single, intuitive dashboard. 

WP Juggler helps you to enhance your operational efficiency, ensure your sites stay current with updates, and reclaim precious time to focus on more impactful projects. 

Being an open-source and completely free tool, WP Juggler is designed to revolutionize your WordPress maintenance experience without costing you a dime.

## How does it work - Main concepts ##

WP Juggler has two components:

- [WP Juggler Server plugin](https://github.com/boldthemes/wp-juggler-server)
- WP Juggler Client plugin - This repository

## Network setup - as easy as one, two, three ##

You can setup your WP Juggler Network in three simple steps:

1. Kickstart the process by installing the [WP Juggler Server plugin](https://github.com/boldthemes/wp-juggler-server) on one of your WordPress sites
2. Install WP Juggler Client plugins on sites you want to control and manage
3. Register and activate your sites on your server with a single mouse click 

## Getting Started Guide ##

### Install plugins ###
1. Download **wp-juggler-server.zip** file from the [latest release](https://github.com/boldthemes/wp-juggler-server/releases/latest) of **WP Juggler Server** plugin.
2. Install and Activate WP Juggler Server plugin on one of your WordPress sites. This site will serve as your control panel.
3. Download **wp-juggler-client.zip** file from the [latest release](https://github.com/boldthemes/wp-juggler-client/releases/latest) of **WP Juggler Client** plugin.
4. Install and Activate WP Juggler Client plugin on WordPress sites you want to monitor and manage remotely.

### Add new site ###
1. Navigate to **WP Juggler > Sites** in your server's wp-admin and click **Add New**
2. Enter the site name as title, enter the site url. 
3. Click **Assign Users** and add the users who will be able to manage the added site (add your user for start). 
4. Click **Save** and copy API Key to clipboard

### Activate new site ###
1. Navigate to **WP Juggler** screen in your client site's wp-admin and enter bith API Key and server's url
2. Click **Save Settings**. You will should see the message that your site is successfully activated
3. If the activation fails and the Auth Header Test Row also says "Failed", do as instructed in the section [Enable PHP HTTP Authorization Header](https://github.com/boldthemes/wp-juggler-client?tab=readme-ov-file#enable-php-http-authorization-header) 

![Screenshot of WP Juggler Site Edit Screen](https://bold-themes.com/wp-content/wp-juggler-assets/wp-juggler-client.png)

### Fetch your first data ###
1. Navigate to  **WP Juggler > Control Panel** in your server's wp-admin and your newly activated site should appear in the list.
2. Click the arrow at the end of its row to expand the panel and click **Refresh All Site Data**
3. Once the refresh finishes you will be able to see the summary of the data retrieved from your site. 
4. Explore the available info by clicking buttons in the expansion panel

### Enable one-click login to wp-admin ###
1. Navigate to **WP Juggler > Sites** in your server's wp-admin and edit the desired site.
2. Check **Automatic Login**, enter **Remote Login Username** (username of the user you are going to log in on the target site) and click **Save**
3. On your client site, edit the User's profile and check **Enable auto login for this user**. 
4. We are all set. You can test the login by first logging out of the client site's wp-admin.  
4. Lastly, go to your control panel and click **wp-admin** button in your site's row. You should be automatically logged in as chosen user.

### Enable PHP HTTP Authorization Header ###

> [!Note]
> This section describes the actions that need to be taken if the Auth Header Test says Failed either on Server or Client side

#### Shared Hosts ####
Most shared hosts have disabled the HTTP Authorization Header by default.

To enable this option you'll need to edit your .htaccess file by adding the following:

```
RewriteCond %{HTTP:Authorization} ^(.*)
RewriteRule ^(.*) - [E=HTTP_AUTHORIZATION:%1]
```
just after
```
RewriteEngine on
```

#### WPEngine ####
To enable this option you'll need to edit your .htaccess file by adding the following (see #1):

```
SetEnvIf Authorization "(.*)" HTTP_AUTHORIZATION=$1
```

## ToDo List ##

If you have a feature proposal or an idea on how to make WP Juggler better and more useful, please use [Issues section](https://github.com/boldthemes/wp-juggler-server/issues). We will be glad to review them and add them to [the list](https://github.com/boldthemes/wp-juggler-server/?tab=readme-ov-file#todo-list).
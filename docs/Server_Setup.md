# Server Setup
This guide will describes the steps you will need to take when setting up a new server. Since this project uses the WAMP stack, it is assumed that a Windows server has already been deployed.

## 1. Set up the WAMP stack
Download and install WampServer from http://www.wampserver.com/en/download-wampserver-64bits/. Specifically, we're using version 3.0.6 which includes Wampserver 3.1.0, Apache 2.4.27, PHP 5.6.31, and MySQL 5.7.19.

*Note: although we built the system with WampServer, any Windows server with these services would work just fine*

## 2. Configure Apache
First, create the web hosting directory (currently labled 'IEFDF') under the /www directory. Apache must be configured to only allow direct access to the defined webpages. <<TODO: add directions for this>>

## 3. Configure MySQL
In MySQL, you must create an account so that the site can interact with the database. You should randonmly generate a username/password, and securely store them.

You will then need to import the grant application schema using the database_schema.sql file. This database should be named 'hige'. Optionally, you may want to import other saved data with a saved file. <<TODO: add more specific instructions; current instructions can be found in the 'Migrating the Server' document>>

## 4. Move server files to server directory
Most of the files in the repository need to be moved over to the web hosting directory. These include: 
 - config.ini: configuration file to hold information such as relative directories and username/password combinations; this way, no critical information needs to be hard-coded
 - index.php: a simple file which just redirects users to the homepage upon navigating to the site; this functionality might be configurable directly in Apache which would make this file redundant
 - favicon.ico: the site's icon, which appears in tabs where the site is loaded
 - /pages: files associated with each navigatable web page
 - /functions: general server-side PHP functions
 - /ajax: more specific server-side 'API'-style functions, usable by AJAX javascript calls
 - /style: simply holds the custom CSS style sheet
 - /images: holds site images
 - /uploads: an empty directory which will fill up over time with subdirectories containing user-uploaded files for their applications
 - /include: somewhat of a miscellaneous directory; holds phpCAS files & configuration, php class definitions, font files, and widely used static html files
 - /PHPMAILER: dependency which allows for email messages to be sent from email clients in PHP
 - /FileSaver.js-master: dependency which allows users to create and download files directly in their browsers, used for excel summary sheet function on the application list page

## 5.


## 6.

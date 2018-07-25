# Server Setup
This guide will describes the steps you will need to take when setting up a new server. It is assumed that a compatible OS is already up and running.

## 1. Set up the LAMP stack
This application was built on the LAMP stack. Although it was originally built on WampServer, using a Windows version of the stack (WAMP), it should work on any modern LAMP build. It is recommended to use the most up-to-date long term stable release versions of these technologies.

If you would like to set up the server as it was originally during development, download and install WampServer from http://www.wampserver.com/en/download-wampserver-64bits/. Specifically, we used version 3.0.6 which includes Wampserver 3.1.0, Apache 2.4.27, PHP 5.6.31, and MySQL 5.7.19.

## 2. Configure Apache
First, set up the web hosting directory for the LAMP stack. With many pre-made LAMP options, this directory is usually labled under /www. This is the case with WampServer, in which case you must create the web hosting directory (currently labled 'IEFDF') under the /www directory. The full directory should look something like "/server/www/IEFDF/".

Next, Apache must be configured to only allow direct access to the defined webpages. <<TODO: add directions for this>>

## 3. Configure MySQL
In MySQL, you must create an account so that the site can interact with the database. You should randonmly generate a username/password, and securely store them.

You will then need to import the grant application schema using the database_schema.sql file. During development we named this database 'iefdf', but you can name it to anything that makes sense; this will require modifying the top of the .sql file. Optionally, you may want to import other saved data with a saved file. <<TODO: add more specific instructions; current instructions can be found in the 'Migrating the Server' document>>

## 4. Move server files to server directory
Most (but not all!) of the files in the repository need to be moved over to the web hosting directory. These include: 
 - sample_config.ini: sample configuration file to hold information such as relative directories and username/password combinations; this way, no critical information needs to be hard-coded
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

## 5. Config File
Perhaps the most important file is the configuration file, as described above. The first step is to remove "sample_" from the name, so it is just named "config.ini".

Within this file, you will need to set all the variables to their appropriate values. This includes the database username/password, the mail server username/password, and more. Some variables may be correctly set ahead of time, but it is recommended to double check all of them regardless. String variables that have special characters need to have quotes around them, although all strings can have quotes around them with no issue.

## 6. Final Tests
At this point, everything should be ready to go. However, there could have been an error when setting up the server, so it is imperitive that you test the application's functionality in full. Here is a basic checklist of functionality that you should check for:
  - [ ] Users (who aren't HIGE staff) can create and submit applications. Appropriate errors should pop up when fields are left empty or invalid data is given. Applicants can optionally upload file attachments. After submitting, applicants can no longer modify their applications, but they may attach more documents. Specified department chairs should be emailed with a confirmation message upon submission.
 - [ ] Department chairs can give their approval on other users' applications where appropriate. They can later view their associated applications, but make no changes.
 - [ ] Application approvers can approve, deny, or hold applications. Emails shoud be sent out to applicants after confirmation. Appropriate errors should appear if email or amount awarded is left empty.
 - [ ] Committee members can view applications, but make no changes.
 - [ ] Applicants can create final reports after getting their applications approved. The same things that were tested during the initial application should be tested here, except for notifying the department chairs.
 - [ ] Final report approvers should have the same functionality on the final report page that application approvers had on the application page.
 - [ ] Administrators can access an admin page, from which they can add and remove other admins, committee members, application approvers, and final report approvers.
 - [ ] Administrators have all the same functionality as application approvers and final report approvers on their respective pages. In addition, they have access to an admin menu, from which they can modify application/report fields directly, and then save changes.
 - [ ] HIGE staff can view all applications on the application list page. They can use various filters to narrow down the list. They can also download a summary excel sheet to view details of the current applications in the list.
 
 You should also make sure that you're able to manipulate the database directly without any issues, probably using MySQL Workbench. <<TODO: add more specific instructions; current instructions can be found in the 'Manipulating the Database' document>>

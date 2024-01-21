# Protect Intellectual Property ESP8266

It can happen that someone starts selling copies after you put a lot of effort in the development of an application.

That is where this tool comes in. You can limit the functionality of each and every 'unwelcome copy' of ESP8266 software out there....

To install and use this tool you need to do something:

- You setup this tool on a Raspberry Pi (3,4,5,... ) with Apache, php and sqlite.
- Put something extra in your application. (Working examples available in the inos folder)
- Import the bin file of your app with a single click into verions management of this tool.

That's all. 

- Send ESP8266's with any version of your app to new buyers. 
- To guarantee that the app runs in full mode and receives updates the customer needs to register after which it is accepted.
    - during registration the customer receives a mail with a link which needs to be clicked to confirm the registration.
    - after confirmation the request needs to be accepted so it is known as a 'welcome copy'.
    - you can change a setting so you do not need to accept manually.

And of course every time you create a new app or new version you import the bin file with a single click and select a setting.

- Every version has a setting to control the run mode of all the apps with that version.
    - The default setting of each version is to let all apps out there run in full mode and only accepted will receive updates.
    - You can change the setting so that devices which are not accepted and have that version will fall back to limited mode.
    - You can change the setting so that any device with that version will run in full mode and receive updates.

How does it work ?

- The app tries to update itself from your web server every now and then and this tool sends an appropriate answer....
    - the key to each version is the md5 hash of the bin running in the device itself.
    - accepted devices which run your bin file receive the update or the answer that there is no update.
    - any other device receives an answer which depends on the setting of the version it runs.
    - (limited_run forces fall back to limited, full_run full mode without updates, full_update full mode and allows updates)

- A hacked image (somone may extract an image and modify it with a hex editor)
    - asking for an update has an illegal md5 and receives an error causing it to stay in limited mode.
    - not reaching your site or not asking for an update stays in limited mode.
    - unless.... it is hacked enough to get and stay in full mode and is out of your control.

The examples show how to make it very, very difficult for anyone to create a hacked version with the last result.

The tool creates an sqlite database and has some forms so no manual database activity is needed:

- table settings : contains the configuration and is managed by the form ms.html
- table requests : filled by end user or by you usinging the request registration form rr.html
- form mr.html manages requests status between registered, accepted and blocked an can delete requests completely.
- table versions : contains details of your bin files and is managed by the form mv.html
- table downloads : holds details of the downloads and you may delete old details by the form md.html
- table unregistered : holds details of illegal download attempts and is managed by the form mu.html
- an additional page si.html shows information on table contents, backups and system information 

Some options you can configure on ms.html :

- management password which is required for all management pages.
- email for confirmation of registrations and email for download notifications (default=disabled)
- bypass the registered status so requests receive the status accepted automatically (default=disabled)
- maintenance mode (default=enabled) disables downloads from public ip addresses except your own so you can test downloads of new bins and leave maintenance mode when things are fine
- backup type and location

It is recommended to use email confirmation and to not bypass the registered status to accept automatically.

- Use email confirmation so you know that the email entered on the user registration form is valid.
- Use mr.html to accept requests so no unwanted device is accepted automatically.

## The Setup

Follow Installation_Details.md to install this tool.
It may contain too many details for you. Just read what you need and skip the rest.

In short Installation_Details.md contains :

- Install a Raspberry Pi OS
- Install sqlite, apache and php
- Make it available to the Internet
- Install this tool for http access
- Add https with self signed certificate
- Move the tool to https
- Get some public domain names
- Install Let’s Encrypt certificate
- Add new domain names to the certificate
- Email Setup

After the installation .... we can test.

From here, to be able to explain further, I use a dummy app named 'myWemosApp'.

The user will use the page rr.html to register requests for his device.
Edit the file rr.html and find the value myWemosApp in 3 places. You can change these 3 later when you start working with your own app. (When you have more apps just copy rr.html to somethingelse.html and change the 3 fields and you have a registration screen for another app)

Now you should be ready to use the form rr.html to register requests for the myWemosAp app with dummy mac addresses and test the other forms. Use your own mail address during registration so you can use the registrations later during testing.

You can manage your requests with mr.html and you will notice 3 dummy registrations. I use these below to explain some testing. You can delete them later.

To enable actual downloads you have to add your apps versions with mv.html.

First you may want to configure and test email.

## Email setup

Email is disabled by default on ms.html. To enable email first follow the 'Email Setup' section in 'Installation_Details.md'.

After enabling email test it from the application.

Start ms.html and configure email settings. Enable them by setting all mail values to true.

Register a request with a dummy mac on rr.html, enter your own mail address, check your mail, click the confirm link and use mr.html to manage the request. Also use rr.html to retrieve your key and request an update for your email address (just change some lower case characters to upper case).
Use mr.html to see the change in the request.

While doing this you should receive several mails.

## Add app versions to the database

In Arduino IDE you compile and upload the first version to your ESP8266. Use Export Compiled Binary to create a binary. Copy the newly created bin file to the bins folder on the web server. 
Click the 'Import Versions' button on the mv.html page and the new bin file is in the database.

After a first version always create a next version to test the automatic updating....We will do that later with an example. 

Lets first test with a dummy setup to check if things work so far.

Enable maintenance mode on ms.html.

In maintenance mode only you have access :

- only ESP8266's on your network can download new versions.
- all ESP8266's out there which would download a new version receive a message that there is no new version available.
- web pages accessed from your network to your domain names do function so you can test.
- web pages access from your network via the ip of your Pi is supported for devices for which you have an exception on ms.html.
- note that https access from your network requires you to accept the risk because the Let's Encrypt certificate is only for domain names.
- web pages accessed by others on the internet will not function.

In case of a new version for an exisiting app :

- you rename the old bin so there is a version number in the name like myWemosApp.ino.bin_V1.0.0 and move it to the bins/oldbins folder.
- you put the new bin yourApp.ino.bin in the bins folder.
- you import the new bins by clicking 'Import Versions'.
- you can test downloading of the bin file with test ESP8266's for which you change the status between accepted and other values.
- you can test by changing the limit_mode of the version on the mv.html page.
- NOTE : you can also test using your web browser and that is what we will do below.
- ALSO : you should never delete entries which were downloaded to user devices.
- it is safe to delete versions which you import during the testing phase because they are not active in user devices.
- when all is fine you only leave the versions which are oke in the database and leave maintenance mode.

After first setup your new database holds some dummy entries so we can test with a web browser.

This is what we have :
```
Dummy mac 1 is 11:11:11:11:11:11 status registered
Dummy mac 2 is 22:22:22:22:22:22 status accepted
Dummy mac 3 is 33:33:33:33:33:33 status blocked

myWemosApp.ino.bin 'myWemosApp.ino.bin Version : 1.0.0' full_run 477fc2d43952a39523c93529cbb49582 is a dummy for an old version (file is in oldbins)
myWemosApp.ino.bin 'myWemosApp.ino.bin Version : 2.0.0' full_run 4026cf80201cb2ac5e493632bf84f09a is a dummy for the new version

```

You can see them on mr.html and mv.html

- When you changed a status change the status back.
- When you deleted the registrations just delete the file registrations.db or rename it to something else so you can put it back later.

Before we start testing, start a terminal on your Pi and type the next to see some logging during testing :

- tail -f /var/log/apache2/error.log

To simulate download requests you can use an url in your browser like :

- htpp://ipaddressofyourpi/updates/tool1.php?mac=somemacaddress&md5=somemd5

You would expect a download for the accepted mac with md5 of V1.0.0 only.

The ip address of my Pi is 192.168.2.13 and this gives a download of the new file
- http://192.168.2.13/updates/tool1.php?mac=22:22:22:22:22:22&md5=477fc2d43952a39523c93529cbb49582

The next simulates an update request from a device which is already up to date so it will not download.
- http://192.168.2.13/updates/tool1.php?mac=22:22:22:22:22:22&md5=4026cf80201cb2ac5e493632bf84f09a

You should see some logging and you should also have an entry in the downloads table and see that on md.html.

Other combinations should not download the file but give some logging.

You can also try to download with your domain name. Results should be the same.

Also change the status of the requests on mr.html and limit_mode on mv.html and see what happens when you try to download.

After these tests you may see and recognise recordings in the unregistered table using mu.html.

#### Whoops you did delete a version which you should not

Never delete entries from versions which could be downloaded in the past because a download request is only accepted when the md5 of the download request is recognized. So when you delete a version which is active in a device anywhere, that device can not be upgraded anymore.

You can revert the accidental deletion by using the bin file of that version like :
- rename the current bin file so it does not end with .bin
- do not move but copy the file which would have the right md5 from bins/oldbins to bins. (you saved it there)
- remove the version part of the file so it ends with .bin again
- click 'Import Versions' and update the notes field of the newly imported old version to what it was
- delete the bin file you just imported from the bins folder. (the original should still be in the bins/oldbins folder)
- rename the file which you renamed in the first step so has the right name with .bin again

You will never completely mess up off course but to be on the safe side when you go into maintenance mode you should click the 'Backup System' button on the ms.html page. A backup contains the complete updates folder including the bin files and the database file.

#### Want to rename your app ?

You may have a good reason to rename your app like after adding some functionality but be aware of this...
When an ESP8266 asks for an update it sends its md5 and with that md5 we do a lookup in the versions table to find the appname to download. 
And when you change the name of the bin file it will not be found.

So when you decide to rename your app you need to change the app names on the Manage Versions page mv.html for all previous versions of your app. 

To let the tool accept changes in the App Name field on the Manage Versions screen you enable the option app_renaming on the Manage Settings screen.

Do your renaming and disable it afterwards so you do not change an appname by accident.

## ESP8266 code requirements

In the folder inos you find basic examples. The examples contain as little code as possible so it is easy to see what basic things you could add in your app.

A 'friendly' example is demoApp_1.ino. It starts in full mode.

A 'less friendly' example is demoApp_2.ino. It starts in limited mode.

When you set up your apps like the examples :
- Accepted devices will always run in full mode and always be able to update.
- All devices will run in full mode when the limit_mode setting of the version is full_run but only accepted will update.
- Not acceptes devices will run in limited mode when the limit_mode setting of the version is limit_run and only accepted will run full and update.

What is not available in the examples and what you have to add :
- A WiFi manager
- A web page with links to the registration page (Example urls are created for you in the demo apps)
- The code of your app
- Use the chack on full and limited mode as you see it in the loop in more places in your app to make it more difficult to hack it.

#### The basic code used to get things working

I used code from https://arduino-esp8266.readthedocs.io/en/latest/ota_updates/readme.html to develop this application.

The "optional current version string here" comes in tool1.php as $\_SERVER[HTTP_X_ESP8266_VERSION] and is not used there. I use the md5 of the image which comes in as $\_SERVER[HTTP_X_ESP8266_SKETCH_MD5] to see which version this is in the versions table.

```
#include <ESP8266httpUpdate.h>

WiFiClient client;
t_httpUpdate_return ret = ESPhttpUpdate.update(client, "domain1.onthewifi.com", 80, "/updates/tool1.php", "optional current version string here");
switch(ret) {
    case HTTP_UPDATE_FAILED:
        Serial.println("[update] Update failed.");
        break;
    case HTTP_UPDATE_NO_UPDATES:
        Serial.println("[update] Update no Update.");
        break;
    case HTTP_UPDATE_OK:
        Serial.println("[update] Update ok."); // may not be called since we reboot the ESP
        break;
}
```

## Some background info

#### Background info for registration, accepting and email functionality

During registration the mac is registered with additional information like email address and a 10 character key in the requests table. The user receives the key which is needed when the user wants to update the email address on the rr.html page.

In the first 2 setups below the devices are accepted automatically. May come in handy when you expect a lot of registrations and want to accept them all. Remember that you can block unwanted entries later anyway.

1) When you have no mail confirmation configured and you bypass the 'registered' status the status field wil contain 'accepted'. Other setups below fill the status field with 'registered' after registration.

2) When you DO have mail confirmation configured and you bypass the 'registered' status the status field wil be 'registered' and after the user clicks te confirmation in the mail the status field is updated to 'accepted'.

There is an option to enable mail notifications for software downloads. The table has a field mailondownload and when this type of notification is enabled and the value of mailondownload ends with 'ok' a download notification is sent to the registered email address. In the first situation above mailondownload contains 'registeredautook'. In the second it contains 'registered' after registration and 'ok' after confirmation.

In next 2 situations below the devices are not accepted automatically so you use mr.html to accept them.

3) When you have no mail confirmation configured and you DO NOT bypass the 'registered' status the status field wil contain 'registered' after registration. The mailondownload contains 'registeredautook' after registration and does not change after you accepted it.

4) With the recomended settings you DO have mail confirmation configured and you DO NOT bypass the 'registered' status the status field contains 'registered' after registration. The mailondownload wil contain 'registered' after registration. After confirmation by the user mailondownload is updated to 'ok' and does not change after you accepted it.

A user can only update the email address with the registered key and when the status is registered or accepted. Blocked entries can not be updated.

After an update the status field is not changed. When mail confirmation is enabled the field mailondownload is changed to 'updated'. This blocks email notification of downloads for accepted devices but after the user confirms by a click in the mail the field is updated to 'ok'. After an email address update without mail confirmation enabled the mailondownload  is changed to 'updatedautook'.

When after some time an email address is not valid anymore you may receive non-delivery notifications in the mailbox of your application account. To stop that you can stop mailondownload on mr.html. Check the mailbox every now and then. Also look into the sent items folder because everything sent by the script stays in that folder and you may want to clean that up.

#### Background information ESP8266 download request processing.

An ESP8266 update request contains info including mac and md5 in a way like we did in the previous section. The md5 is used to find the app name in the versions table. When the md5 is not in the versions table there is no app name found and the request is logged in the unregistered table and no download takes place. (Never delete old versions) When an app name is found in the versions table the next step is to find the status for the mac. The status can be registered, accepted, blocked or the mac is not registered so the status is missing. When it is missing it is logged in the unregistered table and no download takes place. 

When the status is accepted or the version is on full_update we check if we are in maintenance mode. In maintenance mode we send a message that there is no new version available. When we are not in maintenance mode we check if there is a new version. When there is a new version we send the new version and else we send the message that there is no new version.

When the status is not accepted we just send a message. When the version is on full_run they receive a no update available and when the version is on limit_run they receive an error. After enough errors they will fall back to limited mode and stay there until they receive a new version or a message that there is no new version available.

With the 'Manage Downloads' page md.html you can check the downloads table to see which mac has which version.

With the 'Manage Unregistered' page mu.html you can check the download attempts by wrong md5 and wrong mac values.

When you configured to send mail after downloads an appropriate email is sent to the mail address that is registered with the mac address. You may change the content of these messages in the sendMail function in tool1.php itself.

## Notes and Tips

Just some notes and tips.....

#### Registration limit

The tool1.php allows requestors only 1 registration a day. This is to discourage people who want to pollute your database. There is no limit for requests from your network where this tool is running so you can register as many times as you want.

#### Email user and password on ms.html

The fields for the email user and password are only available on ms.html when you start the page from your network.

When someone gets/guesses your management password and starts ms.html the email username and password will not be there

#### Some sql commands

You may be interested in what the tables look like and what they contain from the command line.

List the tables : sqlite3 registrations.db ".tables"

Info on the versions table including field names : sqlite3 registrations.db ".mode columns" "pragma table_info(versions)"

Contents of the settings table : sqlite3 registrations.db ".mode columns" "select * from settings"

Check the data in the downloads : sqlite3 registrations.db ".mode columns" " select * from downloads order by date desc"


After 1st time testing after applicaton setup you may want to clear some tables before you go into production.

To clear a table you use : sqlite3 registrations.db "delete from table" and replace table by the name of the table you want to clear like :

sqlite3 registrations.db "delete from requests"

sqlite3 registrations.db "delete from versions"

sqlite3 registrations.db "delete from downloads"

sqlite3 registrations.db "delete from unregistered"

You can also delete selected entries by using a where clause like:

sqlite3 registrations.db "delete from requests where mac = '11:22:33:44:55:66'"

You may also simply delete the complete database file registrations.db. A new will be created for you as soon as a web page is used.
When you had registrations in your database for bin files these are gone too. Just click the 'Import Versions' on the mv.html page.

Remember the section 'Whoops you did delete a version which you should not' above.

#### Management password lost ?

sqlite3 registrations.db ".mode columns" "select * from settings where setting ='mgmtpwd'"

#### Monitor logging

To see log messages from tool1.php which does all the work in the background you can use : tail -f /var/log/apache2/error.log.

See the my_log statements in tool1.php. Maybe you want to add or remove some my_log logging. Maybe you want to disable the logging on ms.html.

#### Backup, backup and backup

'Last but not least' make sure you always have a recent backup of the complete folder so you are prepared for the crash that never happens.....

# Thanks for reading and success with this installation

# Setup

This Installation_Details.md contains :

- Install a Raspberry Pi OS
- Install sqlite, apache and php
- Make it available to the Internet
- Install this tool for http access
- Add https with self signed certificate
- Move the tool to https
- Get some public domain names
- Install Let’s Encrypt certificate
- Email Setup
- Add new domain names to the certificate
- Locked sqlite database

## Install a Raspberry Pi OS

There is plenty of info on the internet to set up a Raspberry Pi.

Like : https://www.raspberrypi.com/documentation/computers/getting-started.html

In short :
```
You need a Raspberry Pi 3,4,5,....
USB keyboard and mouse
HDMI screen which you can connect to to the Pi
Ethernet connection
uSD card 16 GB or more
```
Put the uSD card in your computer, not your Pi ;-)

Download the Raspberry Pi Imager from https://www.raspberrypi.com/software/

Install and run the software.
```
Click Choose Device and select your Pi
Click Choose OS and select Raspberry Pi OS (64-bit) with Desktop
Click Storage and select your uSD card
Next, click Next.
Click the Edit Settings button to open OS customisation.
On General you enter what you need.
On Services DISABLE SSH
On Options 'Enable Play a sound', 'Enable Eject media' and 'DISABLE Enable telemetry'
Click Save
```
Wait for the process to finish.

Put the uSD in the Pi and connect keyboard, mouse, screen and network

Connect power and watch the magic happen.

You may be asked several questions and it may start updating an wanting to reboot.

#### Some things I needed to do to get started after this

I had an issue with my very old tv with HDMI connector which did not show anything.
I needed to put the uSD card back in my computer and use notepad to edit config.txt which is on the uSD card.
I had to add a letter f in a line : dtoverlay=vc4-kms-v3d -- > dtoverlay=vc4-fkms-v3d

After startup start a terminal window and start 'sudo raspi-config' and find an option to enable vnc.
```
- sudo raspi-config
    display options > resolution	<-select something to enable the Pi to startup without screen.
    interface options > enable vnc
```
Make sure you have a vnc client like realvnc on your computer.

Use vnc to connecto to your Pi. Reboot (init 6) and connect again.

When this works shutdown your Pi ( init 0 ) and remove power.

Remove the screen and put the power back in.

Use vnc to connecto to your Pi.

When vnc does not work without screen put the uSD back in your computer and edit config.txt
Enable the next line : hdmi_force_hotplug=1

Now you should be able to boot and connect with vnc.

#### Configure fixed IP for the wired connection

I like to use the editor geany so I install it (when it is installed it will tell you )

- sudo apt install geany -y

Find the details of the wired ethernet connection :

- ifconfig | grep -e eth0 -A 2

Note the ip address after inet and the mac address after ether like 192.168.2.4 and dc:a6:32:87:44:44

Get the gateway address :

- ip route | grep default

Note the first ip address like 192.168.2.254

Fix network address in the Raspberry Pi

- sudo geany /etc/dhcpcd.conf :

This is the first time I use geany so I want to fix some settings :

Select : Edit > Preferences > Editor > Indentation > remove check for 'Detect width from file' and select spaces Spaces

Close geany and start geany again for /etc/dhcpcd.conf

- sudo geany /etc/dhcpcd.conf

At the end add the lines like but use the ip address values you found :
```
interface eth0
static ip_address=192.168.2.4/24
static routers=192.168.2.254
static domain_name_servers=192.168.2.254
```
Make a dhcp reservation for the mac and ip address in your isp router.
This ensures that the ip address is not given to another device on your network.

#### Enable root account and set password

- sudo vipw

Use cursors and DEL key to remove x from root

Press Ctrl-X and answer Y

For safety put a password on the root account

- sudo passwd root

#### Now you may enable ssh access :

- sudo raspi-config > interface options > enable ssh
    
Want root access over ssh ? ( I do because I need it to copy files with rcp )

- geany /etc/ssh/sshd_config &
```
Find this line: PermitRootLogin without-password.
Edit: PermitRootLogin yes.
Close and save file.
reboot with init 6
```
#### Some last initial things

Start a terminal as user pi

Fist things first

- sudo apt update
- sudo apt upgrade

Cleanup when you see "'sudo apt autoremove' to remove them."

- sudo apt autoremove

Install zip which we will use for backups

- sudo apt install geany zip unzip

# Install sqlite, apache and php

#### Install sqlite3 :

- sudo apt install sqlite3

Install optional desktop application sqlite browser :

- sudo apt install sqlitebrowser

#### Install apache :

- sudo apt install apache2

Start a web browser and browse to http://addressofyourpi/
You should see the Apache2 Debian Default Page.

#### Install php :

Add lsb_release command to get info on your linux.
- sudo apt install lsb-release

Show system info.
- lsb_release -a

Add a third-party PHP repository to be able to install any version of php :
(This generates two output files by tee commands. Also note the lsb_release command in the 2nd line) :
- curl https://packages.sury.org/php/apt.gpg | sudo tee /usr/share/keyrings/suryphp-archive-keyring.gpg >/dev/null
- echo "deb [signed-by=/usr/share/keyrings/suryphp-archive-keyring.gpg] https://packages.sury.org/php/ $(lsb_release -cs) main" | sudo tee /etc/apt/sources.list.d/sury-php.list
- sudo apt update
- sudo apt upgrade

Install php 8.1 or whatever version you like ( note the 2 modules for sqlite and apache2 ) :
- sudo apt install php8.1 php8.1-gd php8.1-sqlite3 php8.1-curl php8.1-zip php8.1-xml php8.1-mbstring php8.1-mysql php8.1-bz2 php8.1-intl php8.1-smbclient php8.1-imap php8.1-gmp php8.1-bcmath libapache2-mod-php8.1

Enable php in apache ( I installed 8.1 so I enable 8.1 ) :
- sudo a2enmod php8.1

There is a message that you need to restart apache :
- sudo service apache2 restart

To find the apache enabled php version you can : sudo a2query -m | grep php

It is good to have some more info on php like the name of your config file.

Locate your Documentroot folder in /etc/apache2/sites-available/000-default.conf:

I assume you find DocumentRoot /var/www/html

- sudo geany /var/www/html/my_info.php &
    ( use the & so you get your cursor back in the terminal window )
Type the next into the editor :
```
<?php
phpinfo();
?>
```
Save the file

Browse to http://addressofyourpi/my_info.php

Locate the next row :

Loaded Configuration File 	/etc/php/8.1/apache2/php.ini

And do a search for pdo_sqlite ( it should be in the section 'Additional .ini files parsed' )

## Make it available to the Internet

To give access to your site from the Internet you need to configure your router.

You need to tell your router it has to forward a port to a port on
the ip address of your Raspberry Pi.

Start your web browser and enter the ip address of your router like 192.168.2.254.

( Same ip address as the 'static routers' you entered in /etc/dhcpcd.conf )

After logon you look for something like an 'IP4 port forwarding' section somewhere.

In my router I have a 'LAN Settings' section and there I find 'Port Forwarding - IPv4'
On that page I have 2 secions 'Port Forwarding - IPv4' like the page name and
'Application Configuration'.

To create a port forwarding I open 'Port Forwarding - IPv4'.
At the bottom I can create a new item.
I could type the address of my Raspberry Pi and select the Application named HTTP.
This would forward port 80 from the internet to port 80 of my Raspberry Pi
I could do the same for the HTTPS application to forward port 433 to 433 on the Pi.
This may be oke for you however....

However I already forwarded HTTP to somewhere else so I had to......
....Open 'Application Configuration' and create a new application.
Give it a name like Updates and create a TCP rule with two 'ranges' of ports.
Start and end like 83 to 83 and start and end 80 and 80
Now I can set up a forwarding to the ip address of the Pi using Updates.

I use the standard HTTPS also for something else so...
I added a TCP rule to Updates with two 'ranges' of ports.
Start and end like 445 to 445 and start and end 443 and 443

To check if everything works so far....

Find your public IPv4 address on a site like : https://www.whatismyip.com/

When you have standard forwarding
Start a web browser and browse to http://yourpublicIPv4/
You should see the Apache2 Debian Default Page.

When you forwarded an other port like 83 include the port number :
Start a web browser and browse to http://yourpublicIPv4:83/
You should see the Apache2 Debian Default Page.

( Https access does not work yet because we did not configure https yet.)
    
## Install this tool for http access

Copy the folder 'updates' with all application files and subfolders to /var/www/html/ so you have the folder /var/www/html/updates

Next we put some basic security for http access in place.
This will block access to all files except the files mentioned in it.

- sudo cp /var/www/html/updates/.htaccess_HTTP_only /var/www/html/updates/.htaccess

- sudo chown www-data: /var/www/html/updates - R

- add the next to (the very end) of your /etc/apache2/sites-available/000-default.conf to enable the .htaccess file
```
<Directory /var/www/html/updates>
        AllowOverride All
</Directory>
```
- sudo service apache2 restart

Browse to http://addressofyourpi/updates/tool1.php
Expect to see a message like : This is an ESP8266 only updater!

The next page should show the settings page ( default password : hi )
Browse to http://addressofyourpi/updates/ms.html

Browse to files you should have no access to like :
Browse to http://addressofyourpi/updates/registrations.db
Browse to http://addressofyourpi/updates/create_backups.sh
Browse to http://addressofyourpi/updates/blabla.html

You could do the same via http://yourpublicIPv4/

## Enable https with self signed certificate

First we will test with self signed certificates.
We will replace these later by free Let's Encrypt certificates

Install ssl :

- sudo apt install openssl

Enable ssl module :

- sudo a2enmod ssl

( To show apache installed modules you can : sudo a2query -m | sort )

Create folder to hold the certificate :

- sudo mkdir -p /etc/apache2/ssl

Prepare to answer the next questions for the next 'sudo openssl' command.
I answered questions 4,5 and 6 with Private.
I filled in personal details where you see xxxxxxxxxx
```
Country Name (2 letter code) [AU]:xxxxxxxxxx
State or Province Name (full name) [Some-State]:xxxxxxxxxx
Locality Name (eg, city) []:xxxxxxxxxx
Organization Name (eg, company) [Internet Widgits Pty Ltd]:Private
Organizational Unit Name (eg, section) []:Private
Common Name (e.g. server FQDN or YOUR name) []:Private
Email Address []:xxxxxxxxxx@xxxxxxxxxx.xxxxxxxxxx
```
Generate a certificate valid for 3650 days :: 10 years

- sudo openssl req -x509 -nodes -days 3650 -newkey rsa:4096 -keyout /etc/apache2/ssl/apache.key -out /etc/apache2/ssl/apache.crt

Configure apache for certificate :

- sudo geany /etc/apache2/sites-available/default-ssl.conf &

Change :
```
SSLCertificateFile /etc/ssl/certs/ssl-cert-snakeoil.pem
SSLCertificateKeyFile /etc/ssl/private/ssl-cert-snakeoil.key
```
to :
```
SSLCertificateFile /etc/apache2/ssl/apache.crt
SSLCertificateKeyFile /etc/apache2/ssl/apache.key
```
Save file

Enable default-ssl.conf :

- sudo a2ensite default-ssl.conf
- sudo service apache2 restart

Start a webbrowser to see if https works : https://addressofyourpi/my_info.php
(You will need to accept a risk because it is a self signed certificate)

## Move the tool to https

Put the virtual host file in place :

- sudo cp /var/www/html/updates/updates.conf /etc/apache2/sites-available/updates.conf

There is an ip address 192.168.2.13 which you need to change to that of your pi.

- sudo geany /etc/apache2/sites-available/updates.conf &
```
<VirtualHost *:443>
    DocumentRoot /var/www/html/updates
    ServerName 192.168.2.13
    ServerAlias localhost
```
Activate the virtual host after changing the address and saving the file :

- sudo a2ensite updates.conf

- sudo service apache2 restart

Browse to http://addressofyourpi/updates/tool1.php
Still expect to see a message like : This is an ESP8266 only updater!

The next page should not work
( accept the risk )
Browse to https://addressofyourpi/updates/ms.html

And now without the updates in the path :

Browse to the https page https://addressofyourpi/tool1.php
Expect to see a message like : This is an ESP8266 only updater!

The next page should work ( default password : hi )
Browse to https://addressofyourpi/ms.html
( accept the risk )

You should already have port forwarding for https (port 443) in place.

Find your public IPv4 address on a site like https://www.whatismyip.com/

Browse to https://yourpublicIPv4/ms.html
( accept the risk )

OR..............

When you forwarded an onther port like 445 :
Browse to https://yourpublicIPv4:445/ms.html
( accept the risk )

## Get some public domain names

Your public ip address may change so you need to get (free) public domain main names.

Some free providers are no-ip, freedns and duckdns. There are others.
Just make sure to get at least one name from two providers so you have 2 names.

Your app needs to try to update from 2 or more domain names. 
When one name does not work anymore because your free domain name provider stops the app can still update using the other.
You can get a new domain name, create a new version which also updates from that domain name and let it be downloaded by all apps out there.
This is why you need at least a name from 2 disfferent providers.

Many providers require you to run client software to regularly refresh your registration.
I am not sure but I think duckdns does not require you to.
freedns requires you to logon to their site once a year and no-ip sends a verification mail every month.

( There are routers having the update client functionality.
I prefer to run an update client on my Raspberry Pi.
When I change internet provider I may need to configure it in that new router which may not support it. )

The hard work is done when you have at least 2 domain names and update clients in place.

From now it is easy.

To explain further I assume you managed to get 2 domain names like :
```
domain1.onthewifi.com
domain2.thehomeserver.net
```
Add them to the top of your config file.

- sudo geany /etc/apache2/sites-available/updates.conf &
```
<VirtualHost *:443>
    DocumentRoot /var/www/html/updates
    ServerName 192.168.2.13
    ServerAlias localhost
    ServerAlias domain1.onthewifi.com
    ServerAlias domain2.thehomeserver.net
:
:
```
and save the file and activate it.

- sudo service apache2 restart

Browse to https://domain1.onthewifi.com/ms.html
( accept the risk )

OR..............

When you forwarded an onther port like 445 :
Browse to https://domain1.onthewifi.com:445/ms.html
( accept the risk )

Also check if domain2.thehomeserver.net works.
    
## Install Let’s Encrypt certificate

You do not want customers to accept a risk when they access your site.

Let’s Encrypt https://letsencrypt.org/ gives you a free trusted certificate so you do not have to
accept the risk every time you access your site.
Also looks more professional when a customer uses rr.html to register

It is very easy to add the Let's Encrypt certificate to apache.

- sudo apt install python3-certbot-apache

Show there are no certificates yet :

- sudo certbot certificates

Grab certificate and install it in apache configurations files which have domainnames in them.

#### ( When the next step fails skip to 'certbot issue due to different port forwarding' below )

- sudo certbot --apache

```
Saving debug log to /var/log/letsencrypt/letsencrypt.log
Plugins selected: Authenticator apache, Installer apache
Enter email address (used for urgent renewal and security notices)
 (Enter 'c' to cancel): <--- Here you enter your email address like yourname@yourprovider.com

- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
Please read the Terms of Service at
https://letsencrypt.org/documents/LE-SA-v1.3-September-21-2022.pdf. You must
agree in order to register with the ACME server. Do you agree?
- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
(Y)es/(N)o: <--- Y

- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
Would you be willing, once your first certificate is successfully issued, to
share your email address with the Electronic Frontier Foundation, a founding
partner of the Let's Encrypt project and the non-profit organization that
develops Certbot? We'd like to send you email about our work encrypting the web,
EFF news, campaigns, and ways to support digital freedom.
- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
(Y)es/(N)o: <--- N
Account registered.

Which names would you like to activate HTTPS for?
- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
1: domain1.onthewifi.com
2: domain2.thehomeserver.net
- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
Select the appropriate numbers separated by commas and/or spaces, or leave input
blank to select all options shown (Enter 'c' to cancel):
Requesting a certificate for domain1.onthewifi.com and domain2.thehomeserver.net
Performing the following challenges:
http-01 challenge for domain1.onthewifi.com
http-01 challenge for domain2.thehomeserver.net
Waiting for verification...
Cleaning up challenges
Deploying Certificate to VirtualHost /etc/apache2/sites-enabled/updates.conf
Deploying Certificate to VirtualHost /etc/apache2/sites-enabled/updates.conf
Added an HTTP->HTTPS rewrite in addition to other RewriteRules; you may wish to check for overall consistency.
Redirecting vhost in /etc/apache2/sites-enabled/000-default.conf to ssl vhost in /etc/apache2/sites-enabled/updates.conf

- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
Congratulations! You have successfully enabled https://domain1.onthewifi.com and
https://domain2.thehomeserver.net
- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

IMPORTANT NOTES:
 - Congratulations! Your certificate and chain have been saved at:
   /etc/letsencrypt/live/domain1.onthewifi.com/fullchain.pem
   Your key file has been saved at:
   /etc/letsencrypt/live/domain1.onthewifi.com/privkey.pem
   Your certificate will expire on <some date>. To obtain a new or
   tweaked version of this certificate in the future, simply run
   certbot again with the "certonly" option. To non-interactively
   renew *all* of your certificates, run "certbot renew"
 - If you like Certbot, please consider supporting our work by:

   Donating to ISRG / Let's Encrypt:   https://letsencrypt.org/donate
   Donating to EFF:                    https://eff.org/donate-le
```

Now let's just check some things :

Show certificate :

- sudo certbot certificates

- sudo cat /etc/apache2/sites-available/updates.conf

Should end with :
```
Include /etc/letsencrypt/options-ssl-apache.conf
SSLCertificateFile /etc/letsencrypt/live/domain1.onthewifi.com/fullchain.pem
SSLCertificateKeyFile /etc/letsencrypt/live/domain1.onthewifi.com/privkey.pem
</VirtualHost>
```
There is a file included and there are 2 lines for the certificate.

To see that you do not need to renew by hand see the update job :

- sudo cat /etc/cron.d/certbot
```
# /etc/cron.d/certbot: crontab entries for the certbot package
#
# Upstream recommends attempting renewal twice a day
#
# Eventually, this will be an opportunity to validate certificates
# haven't been revoked, etc.  Renewal will only occur if expiration
# is within 30 days.
#
# Important Note!  This cronjob will NOT be executed if you are
# running systemd as your init system.  If you are running systemd,
# the cronjob.timer function takes precedence over this cronjob.  For
# more details, see the systemd.timer manpage, or use systemctl show
# certbot.timer.
SHELL=/bin/sh
PATH=/usr/local/sbin:/usr/local/bin:/sbin:/bin:/usr/sbin:/usr/bin

0 */12 * * * root test -x /usr/bin/certbot -a \! -d /run/systemd/system && perl -e 'sleep int(rand(43200))' && certbot -q renew
```

Browse to https://domain1.onthewifi.com/ms.html
( no need to accept the risk )

You can inspect the certificate in your browser by clicking on the lock and click some more to see the details.

## certbot issue due to different port forwarding

When you forwarded other ports than 80 to 80 and 443 to 443 cerbot fails.

I had this issue because I forwarded port 80 and 443 to another Raspberry Pi where I run apache.
And I forwarded port 83 and 445 to 80 and 443 to this Raspberry Pi.
The other Pi was already getting the certificate so I decided to use these.

It failed like :
```
sudo certbot --apache

Saving debug log to /var/log/letsencrypt/letsencrypt.log
Plugins selected: Authenticator apache, Installer apache

Which names would you like to activate HTTPS for?
- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
1: domain1.onthewifi.com
2: domain1.thehomeserver.net
- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
Select the appropriate numbers separated by commas and/or spaces, or leave input
blank to select all options shown (Enter 'c' to cancel):
Requesting a certificate for domain1.onthewifi.com and domain2.thehomeserver.net
Performing the following challenges:
http-01 challenge for domain1.onthewifi.com
http-01 challenge for domain2.thehomeserver.net
Waiting for verification...
Challenge failed for domain domain1.thehomeserver.net <-- failure
Challenge failed for domain domain2.onthewifi.com <-- failure
http-01 challenge for domain1.thehomeserver.net
http-01 challenge for domain2.onthewifi.com
Cleaning up challenges
Some challenges have failed. <-- failure

IMPORTANT NOTES:
 - The following errors were reported by the server:

   Domain: domain1.thehomeserver.net
   Type:   unauthorized
   Detail: PublicIPv4: Invalid response from
   https://domain1.thehomeserver.net/.well-known/acme-challenge/VXQx3GpTBTbvIpkJylRek6BOY0PtVQNgePvN-OkopcM:
   404

   Domain: domain2.onthewifi.com
   Type:   unauthorized
   Detail: PublicIPv4: Invalid response from
   https://domain2.onthewifi.com/.well-known/acme-challenge/G9EdU9p3-Y5cZnhHCQVvSYPUtA3e-Z6jzmRweHofp_4:
   404

   To fix these errors, please make sure that your domain name was
   entered correctly and the DNS A/AAAA record(s) for that domain
   contain(s) the right IP address.
```

#### Use the certificate of the other Pi.
    
I logged on to the other Pi

On the other Pi there is a folder with the certificate files I need :

- sudo ls -l /etc/letsencrypt/live/ -R

This will give you some files in  a subfolder like domain1.onthewifi.com which is one of the domain names.

In the README I found that I need fullchain.pem and privkey.pem

So I copied them to the Pi where I need them for the tool.

rcp uses ssh and I enabled that for root somewhere above.
Replace domain1.onthewifi.com with the right name and 192.168.2.13 with the ip of your Pi.

- sudo rcp /etc/letsencrypt/live/domain1.onthewifi.com/fullchain.pem root@192.168.2.13:/etc/apache2/ssl/fullchain.pem
- sudo rcp /etc/letsencrypt/live/domain1.onthewifi.com/privkey.pem   root@192.168.2.13:/etc/apache2/ssl/privkey.pem

Inserted them to the end of my updates.conf like :

- sudo cat /etc/apache2/sites-available/updates.conf

Last lines :

```
SSLCertificateFile /etc/apache2/ssl/fullchain.pem
SSLCertificateKeyFile /etc/apache2/ssl/privkey.pem
</VirtualHost>
```
Note that I do not have the next line in there :
Include /etc/letsencrypt/options-ssl-apache.conf

Browse to https://domain1.onthewifi.com/ms.html
( no need to accept the risk )

You can inspect the certificate in your browser by clicking on the lock and click some more to see the details.

Certificate renewal, do not forget !!!!

The certificate gets renewed every now and then and needs to be copied over.

On the Raspberry Pi where the certificate is renewed I need passwordless access to the Pi with the tool.

On the Pi which does the renewal of the certificate you logon as root ( sudo -i ) and :

Become root :

- sudo -i

Generate public-private key pair while logged on as root

- ssh-keygen
```
Generating public/private rsa key pair.
Enter file in which to save the key (/root/.ssh/id_rsa):
Enter passphrase (empty for no passphrase):
Enter same passphrase again:
Your identification has been saved in /root/.ssh/id_rsa
Your public key has been saved in /root/.ssh/id_rsa.pub
The key fingerprint is:
:
:
```
Now as root copy the key to the other Pi with the tool

- ssh-copy-id -i ~/.ssh/id_rsa.pub root@ipaddresswiththetool
```
/usr/bin/ssh-copy-id: INFO: Source of key(s) to be installed: "/root/.ssh/id_rsa.pub"
The authenticity of host 'ipaddresswiththetool (ipaddresswiththetool)' can't be established.
ECDSA key fingerprint is SHA256:................
Are you sure you want to continue connecting (yes/no/[fingerprint])? <-- yes
/usr/bin/ssh-copy-id: INFO: attempting to log in with the new key(s), to filter out any that are already installed
/usr/bin/ssh-copy-id: INFO: 1 key(s) remain to be installed -- if you are prompted now it is to install the new keys
root@ipaddresswiththetool's password: <-- enter the root password

Number of key(s) added: 1

Now try logging into the machine, with:   "ssh 'root@ipaddresswiththetool'"
and check to make sure that only the key(s) you wanted were added.
```
Still as root, just check if you can get the hostname of the other Pi with the tool :

- ssh 'root@ipaddresswiththetool' hostname

Should give the name of the other Raspberry Pi

Still as root, on the Raspberry Pi that gets the certificates as root create a script like :

- geany /root/sendcertificate.sh &
```
#!/bin/bash
rcp /etc/letsencrypt/live/domain1.onthewifi.com/fullchain.pem root@192.168.2.13:/etc/apache2/ssl/fullchain.pem
rcp /etc/letsencrypt/live/domain1.onthewifi.com/privkey.pem   root@192.168.2.13:/etc/apache2/ssl/privkey.pem
```
Save the file and make it executable :

- chmod +x sendcertificates.sh

and as root add a crontab job to run every monday :

- crontab -e

Add the next line to the end of the file :
```
30 */12 * * * /root/sendcertificate.sh >/dev/null 2>&1
```
This will run the job every day 30 minutes after the certificate renewal attempt so you will have a valid certificate on the other Pi with the tool.

## Email Setup

Email is disabled by default in the tool. It is recommended to use email but you can skip this section and come back to this later.

The tool needs python version 3 to be installed so it can run amail.py for you.

- python --version

When you do not have a python 3 version google on 'Raspberry Pi install python' and you will find how to install it.

You need to create a gmail account which you will use for this tool only, enable two factor authentication and generate an application password because.....

Gmail does not allow a script like amail.py to use a plain username and password to send emails. 
```
So..
1) You create a new gmail account with a password.
2) Configure two factor authentication for this new gmail account. 
(Two factor authentication means that when you login to the webmail of the gmail account you will receive a notification on the  mobile or tablet on which you have an email program to receive email for that account. You have to click on that notification to confirm it is you who tries to login.)
3) Create an application password
```
Make sure the mail script can run :

- chmod +x amail.py

Send a simple email to yourself 
-./amail.py -rcv youremailaddress -snd yourapplicationaccount -pwd yourapppwd 

(Use './amail.py -h' or 'python3 ./amail.py -h' to see some explanation.)

I do not remember that I added any python packages but when in the end things do not work check the import statements in amail.py and add the missing packages. Also make sure email.py is executable : chmod +x email.py

## Add new domain names to the certificate

You may run into the situation where one of the domain name providers is not available anymore and you loose a domain name.

This is when you need to get a new name somewhere and get that into your /etc/apache2/sites-available/updates.conf and also into the certificate.

To get your new domain name domain3.overthemoon.net in your certificate :

I did not need to yet but when I have I will update this section. WHen you figured it out please let me know how to do this.

## Locked sqlite database

I never had the problem of a locked databasse but ran into these tips one day :

```
Restart Apache

Sudo systemctl restart apache2.service
This can sometimes resolve the issue if the problem is with the application itself.

Using The Shell

If the problem persists, try using the SQLite command line shell to access the database directly.
This can be done by using the “sqlite3 registrations.db” command.
Try running the “PRAGMA busy_timeout 100” command.
This can help the database handle the lock more gracefully, allowing you to access it even if it is currently in use by another process.
If this does not work, you may need to try manually unlocking the database.
This can be done by using the “PRAGMA lock_status” command.
This shows you the current state of the locks on the database.
If the database is indeed locked, you can try using the “PRAGMA busy_timeout 100” and “PRAGMA lock_timeout 100” commands to set a timeout for the lock, after which it will be released automatically.
```
When this does not help you need to restore a backup of registrations.db from a zip or tar file you created before.

# Thanks for reading and success with this installation 

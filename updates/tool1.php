<?php

// search on '// ----' to locate sections in this code

// ------------------------------------------------------- Read ini file

// Where am I installed ?
$currentFolder = __DIR__;
$my_db             = $currentFolder . '/registrations.db';
$my_bins           = $currentFolder .'/bins';

// ------------------------------------------------- Initialise database

initializeDatabase();

// Start a single database connection we close at the end of processing

$db = new SQLite3($my_db);

$mgmtpwd           = getSettingsField('mgmtpwd');
$mylaptopip        = getSettingsField('mylaptopip');
$maintenancemode   = getSettingsField('maintenancemode')    == "true";
$logging           = getSettingsField('logging')            == "true";
$bypassregistered  = getSettingsField('bypassregistered')   == "true";
$keylength         = getSettingsField('keylength');
$emailuser         = getSettingsField('emailuser');
$emailpwd          = getSettingsField('emailpwd');
$mail_confirmation = getSettingsField('mail_confirmation')  == "true";
$mail_on_download  = getSettingsField('mail_on_download')   == "true";
$mail_key          = getSettingsField('mail_key')           == "true";

// ------------------------------------------ Public IP addresses

// In maintenance nothing works for anybody but....you
// There is an intrinsic exception for your public ip.
// In tool1.ini is also an exception for 'mylaptopip'

$myPublicIP4 = getPublicIPv4();

// Used to determine who accesses so we can compare with the exceptions
$publicip = $_SERVER['REMOTE_ADDR']; // the public ip of the client

$itsme = ( $publicip == $myPublicIP4 ) || (stripos($mylaptopip,$publicip) != false );

my_log("itsme : >" . $itsme . "<");

// ------------------- Check if we need the web form OR download section

    if (isset($_GET['function']) || isset($_POST['function'])) {

        if (isset($_GET['function'])) {
            $functionName = $_GET['function'];
        } else {
            $functionName = $_POST['function'];
        }

// --------------------------------------------- Handle Maintenance Mode

        if ( ( $maintenancemode) && ( ! $itsme ) ) {
//        if ( $maintenancemode) {  // I use this to test real maintenenace mode for myself
            my_log("------------ '$functionName' maintenance mode ------------ $publicip");
            go_back("Sorry","Maintenance mode is active",5);
        }

// ------------------------------------------ Web form section start ---

        switch ($functionName) {
// ------------------------------------------------------ User functions
            case "register":
                $mac = strtoupper($_POST['mac']);
                $email = $_POST['email'];
                $supplier = $_POST['supplier'];
                $appname = $_POST['appname'];
/*

 someone may try to register a lot of mac addresses so we allow 1 registration per day

 we allow ourselves to make as many as we want via our public ip address

 I also put in the ip address of my laptop so I can test direct to my webserver without going over the internet

*/
                $date = date("Y-m-d"); // get date yyyy-mm-dd
                $lastdate = substr(getLastDate($publicip),0,10);
                $title="<title>Registration...</title>";
                if ( ( $date != $lastdate) || ( $itsme ) ) {
                    $result = register($mac, $publicip, $email, $appname, $supplier);
                    echo $title . $result;
                } else {
                    echo $title . go_back("Sorry"," only 1 registration per day allowed!",5);
                }
                break;

            case "update":
                $mac = strtoupper($_POST['mac']);
                $email = $_POST['email'];
                $key = $_POST['key'];
                $appname = $_POST['appname'];
                $title="<title>Update...</title>";
                $result = update($mac, $publicip, $email, $key);
                echo $title . $result;
                break;

            case "confirmRegistration":
                $mac = $_GET['mac'];
                $key = $_GET['key'];
                $result = confirmRegistration($mac, $key);
                echo $result;
                break;

            case "confirmUpdate":
                $mac = $_GET['mac'];
                $key = $_GET['key'];
                $result = confirmUpdate($mac, $key);
                echo $result;
                break;

            case "Get Key Info":
                $mac = strtoupper($_POST['mac']);
                $email = $_POST['email'];
                $title="<title>Key Info...</title>";
                $result = getKeyInfo($mac, $email);
                echo $title . $result;
                break;
// ------------------------------------------------ Management functions

            case "getRequests":
                $pwd = $_GET['pwd'];
                $status = $_GET['status'];
                $filter = $_GET['filter'];
                $reverse = $_GET['reverse'];
                $filterValue = $_GET['filterValue'];
                $orderby = $_GET['orderby'];
                $order = $_GET['order'];
                $offset = $_GET['offset'];
                $count = $_GET['count'];
                $result = getRequests($offset, $count, $pwd, $filter, $filterValue,$status, $reverse, $orderby, $order);
// --- Background function so return data
                echo $result;
                break;

            case "Register Selected":
            case "Accept Selected":
            case "Block Selected":
            case "Update Notes":
                if (isset($_POST['selectedRows']) && isset($_POST['pwd'])) {
                    $selectedRows = $_POST['selectedRows'];
                    $macs = $_POST['macs'];
                    $newnotes = $_POST['newnotes'];
                    $pwd = $_POST['pwd'];
                    $result = manageRequests($functionName, $selectedRows, $macs, $newnotes, $pwd);
                    echo go_back($functionName, $result);
                } else {
                    echo go_back($functionName, "ERROR : Missing password or no selection made!");
                }
                break;

            case "Stop Mail":
                if (isset($_POST['selectedRows']) && isset($_POST['pwd'])) {
                    $selectedRows = $_POST['selectedRows'];
                    $pwd = $_POST['pwd'];
                    $result = updatemailondownload($selectedRows, $pwd, 'stopped');
                    echo go_back($functionName, $result);
                } else {
                    echo go_back($functionName, "ERROR : Missing password or no selection made!");
                }
                break;

            case "UnStop Mail":
                if (isset($_POST['selectedRows']) && isset($_POST['pwd'])) {
                    $selectedRows = $_POST['selectedRows'];
                    $pwd = $_POST['pwd'];
                    $result = updatemailondownload($selectedRows, $pwd, 'startedok');
                    echo go_back($functionName, $result);
                } else {
                    echo go_back($functionName, "ERROR : Missing password or no selection made!");
                }
                break;

            case "Delete Blocked":
                if (isset($_POST['selectedRows']) && isset($_POST['pwd'])) {
                    $selectedRows = $_POST['selectedRows'];
                    $pwd = $_POST['pwd'];
                    $result = deleteBlocked($selectedRows, $pwd);
                    echo go_back($functionName, $result);
                } else {
                    echo go_back($functionName, "ERROR : Missing password or no selection made!");
                }
                break;

            case "getUnregistered":
                $pwd = $_GET['pwd'];
                $filter = $_GET['filter'];
                $reverse = $_GET['reverse'];
                $filterValue = $_GET['filterValue'];
                $orderby = $_GET['orderby'];
                $order = $_GET['order'];
                $offset = $_GET['offset'];
                $count = $_GET['count'];
                $result = getUnregistered($offset, $count, $pwd, $filter, $filterValue, $reverse, $orderby, $order);
// --- Background function so return data
                echo $result;
                break;


            case "Delete Unregistered":
                if (isset($_POST['selectedRows']) && isset($_POST['pwd'])) {
                    $selectedRows = $_POST['selectedRows'];
                    $pwd = $_POST['pwd'];
                    $result = deleteUnregistered($selectedRows, $pwd);
                    echo go_back($functionName, $result);
                } else {
                    echo go_back($functionName, "ERROR : Missing password or no selection made!");
                }
                break;

            case "getSystemInfo":
                $pwd = $_GET['pwd'];
                $result = getSystemInfo($pwd);
// --- Background function so return data
                echo $result;
                break;

// ------------------------------------------------- Settingss functions

            case "getSettings":
                $pwd = $_GET['pwd'];
                $result = getSettings($pwd);
// --- Background function so return data
                echo $result;
                break;

            case "Save Settings":
                if (isset($_POST['pwd'])) {
                    $settings = $_POST['settings'];
                    $values = $_POST['values'];
                    $pwd = $_POST['pwd'];
                    $result = saveSettings($settings, $values, $pwd);
                    echo go_back($functionName, $result);
                } else {
                    echo go_back($functionName, "ERROR : Missing password or some error made!");
                }
                break;

            case "Backup System":
                if (isset($_POST['pwd'])) {
                    $settings = $_POST['settings'];
                    $values = $_POST['values'];
                    $pwd = $_POST['pwd'];
                    $result = saveSettings($settings, $values, $pwd);
                    $result = backupSystem($pwd);
                    echo go_back($functionName, $result, 20);
                } else {
                    echo go_back($functionName, "ERROR : Missing password or some error made!");
                }
                break;

// ------------------------------------------------- Versions functions

            case "Import Versions":
                if (isset($_POST['pwd'])) {
                    $pwd = $_POST['pwd'];
                    $result = importVersions($pwd);
                    echo go_back($functionName, $result);
                } else {
                    echo go_back($functionName, "ERROR : Missing password or no selection made!");
                }
                break;

            case "getVersions":
                $pwd = $_GET['pwd'];
                $filter = $_GET['filter'];
                $reverse = $_GET['reverse'];
                $filterValue = $_GET['filterValue'];
                $orderby = $_GET['orderby'];
                $order = $_GET['order'];
                $offset = $_GET['offset'];
                $count = $_GET['count'];
                $result = getVersions($offset, $count, $pwd, $filter, $filterValue, $reverse, $orderby, $order);
// --- Background function so return data
                echo $result;
                break;

            case "Update Versions":
                if (isset($_POST['selectedRows']) && isset($_POST['pwd'])) {
                    $selectedRows = $_POST['selectedRows'];
                    $md5s = $_POST['md5s'];
                    $newlimit_modes = $_POST['newlimit_modes'];
                    $newappnames = $_POST['newappnames'];
                    $newnotes = $_POST['newnotes'];
                    $pwd = $_POST['pwd'];

//        $string=implode(",",$newlimit_modes);
//        my_log("newlimit_modes : " . $string);

                    $result = updateVersions($selectedRows, $md5s, $newappnames, $newlimit_modes, $newnotes, $pwd);
                    echo go_back($functionName, $result);
                } else {
                    echo go_back($functionName, "ERROR : Missing password or no selection made!");
                }
                break;

            case "Delete Versions":
                if (isset($_POST['selectedRows']) && isset($_POST['pwd'])) {
                    $selectedRows = $_POST['selectedRows'];
                    $md5s = $_POST['md5s'];
                    $pwd = $_POST['pwd'];
                    $result = deleteVersions($selectedRows, $md5s, $pwd);
                    echo go_back($functionName, $result);
                } else {
                    echo go_back($functionName, "ERROR : Missing password or no selection made!");
                }
                break;

// ------------------------------------------------- Downloads functions

            case "getDownloads":
                $pwd = $_GET['pwd'];
                $filter = $_GET['filter'];
                $reverse = $_GET['reverse'];
                $filterValue = $_GET['filterValue'];
                $orderby = $_GET['orderby'];
                $order = $_GET['order'];
                $offset = $_GET['offset'];
                $count = $_GET['count'];
                $result = getDownloads($offset, $count, $pwd, $filter, $filterValue, $reverse, $orderby, $order);
// --- Background function so return data
                echo $result;
                break;

            case "Delete Downloads":
                if (isset($_POST['selectedRows']) && isset($_POST['pwd'])) {
                    $selectedRows = $_POST['selectedRows'];
                    $macs = $_POST['macs'];
                    $datedownloads = $_POST['datedownloads'];
                    $pwd = $_POST['pwd'];
                    $result = deleteDownloads($selectedRows, $datedownloads, $macs, $pwd);
                    echo go_back($functionName, $result);
                } else {
                    echo go_back($functionName, "ERROR : Missing password or no selection made!");
                }
                break;

// -------------------------------------------- Default action

            default:
                echo go_back($functionName, "Error: Invalid function!",10);
                my_log("Error: Invalid function $functionName");
        }

// -------------------------------------------- Web form section end ---

    } else {

// ------------------------------------------ Download section start ---

// Missing parameter named 'function' so this seems to be an update request

        my_log(" ------------ Download Requested ------------ ");

/*

I had some trouble to get things up and running....

Code from https://arduino-esp8266.readthedocs.io/en/latest/ota_updates/readme.html#http-server
uses mixed case parameter names in $_SERVER

 The next saves some interesting info in the /var/log/apache2/error.log :

 foreach ($_SERVER as $parm => $value)  my_log("$parm = '$value");

And it shows that all parameters are upper case.

HTTP_HOST = 'publicdomainname:portnumber
HTTP_USER_AGENT = 'ESP8266-http-Update
HTTP_CONNECTION = 'close
HTTP_X_ESP8266_CHIP_ID = '10024254
HTTP_X_ESP8266_STA_MAC = '84:F3:EB:98:F5:3E
HTTP_X_ESP8266_AP_MAC = '86:F3:EB:98:F5:3E
HTTP_X_ESP8266_FREE_SPACE = '1789952
HTTP_X_ESP8266_SKETCH_SIZE = '305744
HTTP_X_ESP8266_SKETCH_MD5 = 'f8ee9e41e20829e13a0a5380eb8a8577
HTTP_X_ESP8266_CHIP_SIZE = '16777216
HTTP_X_ESP8266_SDK_VERSION = '2.2.2-dev(38a443e)
HTTP_X_ESP8266_MODE = 'sketch
HTTP_X_ESP8266_VERSION = 'optional current version string here

*/
        header('Content-type: text/plain; charset=utf8', true);
/*

 We need to figure out $md5, $mac and $macstatus ( requests, accepted, blocked or missing )

 In the README.md are examples on how to use download test from a browser.
 Here we check if this is a test from a web browser url

*/

        if (isset($_GET['md5'])) {
// This is someone testing from a browser url
            $md5 = $_GET['md5'];
            $mac = strtoupper($_GET['mac']);
            $macstatus = getRequestsField( $mac,'status');
        } else {
// This might be a real download request

            if(!check_header('HTTP_USER_AGENT', 'ESP8266-http-Update')) {
                // Close the single database connection
                $db->close();
                header($_SERVER["SERVER_PROTOCOL"].' 403 Forbidden', true, 403);
                echo "This is an ESP8266 only updater!";
                exit();
            }

            if(
                !check_header('HTTP_X_ESP8266_STA_MAC') ||
                !check_header('HTTP_X_ESP8266_AP_MAC') ||
                !check_header('HTTP_X_ESP8266_FREE_SPACE') ||
                !check_header('HTTP_X_ESP8266_SKETCH_SIZE') ||
                !check_header('HTTP_X_ESP8266_SKETCH_MD5') ||
                !check_header('HTTP_X_ESP8266_CHIP_SIZE') ||
                !check_header('HTTP_X_ESP8266_SDK_VERSION')
            ) {
                // Close the single database connection
                $db->close();
                header($_SERVER["SERVER_PROTOCOL"].' 403 Forbidden', true, 403);
                echo "Error: Incomplete header for this is ESP8266 only updater!";
                exit();
            }
// this is an ESP8266 trying to download so let's check if the mac and md5 are in our database

            $md5 = $_SERVER['HTTP_X_ESP8266_SKETCH_MD5'];

// next find $mac status

            $macap  = $_SERVER['HTTP_X_ESP8266_AP_MAC'];
            $macsta = $_SERVER['HTTP_X_ESP8266_STA_MAC'];
            $macstatusap  = getRequestsField( $macap,'status');
            $macstatussta = getRequestsField( $macsta,'status');

            if ($macstatusap != 'missing') {
                $mac = $macap;
                $macstatus = $macstatusap ; // could be requests, accepted
             } else {
                $mac = $macsta;
                $macstatus = $macstatussta ; // could be requests, accepted, but also missing
             }
        }

// check the md5 by finding the limit_mode

        $limit_mode = getVersionsField($md5,'limit_mode'); // limited_run, full_run, full_update or missing

// Now we know $md5, $limit_mode (limited_run, full_run, full_update or missing) , $mac and $macstatus (registered, accepted, blocked or missing)

        if ($limit_mode == "missing") { 
            // md5 we do not know so we send an error (maybe someone used an hexeditor to modify the bin but managed to reach us anyway) 
            my_log("Note/update mac/md5 $mac $md5 in table unregistered");
            noteUnregistered($mac, $publicip, $md5);
            my_log("limit_mode : $limit_mode ; 500 ESP MAC not configured for updates");
            header($_SERVER["SERVER_PROTOCOL"].' 500 ESP MAC not configured for updates', true, 500);
        } else {

// We have a valid md5 with status $macstatus ( registered, accepted, blocked or missing )

            if ($macstatus == "missing") { 
                my_log("Note/update mac/md5 $mac $md5 in table unregistered");
                noteUnregistered($mac, $publicip, $md5);
            }
            
            if ( ($limit_mode == "full_update" ) || ($macstatus == "accepted") ) { // we may download
                
                if ( ( $maintenancemode) && ( ! $itsme ) ) {
//                if ( $maintenancemode) {  // I use this to test real maintenenace mode for myself
                    my_log("------------ maintenance mode ------------ $myPublicIP4 .. $publicip .. $mylaptopip");
                    header($_SERVER["SERVER_PROTOCOL"].' 304 Not Modified', true, 304);
                } else {
                    $app = getVersionsField($md5,'appname');
                    $localBinary = $GLOBALS["my_bins"] . "/$app";
                    if (! file_exists($localBinary) ) {
                        my_log("Whoopsie I am testing and renaming $localBinary");
                        header($_SERVER["SERVER_PROTOCOL"].' 304 Not Modified', true, 304);
                    } else {
                        my_log("Selected binary : $localBinary");

                        $notes = getVersionsField($md5,'notes');
                        my_log("md5 ESP8266       : " . $md5);
                        my_log("md5 ESP8266 notes : " . $notes);

                        $md5_localBinary = md5_file($localBinary);
                        $notes = getVersionsField($md5_localBinary,'notes');
                        my_log("md5 file          : " . $md5_localBinary);
                        my_log("md5 file notes    : " . $notes);

                        if ($md5_localBinary != $md5) {
                            my_log("Sending binary  : $localBinary");
                            my_log("Mac status  : $macstatus ; limit_mode : $limit_mode");
                            sendFile($localBinary);
                            noteDownload($mac, $md5_localBinary);
                            if ($mail_on_download) {
                                $mailondownload = getRequestsField($mac , 'mailondownload');
                                if (substr($mailondownload, -2) == "ok") {
                                    my_log("Sending download mail");
                                    if ( $macstatus == "accepted" ) {
                                      sendMail("downloadaccepted", $mac);
                                    } else if ( $macstatus == "registered" ) {
                                      sendMail("downloadregistered", $mac);
                                    } else {
                                      sendMail("downloadblocked", $mac);
                                    }                                    
                                } else {
                                    my_log("Skip mail mailondownload : $mailondownload");
                                }
                            }
                        } else { // no new version
                            my_log("limit_mode : $limit_mode ; 304 Not Modified ; $localBinary");
                            header($_SERVER["SERVER_PROTOCOL"].' 304 Not Modified', true, 304);
                        }
                    }
                }
                
            } else { // $macstatus is still registered, is blocked or even missing
                $notes = getVersionsField($md5,'notes');

                if ( $limit_mode == "full_run" ) { // we allow full mode so we say there is nothing available
                    my_log("limit_mode : $limit_mode ; 304 Not Modified ; mac >$mac< ; status >$macstatus< ; md5 >$md5< ; notes >$notes<");
                    header($_SERVER["SERVER_PROTOCOL"].' 304 Not Modified', true, 304);
                } else { // $limit_mode is limited_run  so we send an error
                    my_log("limit_mode : $limit_mode ; 500 ESP MAC not configured for updates ; mac >$mac< ; status >$macstatus< ; md5 >$md5< ; notes >$notes<");
                    header($_SERVER["SERVER_PROTOCOL"].' 500 ESP MAC not configured for updates', true, 500);
                }
            }
        }
    }
// -------------------------------------------- Download section end ---

// Here we close the database connection

$db->close();

// ---------------------------------- All functions
// ------------------------------------------------- Log function my_log

function my_log($log_line) {
    if ($GLOBALS["logging"]) { error_log("tool1.php : $log_line");}
}

// ------------------------------------------------- Initialise database

function initializeDatabase()
{

// Connect to SQLite database (create it if not exists)

    $db = new SQLite3($GLOBALS["my_db"]);

    $dbQuery = "CREATE TABLE IF NOT EXISTS requests (
        id INTEGER PRIMARY KEY AUTOINCREMENT
        , mac TEXT UNIQUE
        , publicip TEXT
        , email TEXT
        , mailondownload TEXT
        , appname TEXT
        , date TEXT
        , supplier TEXT
        , key TEXT
        , status TEXT
        , notes TEXT
    )";
    $db->exec($dbQuery);

    $dbQuery = "CREATE TABLE IF NOT EXISTS unregistered (
        id INTEGER PRIMARY KEY AUTOINCREMENT
        , mac TEXT UNIQUE
        , publicip TEXT
        , firstdate TEXT
        , date TEXT
        , md5 TEXT
        , appname TEXT
        , notes TEXT
    )";
    $db->exec($dbQuery);

    $dbQuery = "CREATE TABLE IF NOT EXISTS versions (
        id INTEGER PRIMARY KEY AUTOINCREMENT
        , md5 TEXT UNIQUE
        , appname TEXT
        , limit_mode TEXT
        , notes TEXT
        , date TEXT
    )";
    $db->exec($dbQuery);

    $dbQuery = "CREATE TABLE IF NOT EXISTS downloads (
        id INTEGER PRIMARY KEY AUTOINCREMENT
        , mac TEXT
        , md5 TEXT
        , notes TEXT
        , datedownload TEXT
        , publicip TEXT
        , publicipdownload TEXT
        , email TEXT
        , appname TEXT
        , date TEXT
        , supplier TEXT
        , requestnotes TEXT
    )";
    $db->exec($dbQuery);
    
    $dbQuery = "CREATE TABLE IF NOT EXISTS settings (
        id INTEGER PRIMARY KEY AUTOINCREMENT
        , setting TEXT UNIQUE
        , value TEXT
        , comment TEXT
    )";
    $db->exec($dbQuery);

    $keylength = getSettingsField("keylength",$db );
    if ($keylength == "missing") { // This is when the database is created
        setSettingsField("mgmtpwd", "hi",'The password for all management pages.<br>Lost ? : sqlite3 registrations.db "select * from settings"', $db);
        setSettingsField("bypassregistered", "false","Recommended value is false so you can decide what to accept.", $db);
        setSettingsField("emailuser", "dedicated_gmail_account@gmail.com","You would want to setup a dedicated gmail account with 2 factor authentication ...", $db);
        setSettingsField("emailpwd", "kjasnfkfjsjkqeef","... and create an application password like this.", $db);
        setSettingsField("mail_confirmation", "false","... and enable mail_confirmation so you know that the registered email addresses are valid.", $db);
        setSettingsField("mail_key", "false","Send a mail with the key when the key is requested.", $db);
        setSettingsField("mail_on_download", "false","Sends a short message after a download.<br>When individual deliveries fail you can stop mail for individual devices on mr.html.", $db);
        setSettingsField("keylength", "10","The length of the key which the user receives and needs to update his mail registration. (Maximum length is 20.)", $db);
        setSettingsField("maintenancemode", "false","Maintenance mode blocks all access to your server.<br>Except for you from your lan to your public domain name ...", $db);
        setSettingsField("mylaptopip", "192.168.2.24;192.168.2.27","... and for direct access to the ip of your server you can use the laptop/pc/tablet on your lan with any of these addresses.", $db);
        setSettingsField("app_renaming", "false","When you decide to rename your bin files....<br>You must change the app name for existing versions.", $db);
        setSettingsField("logging", "true","Follow logging like : `tail -f /var/log/apache2/error.log`.<br>You may want to add/remove some logging by the function `my_log` in tool1.php", $db);
        setSettingsField("backup_location", "backups","Default location is backups<br>(in " . $GLOBALS["currentFolder"] . " folder)<br>Recommended is a folder on an other disk like /mnt/stick/backups (path starts with / )", $db);
        setSettingsField("backup_type", "tar","You can either backup to tar or to zip files.<br>Install zip : sudo apt install zip unzip -y", $db);
        setSettingsField("backup_versions", "20","The number of backups you want to keep.", $db);

// put some dummy entries in requests and versions

        $date = date("Y-m-d H:i:s"); // get date yyyy-mm-dd HH:mm:ss
        $key = generateRandomString(10);
        $dbQuery = "REPLACE INTO requests (mac, publicip, email, appname, date, supplier, key, mailondownload, status, notes) VALUES ('11:11:11:11:11:11', '123.54.67.12', 'you@provider.com', 'myWemosApp', '$date', 'Some Shop, Some Street 11, London', '$key', 'ok', 'registered', 'just some notes')";
        $db->exec($dbQuery);
        $key = generateRandomString(10);
        $dbQuery = "REPLACE INTO requests (mac, publicip, email, appname, date, supplier, key, mailondownload, status, notes) VALUES ('22:22:22:22:22:22', '123.54.67.12', 'you@provider.com', 'myWemosApp', '$date', 'Some Shop, Some Street 11, London', '$key', 'ok', 'accepted', 'just some notes')";
        $db->exec($dbQuery);
        $key = generateRandomString(10);
        $dbQuery = "REPLACE INTO requests (mac, publicip, email, appname, date, supplier, key, mailondownload, status, notes) VALUES ('33:33:33:33:33:33', '123.54.67.12', 'you@provider.com', 'myWemosApp', '$date', 'Some Shop, Some Street 11, London', '$key', 'ok', 'blocked' ,'just some notes')";
        $db->exec($dbQuery);

        $date = date("Y-m-d H:i:s"); // get date yyyy-mm-dd HH:mm:ss
        $dbQuery = "REPLACE INTO versions (md5, appname, limit_mode, notes, date) VALUES ('477fc2d43952a39523c93529cbb49582', 'myWemosApp.ino.bin', 'full_run', 'myWemosApp.ino.bin Version : 1.0.0', '$date')";
        $db->exec($dbQuery);
        $dbQuery = "REPLACE INTO versions (md5, appname, limit_mode, notes, date) VALUES ('4026cf80201cb2ac5e493632bf84f09a', 'myWemosApp.ino.bin', 'full_run', 'myWemosApp.ino.bin Version : 2.0.0', '$date')";
        $db->exec($dbQuery);

    }

    $db->close();

}

// ---------------------------------------------- Generate random string

function generateRandomString($length) {
// I removed 0..9 so we can not mix up O and 0
//    return substr(str_shuffle(str_repeat($x='0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ', ceil($length/strlen($x)) )),1,$length);
    return substr(str_shuffle(str_repeat($x='abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ', ceil($length/strlen($x)) )),1,$length);
}

// ----------------------------------------------------Note Unregistered

function noteUnregistered($mac, $publicip, $md5) {

    $date = date("Y-m-d H:i:s"); // get date yyyy-mm-dd HH:mm:ss

    $mac_check = getUnregisteredField($mac,'mac');
    $md5_check = getVersionsField($md5,'md5');

    if ($mac_check == "missing") {
        $firstdate = date("Y-m-d H:i:s"); // get date yyyy-mm-dd HH:mm:ss
        if ($md5_check == "missing") {
            $appname = 'unknown app';
            $notes = 'no notes';
        } else {
            $dbQuery = "SELECT * FROM versions WHERE md5 = '$md5'";
            $md5result = $GLOBALS["db"]->querySingle($dbQuery, true);

            $appname = $md5result['appname'];
            $notes = $md5result['notes'];
        }
        $GLOBALS["db"]->exec('BEGIN IMMEDIATE'); // wait until database lock is free
        $dbQuery = "REPLACE INTO unregistered (mac, publicip, firstdate, date, md5, appname, notes) VALUES ('$mac', '$publicip', '$firstdate', '$date', '$md5', '$appname', '$notes')";
        $GLOBALS["db"]->exec($dbQuery);

    } else {
        if ($md5_check == "missing") {
            $appname = 'unknown app';
            $notes = 'no notes';
        } else {
            $dbQuery = "SELECT * FROM versions WHERE md5 = '$md5'";
            $md5result = $GLOBALS["db"]->querySingle($dbQuery, true);

            $appname = $md5result['appname'];
            $notes = $md5result['notes'];
        }
        $GLOBALS["db"]->exec('BEGIN IMMEDIATE'); // wait until database lock is free
        $dbQuery = "UPDATE unregistered set publicip='$publicip', date='$date', md5='$md5', appname='$appname', notes='$notes' WHERE mac='$mac'";
        $GLOBALS["db"]->exec($dbQuery);
    }
    $GLOBALS["db"]->exec('COMMIT TRANSACTION');

    return "Oke";
}

// ------------------------------------------------------ Register entry

function register($mac, $publicip, $email, $appname, $supplier) {

    // find out if $mac is already in a table
    $status = getRequestsField($mac, 'status');

    if ( $status != "missing") {
        return "Sorry $mac is already registered!";
    }

    $date = date("Y-m-d H:i:s"); // get date yyyy-mm-dd HH:mm:ss

    $key = generateRandomString($GLOBALS['keylength']);

    if ($GLOBALS["mail_confirmation"]) {
        $mailondownload = "registered";
        $status = "registered";
    } else {
        $mailondownload = "registeredautook";
        if ($GLOBALS["bypassregistered"]) {
            $status = "accepted" ;
        } else {
            $status = "registered";
        }
    }

/*
// Do not use the next. This is a customer input form so we need to prevent sql insertion
    $GLOBALS["db"]->exec('BEGIN IMMEDIATE'); // wait until database lock is free
    $dbQuery = "REPLACE INTO requests (mac, publicip, email, appname, date, supplier, key, mailondownload, status, notes) VALUES ('$mac', '$publicip', '$email', '$appname', '$date', '$supplier', '$key', '$mailondownload', '$status', '')";
    $GLOBALS["db"]->exec($dbQuery);
// Do it like this :
*/

    $GLOBALS["db"]->exec('BEGIN IMMEDIATE'); // wait until database lock is free

    $stmt = $GLOBALS["db"]->prepare('REPLACE INTO requests (mac, publicip, email, appname, date, supplier, key, mailondownload, status, notes) VALUES (:mac, :publicip, :email, :appname, :date, :supplier, :key, :mailondownload, :status, :notes)');
    
    $stmt->bindParam(':mac', $mac, SQLITE3_TEXT);
    $stmt->bindParam(':publicip', $publicip, SQLITE3_TEXT);
    $stmt->bindParam(':email', $email, SQLITE3_TEXT);
    $stmt->bindParam(':appname', $appname, SQLITE3_TEXT);
    $stmt->bindParam(':date', $date, SQLITE3_TEXT);
    $stmt->bindParam(':supplier', $supplier, SQLITE3_TEXT);
    $stmt->bindParam(':key', $key, SQLITE3_TEXT);
    $stmt->bindParam(':mailondownload', $mailondownload, SQLITE3_TEXT);
    $stmt->bindParam(':status', $status, SQLITE3_TEXT);
    $stmt->bindParam(':notes', $notes, SQLITE3_TEXT);

    $result = $stmt->execute();

    $GLOBALS["db"]->exec($dbQuery);

    if ($result) {
        $message = "Registration successful!<br><br>Note the next info : mac $mac email $email key $key<br><br>You need this to update your email address info.";
        if ($GLOBALS["mail_confirmation"]) {
            sendMail("receivedregistration", $mac );
            $message = $message . "<br><br>Please check your $email mail to confirm your mail address.";
        }
    } else {
        $message = "Registration failed!";
    }
    
// The mac format is checked on the input form so we can use a simpler approach to optionally clean it from the unregistered table

    $dbQuery = "DELETE from unregistered where mac='$mac'";
    $GLOBALS["db"]->exec($dbQuery);
    $GLOBALS["db"]->exec('COMMIT TRANSACTION');

    return $message;
}

// -------------------------------------------------------- Update entry

function update($mac, $publicip, $email, $key) {

    // find out if $mac can be updated
    $status = getRequestsField($mac, 'status');

    if (( $status == "missing") || ( $status == "blocked") ) {
        return "Sorry $mac is missing or blocked or you have the wrong key!";
    }

    $date = date("Y-m-d H:i:s"); // get date yyyy-mm-dd HH:mm:ss

    $oldkey = getRequestsField($mac, 'key');

    if ($key != $oldkey) {
        return "Sorry $mac is missing or blocked or you have the wrong key!";
    }

    if ($GLOBALS["mail_confirmation"]) {
        $mailondownload = "updated";
    } else {
        $mailondownload = "updatedautook";
    }

    $GLOBALS["db"]->exec('BEGIN IMMEDIATE'); // wait until database lock is free

    $stmt = $GLOBALS["db"]->prepare('UPDATE requests SET publicip = :publicip, email = :email, mailondownload = :mailondownload, date = :date WHERE mac = :mac');

    // Bind the parameters
    $stmt->bindParam(':mac', $mac, SQLITE3_TEXT);
    $stmt->bindParam(':publicip', $publicip, SQLITE3_TEXT);
    $stmt->bindParam(':email', $email, SQLITE3_TEXT);
    $stmt->bindParam(':mailondownload', $mailondownload, SQLITE3_TEXT);
    $stmt->bindParam(':date', $date, SQLITE3_TEXT);

    // Execute the statement
    $result = $stmt->execute();

    $GLOBALS["db"]->exec('COMMIT TRANSACTION');

    // Check for errors in the execution
    if ($result) {
        $message = "Update successful!<br><br>Note the next info : mac $mac email $email key $key<br><br>You need this to update your email address info.";
        if ($GLOBALS["mail_confirmation"]) {
            sendMail("receivedregistration", $mac );
            $message = $message . "<br><br>Please check your $email mail to confirm your mail address.";
        }
    } else {
        $message = "Update failed!";
    }
    
/*
    $dbQuery = "UPDATE requests SET publicip = '$publicip', email = '$email', mailondownload = '$mailondownload', date = '$date' where mac = '$mac'";

    $GLOBALS["db"]->exec('BEGIN IMMEDIATE'); // wait until database lock is free
    $GLOBALS["db"]->exec($dbQuery);
    $GLOBALS["db"]->exec('COMMIT TRANSACTION');
    $message = "Update successful!<br><br>Note the next info : mac $mac email $email key $key<br><br>You need this to update your email address info.";
    if ($GLOBALS["mail_confirmation"]) {
        sendMail("receivedupdate", $mac);
        $message = $message . "<br><br>Please check your $email mail to confirm your mail address.";
    }
*/

    return $message;
}

// -------------------------------------------------------- get Key Info

function getKeyInfo($mac, $email) {

    $oldemail = getRequestsField($mac, 'email');
    if (strtoupper($email) != strtoupper($oldemail)) {
        $message = "Unknown $mac or email address does not match registered email address!";
    } else {
        $message = "Your key : " . getRequestsField($mac, 'key');
        if ($GLOBALS["mail_key"]) {
            sendMail("sendkey", $mac);
            $message = $message . " is also in your $email mail.";
        }
    }
    return $message;
}

// ----------------------------------------------------------- Send Mail

function sendMail($type, $mac) {

// find out if we were accessed over https or http

    $protocol=$_SERVER['PROTOCOL'] = isset($_SERVER['HTTPS']) && !empty($_SERVER['HTTPS']) ? 'https' : 'http';
    $phptool = $protocol . "://".$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF'];

    $output = "";
    $app = getRequestsField($mac , 'appname');
    $email = getRequestsField($mac , 'email');
    $key = getRequestsField($mac , 'key');

    switch ( $type ) {

        case "receivedregistration":
        if (! $GLOBALS["mail_confirmation"]) {
            $output = "Mail disabled";
        } else {
            $sub = "Please confirm your registration";
            $bdy = "<html><center>
            <br>Thank you for your $app registration:
            <br><br>Please click the link below to confirm that you received the next for your $app :
            <br><br>MAC $mac ; email $email ; key $key
            <br><br><a href='$phptool?function=confirmRegistration&mac=$mac&key=$key'>Click to confirm your registration</a>
            <br><br>Save this mail because you need this email and key to update to a new mail address.
            </center></html>";
            $cmd = "./amail.py -snd " . $GLOBALS["emailuser"]  . " -pwd " . $GLOBALS["emailpwd"] . " -rcv $email -sub \"$sub\" -bdy \"$bdy\"";
            $output = shell_exec($cmd);
        }
        break;

        case "registrationconfirmed": // only comes when mail_confirmation is true.
            if ( $GLOBALS["mail_on_download"] ) {
              $insert = "<br><br>You will receive a notification when your device updates.";
            } else {
              $insert = "";
            }
            $sub = "Registration confirmation received.";
            $bdy = "<html><center>
            <br>Thank you for your confirmation.
            <br><br>The registration entered the acceptance phase.
            <br><br>Your device will receive the full version after it is accepted.
            $insert
            </center></html>";
            $cmd = "./amail.py -snd " . $GLOBALS["emailuser"]  . " -pwd " . $GLOBALS["emailpwd"] . " -rcv $email -sub \"$sub\" -bdy \"$bdy\"";
            $output = shell_exec($cmd);
            break;

        case "receivedupdate":
        if (! $GLOBALS["mail_confirmation"]) {
            $output = "Mail disabled";
        } else {
            $sub = "Please confirm your registration update";
            $bdy = "<html><center>
            <br>Thank you for your $app registration update:
            <br><br>Please click the link below to confirm that you reveived the next for your $app :
            <br><br>MAC $mac ; email $email ; key $key
            <br><br><a href='$phptool?function=confirmUpdate&mac=$mac&key=$key'>Click to confirm your update</a>
            <br><br>Save this mail because you need this email and key to update to a new mail address.
            </center></html>";
            $cmd = "./amail.py -snd " . $GLOBALS["emailuser"]  . " -pwd " . $GLOBALS["emailpwd"] . " -rcv $email -sub \"$sub\" -bdy \"$bdy\"";
            $output = shell_exec($cmd);
        }
        break;

        case "updateconfirmed": // only comes when mail_confirmation is true.
            $sub = "Update confirmation received.";
            if ( $GLOBALS["mail_on_download"] ) {
              $insert = "<br><br>You will receive a notification when your device updates.";
            } else {
              $insert = "";
            }
            $bdy = "<html><center>
            <br>Thank you for your update confirmation.
            $insert
            </center></html>";
            $cmd = "./amail.py -snd " . $GLOBALS["emailuser"]  . " -pwd " . $GLOBALS["emailpwd"] . " -rcv $email -sub \"$sub\" -bdy \"$bdy\"";
            $output = shell_exec($cmd);
            break;

        case "sendkey":
        if (! $GLOBALS["mail_key"]) {
            $output = "Mail disabled";
        } else {
            $sub = "Here is your key";
            $bdy = "<html><center>
            <br>Here is your key info for your $app registration:
            <br><br>MAC $mac ; email $email ; key $key
            <br><br>Save this mail because you need this email and key to update to a new mail address.
            </center></html>";
            $cmd = "./amail.py -snd " . $GLOBALS["emailuser"]  . " -pwd " . $GLOBALS["emailpwd"] . " -rcv $email -sub \"$sub\" -bdy \"$bdy\"";
            $output = shell_exec($cmd);
        }
        break;

        case "downloadaccepted":
        if (! $GLOBALS["mail_on_download"]) {
            $output = "Mail disabled";
        } else {
              $sub = "Update notification for your accepted $app";
              $bdy = "<html><center>
              <br>Your $app just updated to the last full version.
              <br><br>Thank you for using an accepted version.
              </center></html>";
            $cmd = "./amail.py -snd " . $GLOBALS["emailuser"]  . " -pwd " . $GLOBALS["emailpwd"] . " -rcv $email -sub \"$sub\" -bdy \"$bdy\"";
              $output = shell_exec($cmd);
        }
        break;

        case "downloadregistered":
        if (! $GLOBALS["mail_on_download"]) {
            $output = "Mail disabled";
        } else {
              $sub = "Update notification for your registered $app";
              $bdy = "<html><center>
              <br>Your $app just updated to the last full version.
              <br><br>This is a full version although your device is not in the accepted state yet.
              <br><br>Thank you for your registration.
              </center></html>";
            $cmd = "./amail.py -snd " . $GLOBALS["emailuser"]  . " -pwd " . $GLOBALS["emailpwd"] . " -rcv $email -sub \"$sub\" -bdy \"$bdy\"";
              $output = shell_exec($cmd);
        }
        break;

        case "downloadblocked":
        if (! $GLOBALS["mail_on_download"]) {
            $output = "Mail disabled";
        } else {
              $supplier = getRequestsField($mac, 'supplier');
              $sub = "Update notification for your blocked $app";
              $bdy = "<html><center>
              <br>Your $app just updated to the last full version.
              <br><br>This is a full version although your device is in the blocked state.
              <br><br>Blocked devices can stop working in the near future.
              <br><br>You may want to contact your supplier.
              <br><br>Supplier info for this $app with mac $mac :
              <br><br>$supplier
              </center></html>";
            $cmd = "./amail.py -snd " . $GLOBALS["emailuser"]  . " -pwd " . $GLOBALS["emailpwd"] . " -rcv $email -sub \"$sub\" -bdy \"$bdy\"";
              $output = shell_exec($cmd);
        }
        break;

        default:
            $output = "Error: Invalid mail function $type";
            my_log($output);
    }
    return $output;
}

// ---------------------------- Confirm Registration

function confirmRegistration($mac, $key) {

    $oldkey = getRequestsField($mac, 'key');

    if ($oldkey != $key) {
        return "Confirmation failed!";
    }
    setRequestsField($mac, 'mailondownload', 'ok' );
    if ( $GLOBALS["bypassregistered"] ) {
        setRequestsField($mac, 'status', 'accepted' );
    }
    sendMail("registrationconfirmed",$mac);
    return "Thank you for confirming your registration!";
}

// ---------------------------- Confirm Update

function confirmUpdate($mac, $key) {

    $oldkey = getRequestsField($mac, 'key');

    if ($oldkey != $key) {
        return "Update failed!";
    }

    setRequestsField($mac, 'mailondownload', 'ok' );
    if ( $bypassregistered ) {
        setRequestsField($mac, 'status', 'accepted' );
    }
    sendMail("updateconfirmed", $mac);
    return "Thank you for confirming your update!";
}

// ------------------------------------------------------- Backup System

function backupSystem($pwd) {

    if ( ($pwd == $GLOBALS["mgmtpwd"]) && ( $GLOBALS["itsme"] ) ) {
        $backup_location = getSettingsField('backup_location');
        $backup_type = getSettingsField('backup_type');
        $backup_versions = getSettingsField('backup_versions');
        $cmd = "./create_backup.sh $backup_location $backup_type $backup_versions ";
// Lock the database so no one can write
        $GLOBALS["db"]->exec('BEGIN IMMEDIATE'); // wait until database lock is free
        $output = shell_exec($cmd);
        $GLOBALS["db"]->exec('COMMIT TRANSACTION');
// Remove lock so database can be written again
        return $output;
    } else {
        return "ERROR, Not allowed to backup";
    }
}

// ------------------------------------------------------- Backup System

function getSystemInfo($pwd) {
    if ($pwd == $GLOBALS["mgmtpwd"]) {
        $cmd = "./get_info.sh";
        $output = shell_exec($cmd);

        // Split the output into lines
        $lines = explode("\n", trim($output));

        // Create an array to store the data
        $data = [];

        // Loop through each line starting from index 2 to skip the column names and dashes
        for ($i = 0; $i < count($lines); $i++) {
            // Remove extra spaces and trim the line
            $line = preg_replace('/\s+/', ' ', trim($lines[$i]));

            // Skip empty lines
            if (empty($line)) {
                continue;
            }

            // Remove any extra double quotes and replace escaped double quotes with a single quote
            $line = str_replace('\"', '"', trim($line, '"'));

            // Split the line into columns
            $columns = explode('|', $line);

            // Create an associative array with column names as keys
            $entry = [
                'col1' => $columns[0],
                'col2' => $columns[1],
                'col3' => $columns[2],
                'col4' => $columns[3],
            ];

            // Add the entry to the data array
            $data[] = $entry;
        }

        // Convert the array to JSON
        $jsonoutput = json_encode($data);

        return $jsonoutput;
    } else {
        return "ERROR";
    }
}

function getSystemInfook($pwd) {
    if ($pwd == $GLOBALS["mgmtpwd"]) {
        $cmd = "./get_info.sh";
        $output = shell_exec($cmd);

        // Split the output into lines
        $lines = explode("\n", trim($output));

        // Create an array to store the data
        $data = [];

        // Loop through each line starting from index 2 to skip the column names and dashes
        for ($i = 2; $i < count($lines); $i++) {
            // Remove double quotes and extra spaces
            $line = preg_replace('/\s+/', ' ', trim($lines[$i]));
            $line = trim($line, '"');

            // Skip empty lines
            if (empty($line)) {
                continue;
            }

            // Split the line into columns
            $columns = explode(' ', $line);

            // Create an associative array with column names as keys
            $entry = [
                'col1' => $columns[0],
                'col2' => $columns[1],
                'col3' => $columns[2],
                'col4' => $columns[3],
            ];

            // Add the entry to the data array
            $data[] = $entry;
        }

        // Convert the array to JSON
        $jsonoutput = json_encode($data);

        return $jsonoutput;
    } else {
        return "ERROR";
    }
}

// ------------------------------------------------------------ Change mailondownload column

function updatemailondownload($selectedRows, $pwd, $status) {

    if ( ($pwd == $GLOBALS["mgmtpwd"]) && ( $GLOBALS["itsme"] ) ) {
        $GLOBALS["db"]->exec('BEGIN IMMEDIATE'); // wait until database lock is free
        foreach ($selectedRows as $mac) {
            $dbQuery = "UPDATE requests SET mailondownload = '$status' WHERE mac = '$mac'";
            $result = $GLOBALS["db"]->querySingle($dbQuery, true);
        }
        $GLOBALS["db"]->exec('COMMIT TRANSACTION');
        return "Oke, changed to $status";
    } else {
        return "ERROR, Not allowed to update";
    }
}

// ---------------------------------------- Delete selected from requests

function deleteBlocked($selectedRows, $pwd) {
    if ( ($pwd == $GLOBALS["mgmtpwd"]) && ( $GLOBALS["itsme"] ) ) {
        $countdeleted = 0;
        $countskipped = 0;
        $GLOBALS["db"]->exec('BEGIN IMMEDIATE'); // wait until database lock is free
        foreach ($selectedRows as $mac) {
            $dbQuery = "DELETE FROM requests WHERE mac = '$mac' AND status = 'blocked'";
            $GLOBALS["db"]->exec($dbQuery);

            $deletedRows = $GLOBALS["db"]->changes();

            if ($deletedRows > 0) {
                $countdeleted = $countdeleted + 1;
            } else {
                $countskipped = $countskipped + 1;
            }
        }
        $GLOBALS["db"]->exec('COMMIT TRANSACTION');
        return "Oke, deleted $countdeleted 'blocked' requests and skipped $countskipped";
    } else {
        return "ERROR, Not allowed to delete";
    }
}

// ---------------------------------------- Delete Unregistered

function deleteUnregistered($selectedRows, $pwd) {
    if ( ($pwd == $GLOBALS["mgmtpwd"]) && ( $GLOBALS["itsme"] ) ) {
        $countdeleted = 0;
        $countskipped = 0;
        $GLOBALS["db"]->exec('BEGIN IMMEDIATE'); // wait until database lock is free
        foreach ($selectedRows as $mac) {
            $dbQuery = "DELETE FROM unregistered WHERE mac = '$mac'";
            $GLOBALS["db"]->exec($dbQuery);

            $deletedRows = $GLOBALS["db"]->changes();

            if ($deletedRows > 0) {
                $countdeleted = $countdeleted + 1;
            } else {
                $countskipped = $countskipped + 1;
            }
        }
        $GLOBALS["db"]->exec('COMMIT TRANSACTION');
        return "Oke, deleted $countdeleted 'unregistered'";
    } else {
        return "ERROR, Not allowed to delete";
    }
}

// ---------------------------------------------- Update requests notes

function manageRequests($functionName, $selectedRows, $macs, $newnotes, $pwd) {

    if ( ($pwd == $GLOBALS["mgmtpwd"]) && ( $GLOBALS["itsme"] ) ) {

        switch ($functionName) {
            case "Register Selected":
                $baseQuery = "UPDATE requests SET status = 'registered', ";
                break;
            case "Accept Selected":
                $baseQuery = "UPDATE requests SET status = 'accepted', ";
                break;
            case "Block Selected":
                $baseQuery = "UPDATE requests SET status = 'blocked', ";
                break;
            case "Update Notes":
                $baseQuery = "UPDATE requests SET ";
                break;
            default:
                return "Error: Invalid function!";
        }

        $dataToUpdate = array();
        foreach ($macs as $index => $mac) {
            $dataToUpdate[$mac] = array(
                'newnotes' => $newnotes[$index],
            );
        }

        $count = 0;
        $GLOBALS["db"]->exec('BEGIN IMMEDIATE'); // wait until database lock is free
        foreach ($selectedRows as $selectedRow) {
            $mac = $selectedRow;
            if (array_key_exists($mac, $dataToUpdate)) {
                $values = $dataToUpdate[$mac];
                $notes = $values['newnotes'];
                $dbQuery = $baseQuery . " notes = '$notes' WHERE mac = '$mac'";

                $result = $GLOBALS["db"]->querySingle($dbQuery, true);
                $count = $count + 1;
            }
        }
        $GLOBALS["db"]->exec('COMMIT TRANSACTION');
        return "Oke, $count updates";
    } else {
        return "ERROR, Not allowed : $functinName";
    }
}

// ---------------------------------------------- Get json from requests

function getRequests($offset, $count, $pwd, $filter, $filterValue, $status, $reverse, $orderby, $order) {

    if ($pwd == $GLOBALS["mgmtpwd"]) {

        $dbQuery = "SELECT * FROM requests";
        if ($status == "any") {
            $dbQuery = $dbQuery . " WHERE status != 'empty'";
        } else {
            $dbQuery = $dbQuery . " WHERE status = '$status'";
        }

        if ( $filter == "None" ) {
            $dbQuery = $dbQuery . " ORDER By $orderby $order LIMIT $offset , $count";
        } else {
            if ($reverse == "Yes") {
                $dbQuery = $dbQuery . " AND $filter not like '%$filterValue%' ORDER By $orderby $order LIMIT $offset , $count";
            } else {
                $dbQuery = $dbQuery . " AND $filter like '%$filterValue%' ORDER By $orderby $order LIMIT $offset , $count";
            }
        }

        $result = $GLOBALS["db"]->query($dbQuery);

        $data = array();
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $data[] = $row;
        }
        return json_encode($data);
    } else {
        return "Error";
    }
}

// --------------------------------------------- Get json from downloads

function getDownloads($offset, $count, $pwd, $filter, $filterValue, $reverse, $orderby, $order) {

    if ($pwd == $GLOBALS["mgmtpwd"]) {

        $dbQuery = "SELECT * FROM downloads";

        if ( $filter == "None" ) {
            $dbQuery = $dbQuery . " ORDER By $orderby $order LIMIT $offset , $count";
        } else {
            if ($reverse == "Yes") {
                $dbQuery = $dbQuery . " WHERE $filter not like '%$filterValue%' ORDER By $orderby $order LIMIT $offset , $count";
            } else {
                $dbQuery = $dbQuery . " WHERE $filter like '%$filterValue%' ORDER By $orderby $order LIMIT $offset , $count";
            }
        }

        $result = $GLOBALS["db"]->query($dbQuery);

        $data = array();
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $data[] = $row;
        }
        return json_encode($data);
    } else {
        return "Error";
    }
}

// ---------------------------------------------------- Delete Downloads

function deleteDownloads($selectedRows, $datedownloads, $macs, $pwd) {

    if ( ($pwd == $GLOBALS["mgmtpwd"]) && ( $GLOBALS["itsme"] ) ) {
        // Combine $datedownloads with $macs using a loop
        $dataToDelete = array();
        foreach ($datedownloads as $index => $datedownload) {
            $dataToDelete[$datedownload] = array(
                'mac' => $macs[$index],
            );
        }
        $count = 0;
        $GLOBALS["db"]->exec('BEGIN IMMEDIATE'); // wait until database lock is free
        foreach ($selectedRows as $selectedRow) {
            $datedownload = $selectedRow;

            if (array_key_exists($datedownload, $dataToDelete)) {
                $values = $dataToDelete[$datedownload];
                $mac = $values['mac'];
                $dbQuery = "DELETE FROM downloads WHERE datedownload = '$datedownload' AND mac = '$mac'";
                $GLOBALS["db"]->exec($dbQuery);
                $count = $count + 1;
            }
        }
        $GLOBALS["db"]->exec('COMMIT TRANSACTION');
        return "Oke, deleted $count downloads";
    } else {
        return "ERROR, Not allowed to delete";
    }
}

// ------------------------------------------------------- Save Settings

function saveSettings($settings, $values, $pwd) {

    if ( ($pwd == $GLOBALS["mgmtpwd"]) && ( $GLOBALS["itsme"] ) ) {

        for ($x = 0; $x < count($settings); $x++) {
            $setting = $settings[$x];
            $value = $values[$x];
            if (($setting == "keylength") && ($value > 20 )) {$value = 20;}
            $dbQuery = "UPDATE settings SET value='$value' WHERE setting='$setting'";
//            $GLOBALS["db"]->exec('PRAGMA busy_timeout = 1000'); // Set timeout to 1000 milliseconds (1 second)
            $GLOBALS["db"]->exec('BEGIN IMMEDIATE'); // wait until database lock is free
            $GLOBALS["db"]->exec($dbQuery);
            $GLOBALS["db"]->exec('COMMIT TRANSACTION');
        }

        return "Oke, saved settings";
    } else {
        return "ERROR, Not allowed to save data";
    }
}

// ------------------------------------------------------- Update Versions

function updateVersions($selectedRows, $md5s, $newappnames, $newlimit_modes, $newnotes, $pwd) {

    if ( ($pwd == $GLOBALS["mgmtpwd"]) && ( $GLOBALS["itsme"] ) ) {
        // Combine md5s with newappnames and newnotes using a loop
        $dataToUpdate = array();
        foreach ($md5s as $index => $md5) {
            $dataToUpdate[$md5] = array(
                'newappnames' => $newappnames[$index],
                'newlimit_modes' => $newlimit_modes[$index],
                'newnotes' => $newnotes[$index],
            );
        }

        $app_renaming = getSettingsField('app_renaming') == "true";

        $GLOBALS["db"]->exec('BEGIN IMMEDIATE'); // wait until database lock is free
        foreach ($selectedRows as $selectedRow) {
            $md5 = $selectedRow;

            if (array_key_exists($md5, $dataToUpdate)) {
                $values = $dataToUpdate[$md5];
                $appname = $values['newappnames'];
                $limit_mode = $values['newlimit_modes'];
                $notes = $values['newnotes'];

                if ( $app_renaming ) {
                    $dbQuery = "UPDATE versions SET appname='$appname', notes='$notes', limit_mode='$limit_mode' WHERE md5='$md5'";
                } else {
                    $dbQuery = "UPDATE versions SET notes='$notes', limit_mode='$limit_mode' WHERE md5='$md5'";
                }
                $GLOBALS["db"]->exec($dbQuery);
            }
        }
        $GLOBALS["db"]->exec('COMMIT TRANSACTION');

        if ( $app_renaming ) {
            return "Oke (app renaming is enabled)";
        } else {
            return "Oke (app renaming is disabled)";
        }
    } else {
        return "ERROR, Not allowed to update versions";
    }
}

// ---------------------------------------------------- Delete Versions

function deleteVersions($selectedRows, $md5s, $pwd) {

    if ( ($pwd == $GLOBALS["mgmtpwd"]) && ( $GLOBALS["itsme"] ) ) {
        $count = 0;
        $GLOBALS["db"]->exec('BEGIN IMMEDIATE'); // wait until database lock is free
        foreach ($selectedRows as $index => $md5) {
           $dbQuery = "DELETE FROM versions WHERE md5 = '$md5'";
           $GLOBALS["db"]->exec($dbQuery);
           $count = $count + 1;
        }
        $GLOBALS["db"]->exec('COMMIT TRANSACTION');
        return "Oke, deleted $count verions";
    } else {
        return "ERROR, Not allowed to delete versions";
    }
}

// ----------------------------------------------------- Import Versions

function importVersions($pwd) {

    if ($pwd == $GLOBALS["mgmtpwd"]) {
        $binDirectory = $GLOBALS["my_bins"];
        $binFiles = glob("$binDirectory/*.bin");
        $count = 0;
        $GLOBALS["db"]->exec('BEGIN IMMEDIATE'); // wait until database lock is free
        foreach ($binFiles as $binFile) {
            $md5 = md5_file($binFile);
            $app = getVersionsField($md5,'appname');
            if ( $app == 'missing' ) {
                $appname = pathinfo($binFile, PATHINFO_FILENAME);
                $appname = pathinfo($binFile,  PATHINFO_BASENAME);
                $limit_mode = "full_run";
                $notes = "$appname Version : latest";
                $date = date('Y-m-d H:i:s');
                $dbQuery = "REPLACE INTO versions (md5, appname, limit_mode, notes, date) VALUES ('$md5', '$appname', '$limit_mode', '$notes', '$date')";
                $GLOBALS["db"]->exec($dbQuery);
                $count = $count + 1;
            }
        }
        $GLOBALS["db"]->exec('COMMIT TRANSACTION');
        return "Oke, imported $count versions";
    } else {
        return "ERROR";
    }
}

// ---------------------------------------------- Get json from versions

function getSettings($pwd) {

    if ($pwd == $GLOBALS["mgmtpwd"]) {

        if ( ! $GLOBALS["itsme"] ) {
            $dbQuery = "SELECT * FROM settings where setting not like 'email%'";
        } else {
            $dbQuery = "SELECT * FROM settings";
        }

        $result = $GLOBALS["db"]->query($dbQuery);
        $data = array();

        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $data[] = $row;
        }
        return json_encode($data);
    } else {
        return "Error";
    }
}

// ---------------------------------------------- Get json from versions

function getVersions($offset, $count, $pwd, $filter, $filterValue, $reverse, $orderby, $order) {

    if ($pwd == $GLOBALS["mgmtpwd"]) {

        $app_renaming = getSettingsField('app_renaming');

        $dbQuery = "SELECT $app_renaming as 'app_renaming', * FROM versions";

        if ( $filter == "None" ) {
            $dbQuery = $dbQuery . " ORDER By $orderby $order LIMIT $offset , $count";
        } else {
            if ($reverse == "Yes") {
                $dbQuery = $dbQuery . " WHERE $filter not like '%$filterValue%' ORDER By $orderby $order LIMIT $offset , $count";
            } else {
                $dbQuery = $dbQuery . " WHERE $filter like '%$filterValue%' ORDER By $orderby $order LIMIT $offset , $count";
            }
        }
        $result = $GLOBALS["db"]->query($dbQuery);
        $data = array();
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $data[] = $row;
        }
        return json_encode($data);
    } else {
        return "Error";
    }
}

// ------------------------------------------ Get json from unregistered

function getUnregistered($offset, $count, $pwd, $filter, $filterValue, $reverse, $orderby, $order) {

    if ($pwd == $GLOBALS["mgmtpwd"]) {

        $dbQuery = "SELECT * FROM unregistered";

        if ( $filter == "None" ) {
            $dbQuery = $dbQuery . " ORDER By $orderby $order LIMIT $offset , $count";
        } else {
            if ($reverse == "Yes") {
                $dbQuery = $dbQuery . " WHERE $filter not like '%$filterValue%' ORDER By $orderby $order LIMIT $offset , $count";
            } else {
                $dbQuery = $dbQuery . " WHERE $filter like '%$filterValue%' ORDER By $orderby $order LIMIT $offset , $count";
            }
        }
        $result = $GLOBALS["db"]->query($dbQuery);
        $data = array();
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $data[] = $row;
        }
        return json_encode($data);
    } else {
        return "Error";
    }
}

// ------------------------------------------ Get my public ip 4 address

function getPublicIPv4() {
    // Use stream context to set a timeout for the HTTP request
    $context = stream_context_create(['http' => ['timeout' => 5]]);

    // Use @ to suppress warnings and handle errors manually
    $response = @file_get_contents('https://api.ipify.org?format=json', false, $context);

    if ($response === false) {
        // Handle the error, in this case, set $ipv4 to "missing"
        $ipv4 = "missing";
    } else {
        // Decode the JSON response
        $data = json_decode($response, true);

        // Check if the 'ip' key is present in the decoded data
        if ($data && isset($data['ip'])) {
            $ipv4 = $data['ip'];
        } else {
            // If 'ip' key is not present, set $ipv4 to "missing"
            $ipv4 = "missing";
        }
    }

    return $ipv4;
}
/*
In this version, a stream context is created to set a timeout of 5 seconds for the HTTP request. The @ symbol is used to suppress warnings from file_get_contents, and then the function manually checks if there was an error (if $response is false) and handles it by setting $ipv4 to "missing". If the request is successful, it proceeds with decoding the JSON response and checking for the 'ip' key as before.
*/

// --------------------------------- Get modification date for public ip

function getLastDate($publicip) {

    $dbQuery = "SELECT date FROM requests WHERE publicip = '$publicip' ORDER BY date DESC LIMIT 1";
    $result = $GLOBALS["db"]->query($dbQuery);
    if (!$result) {
        // Handle query error, e.g., return an error message or log the error
        return "No date 1";
    }
    $row = $result->fetchArray(SQLITE3_ASSOC);
    $date = $row['date'];
    return $date;
}

// ------------------------------------------------ Get a settings field

function getSettingsField($field , $db = "none") {

    $dbQuery = "SELECT * FROM settings WHERE setting = '$field'";

    if ($db == "none") {
        $result = $GLOBALS["db"]->query($dbQuery);
    } else {
        $result = $db->query($dbQuery);
    }
    if (!$result) {
        return "missing";
    }
    $value = "missing";
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $value = $row['value'];
    }
    return $value;
}

// ------------------------------------------------ Set a settings field

function setSettingsField($field, $value, $comment = NULL , $db = NULL ) {

/*
The function initializeDatabase() which initialises the database uses this function
to fill the databaase with initial values and passes $db

The none value would be used from the main code which is not in use yet

*/

    if ($db == NULL) {
        $dbQuery = "UPDATE settings set value = '$value' WHERE setting ='$field'";
        $GLOBALS["db"]->exec('BEGIN IMMEDIATE'); // wait until database lock is free
        $result = $GLOBALS["db"]->query($dbQuery);
        $GLOBALS["db"]->exec('COMMIT TRANSACTION');
    } else {
        $dbQuery = "REPLACE INTO settings (setting, value, comment) VALUES ('$field','$value','$comment')";
        $db->exec('BEGIN IMMEDIATE'); // wait until database lock is free
        $result = $db->query($dbQuery);
        $db->exec('COMMIT TRANSACTION');
    }
    
    return $value;
}

// ------------------------------------------------ Get a request field

function getRequestsField($mac, $field) {
 
// This function can be used by the  functions update and getKeyInfo from rr.html so it needs protection.
 
    // Use prepared statement to prevent SQL injection
    $stmt = $GLOBALS["db"]->prepare('SELECT ' . $field . ' FROM requests WHERE mac = :mac');

    // Bind the parameter
    $stmt->bindParam(':mac', $mac, SQLITE3_TEXT);

    // Execute the statement
    $result = $stmt->execute();

    // Check for errors in the execution
    if (!$result) {
        return "missing";
    }

    $value = "missing";

    // Fetch the results
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $value = $row[$field];
    }

    return $value;
}


// ------------------------------------------------ Set a request field

function setRequestsField($mac, $field, $value) {

    $dbQuery = "UPDATE requests set $field = '$value' WHERE mac = '$mac'";

    $GLOBALS["db"]->exec('BEGIN IMMEDIATE'); // wait until database lock is free
    $result = $GLOBALS["db"]->query($dbQuery);
    $GLOBALS["db"]->exec('COMMIT TRANSACTION');
    if (!$result) {
        return "missing";
    }
    return $value;
}

// ------------------------------------------------ Get a versions field

function getVersionsField($md5, $field) {

    $dbQuery = "SELECT * FROM versions WHERE md5 = '$md5'";
    $result = $GLOBALS["db"]->query($dbQuery);
    if (!$result) {
        return "missing";
    }
    $value = "missing";
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $value = $row[$field];
    }
    return $value;
}

// ------------------------------------------------ Get unregistered field

function getUnregisteredField($mac, $field) {

    $dbQuery = "SELECT * FROM unregistered WHERE mac = '$mac'";
    $result = $GLOBALS["db"]->query($dbQuery);
    if (!$result) {
        return "missing";
    }
    $value = "missing";
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $value = $row[$field];
    }
    return $value;
}

// ----------------------------------------------- Note download for mac

function noteDownload($mac, $md5) {

    $macstatus = getRequestsField($mac, 'status');
    $app = getVersionsField($md5,'appname');
    $datetime = date("Y-m-d H:i:s");
    $publicipdownload = $GLOBALS["publicip"];

    $dbQuery = "SELECT * FROM versions WHERE md5 = '$md5'";
    $md5result = $GLOBALS["db"]->querySingle($dbQuery, true);

    $GLOBALS["db"]->exec('BEGIN IMMEDIATE'); // wait until database lock is free
    if ($macstatus == "missing") {
        $dash = "-";
        $dbQuery = "REPLACE INTO downloads (mac, md5, appname, notes, datedownload, publicipdownload, publicip, email, date, supplier, requestnotes) VALUES ('$mac', '$md5', '$app', '$md5result[notes]', '$datetime', '$publicipdownload', '$dash', '$dash', '$dash', '$dash', '$dash')";
        $GLOBALS["db"]->exec($dbQuery);

    } else {

// get mac data

        $dbQuery = "SELECT * FROM requests WHERE mac = '$mac'";
        $macresult = $GLOBALS["db"]->querySingle($dbQuery, true);
        $dbQuery = "REPLACE INTO downloads (mac, md5, appname, notes, datedownload, publicipdownload, publicip, email, date, supplier, requestnotes) VALUES ('$mac', '$md5', '$macresult[appname]', '$md5result[notes]', '$datetime', '$publicipdownload', '$macresult[publicip]', '$macresult[email]', '$macresult[date]', '$macresult[supplier]', '$macresult[notes]')";
        $GLOBALS["db"]->exec($dbQuery);
    }
    $GLOBALS["db"]->exec('COMMIT TRANSACTION');
}

// ------------------------ Send web browser to previous page and reload

function go_back($parm1, $parm2, $seconds = NULL){

    if ($seconds == NULL ) { $steps = 30 ; } else { $steps = $seconds * 10 ; }
    echo "<html><head><title>......</title></head>
            <body>$parm1 $parm2<br>
            <progress value='0' max='" . $steps . "' id='progressBar'></progress>
            <script>
            var timeleft = $steps;
            var gobackTimer = setInterval(function(){
            if(timeleft <= 0){
            clearInterval(gobackTimer);
            window.history.go(-1);
            }
            document.getElementById('progressBar').value = $steps - timeleft;
            timeleft -= 1;
            }, 100);
            </script>
            </body></html>
            "; 
/*
    } else {
// Go back and reload page.
// Used on rr.html
        echo "$parm1<p>Redirecting in 10 seconds...</p>
              <script>
                setTimeout(function() {
                    window.location.replace(document.referrer); // Simulate going back
                    setTimeout(function() {
                        location.reload(); // Reload the page
                    }, 1000);
                }, 10000);
              </script>";
    }
*/
}

// ------------------------ Download Functions check_header and sendFile

// come from  https://arduino-esp8266.readthedocs.io/en/latest/ota_updates/readme.html#http-server

function check_header($name, $value = false) {

    if(!isset($_SERVER[$name])) {
        return false;
    }
    if($value && $_SERVER[$name] != $value) {
        return false;
    }
    return true;
}

function sendFile($path) {
    header($_SERVER["SERVER_PROTOCOL"].' 200 Oke', true, 200);
    header('Content-Type: application/octet-stream', true);
    header('Content-Disposition: attachment; filename='.basename($path));
    header('Content-Length: '.filesize($path), true);
    header('x-MD5: '.md5_file($path), true);
    readfile($path);
}

// ------------------------ End tool1.php

?>

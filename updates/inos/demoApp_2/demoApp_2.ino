// This app is like demoApp_1 but starts in limited mode
// It switches to full mode after it connected as an accepted device
// It also falls back to Limited mode after a sequence of failures

// Unwelcome copies will never work when you use this setup

// Browse through the code and change the values of some things to get it working for you

// ----- I have no WiFi manager in this example
String ssid = "SSID";
String password = "WiFiPassword";

// To create a new version change the version number
static const char app_version[] PROGMEM = "V1.0.0";
static const char app_name[] PROGMEM = "demoApp_2";

// ----- Timing mechanism to call the function updateHandler()
#include <Ticker.h>
// ESPhttpUpdate is not supported in a Ticker so........ the loop has to call the updateHandler()
// The Ticker function updateTickerRoutine() sets a trigger which is monitored in the loop to call the function updateHandler()
Ticker updateTicker;              // used in setup() to schedule updateTickerRoutine()
float updateTickerInterval = 30 ; // seconds interval , use 3600 * 24 for a day
bool triggerUpdate = true;        // trigger is set to false in updateHandler()
void updateTickerRoutine()  { triggerUpdate = true; }

// ----- include the ESP8266httpUpdate library
#include <ESP8266httpUpdate.h>
WiFiClient client;

// ----- Settings for updateHandler()
// Settings for the ESPhttpUpdate calls
static const char update_Domain1[] PROGMEM = "domain1.onthewifi.com";
static const char update_Tool1[] PROGMEM   = "/updates/tool1.php";
int    update_http_Port1 = 83;
int    update_https_Port1 = 445;
static const char update_Domain2[] PROGMEM = "domain2.thehomeserver.net";
int    update_http_Port2 = 83;
int    update_https_Port2 = 445;
static const char update_Tool2[] PROGMEM   = "/updates/tool1.php";
static const char update_Version[] PROGMEM = "Not used by tool1.php";

// boolean to know in which mode we run and we start in limited mode
bool fullMode = false;
// boolean to detect in updateHandler() if we changed mode so we can give a message on the serial line
bool previousMode = false;

// We need something to decide when we fall back to limited mode
int numberFails = 6;    // Count consequtive failures ( reset to 0 after success )
int numberFailsMax = 6; // limited mode after updateTickerInterval * numberFailsMax seconds

void updateHandler() {

    triggerUpdate = false; // stop loop to call this routine until triggerUpdate is set again by Ticker routine.

    bool update_Failing = false;
    t_httpUpdate_return ret = ESPhttpUpdate.update(client, String(update_Domain1), update_http_Port1, String(update_Tool1), String(update_Version));
    switch(ret) {
    case HTTP_UPDATE_FAILED:
        // This means we could not connect because our server is down or what we do not like.....
        // maybe someone used an hex editor to change the connection details to avoid a update of a limited version
        if (numberFails < numberFailsMax ) {numberFails = numberFails + 1;} // we want to know if we have to many failures and also avoid an integer overflow
        Serial.println(F("[update] Update 1 failed."));
        update_Failing = true;
        break;
    case HTTP_UPDATE_NO_UPDATES:
        // This means we detected there are no updates so we have connection
        numberFails = 0;
        Serial.println(F("[update] Update 1 no Update."));
        break;
    case HTTP_UPDATE_OK:
        // This means we had an update and may not get here since we reboot the ESP
        numberFails = 0;
        Serial.println(F("[update] Update 1 ok.")); // may not be called since we reboot the ESP
        break;
    }

    if (update_Failing ) {
  //      update_Failing = false; // add this when you add a 3rd domain
        t_httpUpdate_return ret = ESPhttpUpdate.update(client, String(update_Domain2), update_http_Port2, String(update_Tool2), String(update_Version));
        switch(ret) {
        case HTTP_UPDATE_FAILED:
            Serial.println(F("[update] Update 2 failed."));
  //          update_Failing = true; // add this when you add a 3rd domain
            break;
        case HTTP_UPDATE_NO_UPDATES:
            numberFails = 0;
            Serial.println(F("[update] Update 2 no Update."));
            break;
        case HTTP_UPDATE_OK:
            numberFails = 0;
            Serial.println(F("[update] Update 2 ok.")); // may not be called since we reboot the ESP
            break;
        }
    }

    fullMode = ( numberFails < numberFailsMax) ;
    if (fullMode != previousMode ) {
        // save new mode
        previousMode = fullMode;
        if (fullMode) {
            Serial.println(F(">>>> Switched to Full mode"));
            // Here we do our full mode activities
        } else {
            Serial.println(F(">>>> Switched to Limited mode"));
            // Here we do our limited mode activities
        }
    }

}

// Just a function to have something to do in the loop
void flip_LED(String by) {
    // Flip the onboard led and write a message
    if ( by != F("") ) {Serial.println( F("flip_LED by : ") + by );}
    digitalWrite(LED_BUILTIN, ! digitalRead(LED_BUILTIN));
}

void setup() {

  Serial.begin(115200);

  WiFi.mode(WIFI_STA);
  WiFi.begin(ssid, password);

  while (WiFi.status() != WL_CONNECTED) {
    delay(500);
    Serial.print(F("."));
  }
  Serial.println(F("WiFi connected"));
  Serial.print(F("IP address: "));  Serial.println(WiFi.localIP());
  Serial.print(F("AP  MAC: "));     Serial.println(WiFi.softAPmacAddress());
  Serial.print(F("STA MAC: "));     Serial.println(WiFi.macAddress());

// ----- Start the update Ticker

  updateTicker.attach(updateTickerInterval, updateTickerRoutine);

// These are the registration urls you need to put in a link on a web page of your app

  String update_registration1 = "https://" + String(update_Domain1) + F(":") + String(update_https_Port1)+ F("/rr.html?mac=") + WiFi.softAPmacAddress();
  String update_registration2 = "https://" + String(update_Domain2) + F(":") + String(update_https_Port2)+ F("/rr.html?mac=") + WiFi.softAPmacAddress();

  Serial.println(F("Registration URL 1: ") + update_registration1);
  Serial.println(F("Registration URL 2: ") + update_registration2);
}

void loop() {

  if (triggerUpdate) { updateHandler() ; }

  if (fullMode) {
    // do full mode activities
    flip_LED(String(app_name) + F(" ") + String(app_version) + F(" Full mode "));
    delay(5000);
  } else {
    // do limited mode activities
    flip_LED(String(app_name) + F(" ") + String(app_version) + F(" Limited mode "));
    delay(10000);
  }
}

# WeatherHub Web-API
Web application used as a client for TFA WEATHERHUB SmartHome System meteostation and communicating with Mobile Alerts Cloud. App is written in PHP (Nette framework) and MySQL. It allows to view charts of measured values. This app can be easily customized to support more types of sensors.

## Main info
- this "API" is built on "hacked" communication channel of mobile app
- this app is acting like official mobile application, retireves values from cloud and stores data in MySQL database
- to get it working you need to capture encrypted communication between mobile app and cloud
- there was no other way few years ago...
- the main code is placed in: nette/app/presenters/CronPresenter.php

## Supported sensors
- temperature, humidity, rain and wind

## How to get working?
 - decrypting HTTPS communication is not required anymore, even phoneId is optional
 - ~~setup android app WeatherHub~~
 - ~~install Packet Capture with SSL certificate~~
 - ~~start capture, reload WeatherHub data, save captured data~~
 - ~~enter devicetoken, vendorid and phoneid (and device IDs) into this app~~
 - ~~*there may be a problem on newer devices with sniffing HTTPS communication*~~

## How did I get it working? (admin)
 - download current app in .apk format
 - use apktool do decompile
 - find file (./smali/com/synertronixx/mobilealerts1/RMGlobalData.smali) using: fgrep -ri 'md5' .
 - find salt and excluded characters before calling getMD5EncryptedString
 - modify app according to this information

## Other ways for "API"
- **new REST API (http://www.mobile-alerts.eu)**
  - provided public API is very similar to this app, but it's much easier to setup
  - public API is limited, the most limiting factor is ability to retrieve only last measurement for each sensor (not able to retrieve measurements for whole hour / day etc...), but this should not be a problem for most of usages running 24/7
  - not implemented into this app
- there is some service http://conradconnect.de (https://mobile-alerts.eu/de/conrad-connect/) with support of these devices
- there is also http://wh-observer.de (but not all sensors are supported)
- new https://measurements.mobile-alerts.eu to read all sensors using PhoneID
- **interesting** information about whole WeatherHub system: https://github.com/sarnau/MMMMobileAlerts
- and many other projects including Home Assistant integrations and projects working even without cloud API (local connection to GW) 

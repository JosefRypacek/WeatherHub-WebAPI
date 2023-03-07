# WeatherHub Web-API
Web application used as a client for TFA WEATHERHUB SmartHome System meteostation. App is written in PHP (Nette framework) and MySQL. It allows to view charts of measured values. This app can be easily customized to support more types of sensors.

## Main info
- this "API" is built on "hacked" communication channel of mobile app
- this app is acting like official mobile application, retireves values from cloud and stores data in MySQL database
- to get it working you need to capture encrypted communication between mobile app and cloud
- there was no other way few years ago...

## Supported sensors
- temperature, humidity, rain and wind

## Other ways for "API"
- **the have new REST API (http://www.mobile-alerts.eu)**
  - provided public API is very similar to this app, but it's much more easy to setup
  - public API is limited, the most limiting factor is ability to retrieve only last measurement for each sensor (not able to retrieve measurements for whole hour / day etc...), but this should not be a problem for most of usages
  - not implemented into this app
- there is some service http://conradconnect.de (https://mobile-alerts.eu/de/conrad-connect/) with support of these devices
- there is also http://wh-observer.de (but I'm not sure if it's compatible)

## Source code
Source code is not public, but let me know if you are seriously interested in this project :)

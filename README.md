## ForecastTools ##

Wrapper for [Forecast.io](http://forecast.io/) [API](https://developer.forecast.io/) that supports many simultaneous API calls, substantially reducing wait time for any applications needing to look up weather conditions en masse.

*Note: If you will never want to make more than one request at a time or cache the results (coming later), you might want to try [Guilherm Uhelski](https://github.com/guhelski)’s simpler [forecast-php](https://github.com/guhelski/forecast-php) project.*

### About the Forecast API

> The easiest, most advanced, weather API on the web<br />
> The same API that powers [Forecast.io](http://forecast.io/) and [Dark Sky for iOS](http://darkskyapp.com/) can provide accurate short and long­ term weather predictions to your business, application or crazy idea.


## Power of Multi cURL

The included `example.php` was used to demonstrate various combinations of numbers of total requests and numbers of simultaneous threads. This table shows the results in seconds, where each value is an average of three trials. 

                  Total Requests      
    Threads    10    100    250     500
          1  3.25  31.39  75.19  155.73
          5  1.00   9.49  23.02   43.05
         10  0.65   5.36  14.61   27.04
         25  0.49   5.02   9.90   18.20
         50  0.59   2.93   6.97   14.74
        100  0.50   2.35   7.23   12.14

This shows more than a tenfold speed increase using ForecastTools with 100 threads over using the single execution available in other projects. *Note: The Dark Sky Company recommends 10 threads due to server load concerns, so that is the current default. Please use discretion in changing this value.*


## Requirements

### PHP configuration

To handle multiple simultaneous API calls, this wrapper requires [cURL](http://www.php.net/manual/en/intro.curl.php) and relies on [libcurl-multi](http://curl.haxx.se/libcurl/c/libcurl-multi.html). If you have cURL installed, you likely have what you need.

For single API calls, this wrapper uses cURL if available and falls back on the PHP function [file_get_contents](http://php.net/manual/en/function.file-get-contents.php), which itself requires `allow_url_fopen On` in your php.ini file, which enables [URL-aware fopen wrappers](http://www.php.net/manual/en/filesystem.configuration.php#ini.allow-url-fopen).

### API key

You need an API key, which is available after signing up for [Forecast for Developers](https://developer.forecast.io/).


## Installation

#### FTP program

1. Download [ForecastTools](https://github.com/CNG/ForecastTools/archive/master.zip)
1. Extract the ZIP file
1. Rename `ForecastTools-master` to `ForecastTools` 
1. Upload `ForecastTools` to your web server using an FTP program

#### Console (advanced)

Here is how you could copy the files to your server if your web root is `/var/www/html`:

    $ cd /var/www/html
    $ wget -O ForecastTools.zip https://github.com/CNG/ForecastTools/archive/master.zip
    $ unzip ForecastTools.zip
    $ rm -f ForecastTools.zip
    $ mv ForecastTools-master ForecastTools


## Structure

This project is a series of classes that implement the various branches of the API response. The PHP files in `lib` are thoroughly documented to the point you can rely on them and not need to refer to the official [API documentation](https://developer.forecast.io/docs/v2).

Here is the basic structure:

`Forecast` objects make requests to the API and can return `ForecastResponse` objects that wrap the JSON response. `ForecastResponse` objects can return `ForecastDataPoint` objects for accessing weather conditions, as well as `ForecastAlert` and `ForecastFlags` objects for accessing metadata.


## Usage

### Current conditions, single request

Here is how you could get the current temperature given some GPS coordinates:

    <?php

    require_once 'lib/Forecast.php';

    // The Castro
    $latitude  = 37.770452;
    $longitude = -122.424923;

    // Make request to the API for the current forecast
    $forecast  = new Forecast('YOUR_API_KEY_HERE');
    $response = $forecast->getData($latitude, $longitude);
    $currently = $response->getCurrently();
    $time = date("H:i:s", $currently->getTime());
    $temp = number_format($currently->getTemperature(), 1);
    echo "Temperature in the Castro at $time: $temp&#8457;<br />\n";

    ?>

You can disregard the getter methods of the `ForecastDataPoint`, `ForecastAlerts` and `ForecastFlags` classes and deal directly with the API response. Continuing from above:

    <?php

    $data = $response->getRawData();
    var_dump($data);
    
    ?>

### Historical conditions, many requests

Here is how you could get the temperature at the current time for every month between now and 75 years ago:

    <?php
    
    require_once 'lib/Forecast.php';
    $threads   = 10;
    $forecast  = new Forecast('YOUR_API_KEY_HERE', $threads);
    
    // The Castro
    $latitude  = 37.770452;
    $longitude = -122.424923;
    
    // Build requests for current time each month in last 75 years
    $requests = array();
    for ($i = 0; $i < 75*12; $i++) {
      $requests[] = array(
        'latitude'  => $latitude,
        'longitude' => $longitude,
        'time'      => strtotime("-$i months"),
      );
    }
    
    // Make requests to the API
    $responses = $forecast->getData($requests);
    
    foreach ($responses as $response) {
      if ($currently = $response->getCurrently()) {
        $time = date("Y-m-d H:i:s", $currently->getTime());
        $temp = $currently->getTemperature()
                ? number_format($currently->getTemperature(), 2) . '&#8457;'
                : "unknown";
        echo "$time: $temp<br />\n";
      }
    }
    
    ?>


## Documentation

The PHP files in `lib` are thoroughly documented to the point you can rely on them and not need to refer to the official [API documentation](https://developer.forecast.io/docs/v2).

There is also documentation generated by [phpDocumentor](http://phpdoc.org/) available [on my website](http://votecharlie.com/projects/ForecastTools/docs/index.html), or you can generate your own from source with the command:

    phpdoc -d /path/to/ForecastTools/lib -t docs --template abstract
    
In general, anything can return false if the data is not available or if there is an error, so code defensively.

### Forecast

Instantiate:

    $forecast  = new Forecast('YOUR_API_KEY_HERE');

Single request:

    $response  = $forecast->getData(float, float[, int]);

Multiple simultaneous requests:

    $requests = array(
      array('latitude' => float, 'longitude' => float, 'time' => int),
      array('latitude' => float, 'longitude' => float, 'time' => int),
      array('latitude' => float, 'longitude' => float, 'time' => int),
    );
    $responses = $forecast->getData($requests);

### ForecastResponse

A ForecastResponse object is used to access the various data blocks returned from Forecast.io for a given request. In general, to determine the weather at a given point in time, one should examine the highest-precision data block defined (minutely, hourly, and daily respectively), taking any data available from from it and falling back to the next-highest precision data block for any properties that are missing for the point in time desired.

It is returned by `Forecast->getData()`

Single request:

    $response->getRawData();

Multiple requests:

    foreach ($responses as $response) {
      $response->getRawData();
    }

#### Other methods

Properties of the location of time requested:

- `getAlerts()` returns array of `ForecastAlert` objects
- `getFlags()` returns `ForecastFlags` object
- `getCurrently()` returns array of `ForecastDataPoint` objects
- `getLatitude()`
- `getLongitude()`
- `getOffset()`
- `getTimezone()`

Forecast or historical conditions around location and time requested:

- `getMinutely()` returns array of `ForecastDataPoint` objects
- `getHourly()` returns array of `ForecastDataPoint` objects
- `getDaily()` returns array of `ForecastDataPoint` objects

The previous three methods take an optional zero based integer argument to return a specific `ForecastDataPoint` object in the set. Can be used in conjunction with `getCount(string)`.

- `getCount($type)` returns number of ForecastDataPoint objects that exist within specified block. $type can be 'minutely','hourly' or 'daily'.

#### ForecastDataPoint

A ForecastDataPoint object represents the various weather phenomena occurring at a specific instant of time, and has many varied methods. All of these methods (except time) are optional, and will only be set if we have that type of information for that location and time. Please note that minutely data points are always aligned to the nearest minute boundary, hourly points to the top of the hour, and daily points to midnight of that day. Data points in the daily data block (see below) are special: instead of representing the weather phenomena at a given instant of time, they are an aggregate point representing the weather phenomena that will occur over the entire day. For precipitation fields, this aggregate is a maximum; for other fields, it is an average. The following are not implemented as get functions due to lack of documentation: All of the data oriented methods may have an associated error value defined, representing our system’s confidence in its prediction. Such properties represent standard deviations of the value of their associated property; small error values therefore represent a strong confidence, while large error values represent a weak confidence. These properties are omitted where the confidence is not precisely known (though generally considered to be adequate).

- `getTime()` returns the UNIX time (that is, seconds since midnight GMT on 1 Jan 1970) at which this data point occurs.
- `getSummary()` returns a human-readable text summary of this data point.
- `getIcon()` returns a machine-readable text summary of this data point, suitable for selecting an icon for display. If defined, this property will have one of the following values: clear-day, clear-night, rain, snow, sleet, wind, fog, cloudy, partly-cloudy-day, or partly-cloudy-night. (Developers should ensure that a sensible default is defined, as additional values, such as hail, thunderstorm, or tornado, may be defined in the future.)
- `getSunriseTime()` returns (only defined on daily data points) the UNIX time (that is, seconds since midnight GMT on 1 Jan 1970) of sunrise and sunset on the given day. (If no sunrise or sunset will occur on the given day, then the appropriate fields will be undefined. This can occur during summer and winter in very high or low latitudes.)
- `getSunsetTime()` returns (only defined on daily data points) the UNIX time (that is, seconds since midnight GMT on 1 Jan 1970) of sunrise and sunset on the given day. (If no sunrise or sunset will occur on the given day, then the appropriate fields will be undefined. This can occur during summer and winter in very high or low latitudes.)
- `getPrecipIntensity()` returns a numerical value representing the average expected intensity (in inches of liquid water per hour) of precipitation occurring at the given time conditional on probability (that is, assuming any precipitation occurs at all). A very rough guide is that a value of 0 in./hr. corresponds to no precipitation, 0.002 in./hr. corresponds to very light precipitation, 0.017 in./hr. corresponds to light precipitation, 0.1 in./hr. corresponds to moderate precipitation, and 0.4 in./hr. corresponds to heavy precipitation.
- `getPrecipIntensityMax()` returns (only defined on daily data points) numerical values representing the maximumum expected intensity of precipitation (and the UNIX time at which it occurs) on the given day in inches of liquid water per hour.
- `getPrecipIntensityMaxTime()` returns (only defined on daily data points) numerical values representing the maximumum expected intensity of precipitation (and the UNIX time at which it occurs) on the given day in inches of liquid water per hour.
- `getPrecipProbability()` returns a numerical value between 0 and 1 (inclusive) representing the probability of precipitation occuring at the given time.
- `getPrecipType()` returns a string representing the type of precipitation occurring at the given time. If defined, this method will have one of the following values: rain, snow, sleet (which applies to each of freezing rain, ice pellets, and “wintery mix”), or hail. (If getPrecipIntensity() returns 0, then this method should return false.)
- `getPrecipAccumulation()` returns (only defined on daily data points) the amount of snowfall accumulation expected to occur on the given day. (If no accumulation is expected, this method should return false.)
- `getTemperature()` returns (not defined on daily data points) a numerical value representing the temperature at the given time in degrees Fahrenheit.
- `getTemperatureMin()` returns (only defined on daily data points) numerical value representing the minimum temperatures on the given day in degrees Fahrenheit.
- `getTemperatureMinTime()` returns (only defined on daily data points) numerical values representing the minimum temperatures (and the UNIX times at which they occur) on the given day in degrees Fahrenheit.
- `getTemperatureMax()` returns (only defined on daily data points) numerical values representing the maximumum temperatures (and the UNIX times at which they occur) on the given day in degrees Fahrenheit.
- `getTemperatureMaxTime()` returns (only defined on daily data points) numerical values representing the maximumum temperatures (and the UNIX times at which they occur) on the given day in degrees Fahrenheit.
- `getApparentTemperature()` returns (not defined on daily data points) a numerical value representing the apparent (or “feels like”) temperature at the given time in degrees Fahrenheit.
- `getApparentTemperatureMin()` returns (only defined on daily data points) numerical value representing the minimum apparent temperatures on the given day in degrees Fahrenheit.
- `getApparentTemperatureMinTime()` returns (only defined on daily data points) numerical values representing the minimum apparent temperatures (and the UNIX times at which they occur) on the given day in degrees Fahrenheit.
- `getApparentTemperatureMax()` returns (only defined on daily data points) numerical values representing the maximumum apparent temperatures (and the UNIX times at which they occur) on the given day in degrees Fahrenheit.
- `getApparentTemperatureMaxTime()` returns (only defined on daily data points) numerical values representing the maximumum apparent temperatures (and the UNIX times at which they occur) on the given day in degrees Fahrenheit.
- `getDewPoint()` returns a numerical value representing the dew point at the given time in degrees Fahrenheit.
- `getWindSpeed()` returns a numerical value representing the wind speed in miles per hour.
- `getWindBearing()` returns a numerical value representing the direction that the wind is coming from in degrees, with true north at 0° and progressing clockwise. (If getWindSpeed is zero, then this value will not be defined.)
- `getCloudCover()` returns a numerical value between 0 and 1 (inclusive) representing the percentage of sky occluded by clouds. A value of 0 corresponds to clear sky, 0.4 to scattered clouds, 0.75 to broken cloud cover, and 1 to completely overcast skies.
- `getHumidity()` returns a numerical value between 0 and 1 (inclusive) representing the relative humidity.
- `getPressure()` returns a numerical value representing the sea-level air pressure in millibars.
- `getVisibility()` returns a numerical value representing the average visibility in miles, capped at 10 miles.
- `getOzone()` returns a numerical value representing the columnar density of total atmospheric ozone at the given time in Dobson units.

#### ForecastAlert

An alert object represents a severe weather warning issued for the requested location by a governmental authority (for a list of which authorities we currently support, please see data sources at https://developer.forecast.io/docs/v2

- `getDescription()` returns detailed text description of the alert from appropriate weather service.
- `getExpires()` returns the UNIX time (that is, seconds since midnight GMT on 1 Jan 1970) at which the alert will cease to be valid.
- `getTitle()` returns a short text summary of the alert.
- `getURI()` returns the HTTP(S) URI that contains detailed information about the alert.

#### ForecastFlags

The flags object contains various metadata information related to the request.

- `getDarkskyUnavailable()` The presence of this property indicates that the Dark Sky data source supports the given location, but a temporary error (such as a radar station being down for maintenace) has made the data unavailable.
- `getDarkskyStations()` returns an array of IDs for each radar station utilized in servicing this request.
- `getDatapointStations()` returns an array of IDs for each DataPoint station utilized in servicing this request.
- `getISDStations()` returns an array of IDs for each ISD station utilized in servicing this request.
- `getLAMPStations()` returns an array of IDs for each LAMP station utilized in servicing this request.
- `getMETARStations()` returns an array of IDs for each METAR station utilized in servicing this request.
- `getMetnoLicense()` The presence of this property indicates that data from api.met.no was utilized in order to facilitate this request (as per their license agreement). 
- `getSources()` returns an array of IDs for each data source utilized in servicing this request.
- `getUnits()` The presence of this property indicates which units were used for the data in this request. 


## Notes

I have run into some inconsistent errors when testing many (1000+) requests over and over (10+ times), such as cURL coming back with an error like:

    Unknown SSL protocol error in connection to api.forecast.io:443
    
The errors do not happen when doing fewer requests in a short period, so I attributed it to a service limitation. This needs to be explored more if you intend to use ForecastTools to do massive numbers of requests.


## Changelog

- Version 1.0: 7 October 2013
  - First release


## Improvements to come

I wrote another layer for use in my own app, [Weatherbit](http://votecharlie.com/weatherbit/), that caches requests to a MySQL database to prevent redundant queries to the API. This is especially useful for applications that are likely to make repeated requests for the same information, such as a Weatherbit user checking his 30 day chart every day. I plan to abstract this a bit and include with this wrapper eventually. If you want to see what I did sooner, feel free to send me a message.
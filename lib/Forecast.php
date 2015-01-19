<?php
/**
 * Forecast.php
 */

/**
 * ForecastTools 
 *
 * Forecast.io Forecast API v2 lets you query for almost anywhere and returns:
 * <ul>
 *   <li>Current conditions</li>
 *   <li>Minute by minute forecasts out to 1 hour where available</li>
 *   <li>Hour by hour forecasts out to 48 hours</li>
 *   <li>Day by day forecasts out to 7 days</li>
 * </ul>
 * 
 * You can get the current forecast or query for a specific time, up to 60 years
 * in the past up to 10 years in the future. 
 * 
 * This wrapper handles many simultaneous requests with cURL or single requests 
 * without cURL.
 * 
 * @package ForecastTools
 * @author  Charlie Gorichanaz <charlie@gorichanaz.com>
 * @license http://opensource.org/licenses/MIT The MIT License
 * @version 1.0
 * @link    http://github.com/CNG/ForecastTools
 * @example ../example.php 
 */
class Forecast
{

  private $_api_key;
  private $_threads; // multi cURL simultaneous requests
  const   API_URL = 'https://api.forecast.io/forecast/';

  /**
   * Create Forecast object
   * 
   * @param string  $api_key API key obtained from 
   * <a href="https://developer.forecast.io">Forecast.io</a>
   * @param integer $threads Number of requests to process simultaneously.
   * Default is 10.
   */
  public function __construct($api_key, $threads = 10)
  {
    $this->_api_key = $api_key;
    $this->_threads = $threads;
  }

  /**
   * Make requests to API
   * 
   * @param array $requests_data Requests to process. Each element is 
   * <pre>
   * array('
   *   latitude' => float,
   *   'longitude' => float,
   *   [
   *     'time' => int,
   *     'units' => string,
   *     'exclude' => string,
   *     'extend' => string,
   *     'callback' => string
   *   ]
   * )
   * </pre>
   * 
   * @return array JSON decoded responses or false values for
   *                                   each request element
   */
  private function _request($requests_data)
  {

    $request_urls   = array(); // final URLs to process
    $responses      = array(); // raw responses from API
    $nice_responses = array(); // json_decoded values or false on errors

    // convert arrays of search parameters to request URLs
    foreach ($requests_data as $request_data) {

      // required attributes
      $latitude  = $request_data['latitude'];
      $longitude = $request_data['longitude'];
      // optional attributes
      $time      = empty($request_data['time']) ? null : $request_data['time'];
      $options   = array();
      if (!empty($request_data['units'])) {
        $options['units'] = $request_data['units'];
      }
      if (!empty($request_data['exclude'])) {
        $options['exclude'] = $request_data['exclude'];
      }
      if (!empty($request_data['extend'])) {
        $options['extend'] = $request_data['extend'];
      }
      if (!empty($request_data['callback'])) {
        $options['callback'] = $request_data['callback'];
      }
      $options = http_build_query($options);

      $request_url = self::API_URL
        . $this->_api_key
        . "/$latitude,$longitude"
        . ($time ? ",$time" : '')
        . ($options ? "?$options" : '');

      $request_urls[] = $request_url;

    }

    // select method for making requests, preferring multi cURL
    if (function_exists('curl_multi_select') && $this->_threads > 1) {
      $responses = $this->_processWithMultiCurl($request_urls);
    } elseif (function_exists('curl_version')) {
      $responses = $this->_processWithSingleCurl($request_urls);
    } else {
      $responses = $this->_processWithFileGetContents($request_urls);
    }

    // JSON decode responses and log any problems with them
    // (but do not choke on problems, just return false in those cases)
    foreach ($responses as $response) {

      if (empty($response)) {
        // decided to just let developers check for false instead of forcing
        // everyone to implement full exception handling
        $err = 'At least one of the API responses was empty.';
        trigger_error(__FILE__ . ':L' . __LINE__ . ": $err\n");
        $nice_responses[] = false;
      } else {
        $decoded = json_decode($response);
        if ($decoded === null) {
          $err = 'Cannot decode one of the API responses.';
          trigger_error(__FILE__ . ':L' . __LINE__ . ": $err\n");
          $nice_responses[] = false;
        } else {
          $nice_responses[] = $decoded;
        }
      }

    }

    return $nice_responses;

  }

  /**
   * Make requests using multi cURL
   * 
   * @param array $request_urls Simple array of URLs to run through Multi cURL
   * 
   * @return array Simple array of responses from URL queries where array keys 
   * correspond to input array keys.
   */
  private function _processWithMultiCurl($request_urls)
  {

    $threads = $this->_threads; // requests to handle simultaneously
    $responses = array();
    $iterations = floor(count($request_urls) / $threads);
    if (count($request_urls) % $threads) {
      $iterations++;
    }

    for ($j = 0; $j < $iterations; $j++) {

      $request_urls_slice = array_slice($request_urls, $j * $threads, $threads);
      $responses_part = array();

      $mh = curl_multi_init();
      foreach ($request_urls_slice as $i => $url) {
        $ch[$i] = curl_init($url);
        curl_setopt($ch[$i], CURLOPT_RETURNTRANSFER, 1);
        curl_multi_add_handle($mh, $ch[$i]);
      }
      
      // Following block replaces the commented out block below
      // Old code worked w/ Ubuntu 12.04/Apache2.2, but not on Ubuntu 14.04 with Apache 2.4 and PHP-FPM
      // Don't have time for more specifics at moment, but if you experience issues, try swapping out the blocks
      
      $running = null;
      do {
        $execReturnValue = curl_multi_exec($mh, $running);
      } while($running > 0);
      
      /*
      do {
          $execReturnValue = curl_multi_exec($mh, $runningHandles);
      } while ($execReturnValue == CURLM_CALL_MULTI_PERFORM);
      while ($runningHandles && $execReturnValue == CURLM_OK) {
        $numberReady = curl_multi_select($mh);
        if ($numberReady != -1) {
          do {
            $execReturnValue = curl_multi_exec($mh, $runningHandles);
          } while ($execReturnValue == CURLM_CALL_MULTI_PERFORM);
        }
      }
      */
      
      if ($execReturnValue != CURLM_OK) {
        $err = "Multi cURL read error $execReturnValue";
        trigger_error(__FILE__ . ':L' . __LINE__ . ": $err\n");
      }

      foreach ($request_urls_slice as $i => $url) {

        $curlError = curl_error($ch[$i]);
        if ($curlError == "") {
          $responses_part[$i] = curl_multi_getcontent($ch[$i]);
        } else {
          $responses_part[$i] = false;
          $err = "Multi cURL error on handle $i: $curlError";
          trigger_error(__FILE__ . ':L' . __LINE__ . ": $err\n");
        }
        curl_multi_remove_handle($mh, $ch[$i]);
        curl_close($ch[$i]);

      }
      curl_multi_close($mh);

      $responses = array_merge($responses, $responses_part);

    }

    return $responses;

  }

  /**
   * Make requests using regular cURL (not multi)
   * 
   * @param array $request_urls Simple array of URLs to run through cURL
   * 
   * @return array Simple array of responses from URL queries where array keys
   * correspond to input array keys.
   */
  private function _processWithSingleCurl($request_urls)
  {

    $responses = array();

    foreach ($request_urls as $request_url) {

      $ch1 = curl_init();
      curl_setopt($ch1, CURLOPT_URL, $request_url);
      curl_setopt($ch1, CURLOPT_HEADER, 0);
      curl_setopt($ch1, CURLOPT_RETURNTRANSFER, 1);
      $response = curl_exec($ch1);
      $responses[] = $response;
      if ($response === false) {
        $curlError = curl_error($ch1);
        $err = "cURL error: $curlError";
        trigger_error(__FILE__ . ':L' . __LINE__ . ": $err\n");
      }
      curl_close($ch1);

    }

    return $responses;

  }

  /**
   * Make requests using file_get_contents (not cURL)
   * 
   * @param array $request_urls Simple array of URLs to run through 
   * file_get_contents()
   * 
   * @return array Simple array of responses from URL queries where array keys 
   * correspond to input array keys.
   */
  private function _processWithFileGetContents($request_urls)
  {

    $responses = array();

    foreach ($request_urls as $request_url) {
      
      /**
        * Use Buffer to cache API-requests if initialized
        * (if not, just get the latest data)
        * 
        * More info: http://git.io/FoO2Qw
        */
      
      if(class_exists('Buffer')) {
        $cache = new Buffer();
        $response = $cache->data($request_url);
      } else {
        $response = file_get_contents($request_url);
      }
      
      $responses[] = $response;
      if ($response === false) {
        trigger_error(__FILE__ . ':L' . __LINE__ . ": Error on file_get_contents($request_url)\n");
      }

    }

    return $responses;

  }


  /**
   * Retrieve ForecastResponse objects for each set of location and time.
   *
   * You can call the function with either the values for one API request or an
   * array or arrays, with one subarray for each API request. Therefore making
   * one API request can be done by passing in latitude, longitude, etc., or by
   * passing an array containing one array. Making multiple simultaneous API
   * requests must be done by passing an array of arrays. The return value will
   * typically be either an array of ForecastResponse objects or a single such
   * object. If you pass in an array, you will get an array back.
   * 
   * For each request, either a ForecastResponse object or false will be
   * returned, so you must check for false. This can indicate the data is not
   * available for that request. 
   * 
   * If invalid parameters are passed, this can throw a ForecastException. If
   * other errors occur, such as a problem making the request or data not being
   * available, the response will generally just be false. Some errors are 
   * logged with trigger_error to the same location as PHP warnings and notices.
   * You must therefore write code in a way that will handle false values. You
   * probably do not need to handle the ForecastException unless your production
   * code might result in variable parameters or formats.
   * 
   * @param mixed $args1 Pass either of the following:
   * <ol>
   *   <li>
   *     One parameter that is an array of one or more associative arrays like
   *     <pre>
   *       array(
   *         'latitude'  => float,
   *         'longitude' => float,
   *         'time'      => int,
   *         'units'     => string,
   *         'exclude'   => string,
   *         'extend'    => string,
   *         'callback'  => string,
   *       )
   *     </pre>
   *     with only the latitiude and longitude required
   *   </li>
   *   <li>
   *     Two to seven parameters in this order: latitude float, longitude float,
   *     time int, units string, exclude string, extend string, callback string
   *   </li>
   * </ul>
   * 
   * @return array|ForecastResponse|bool If array passed in, returns array of 
   * ForecastIOConditions objects or false vales. Otherwise returns a single 
   * ForecastResponse object or false value.
   * @throws ForecastException If invalid parameters used
   */
  public function getData($args1)
  {
    /*
      This implementation is a little messy since I am allowing parameters to
      be passed individually or within arrays. Ideally I would require arrays of
      arrays with named keys, but I want to maintain compatibility with existing
      Forecast.io PHP implementations.
    */
    $requests; // will hold array of arrays of lat/long/time/options
    $return_array = true; // if params not passed as array, don't return array
    if (func_num_args() == 1 && is_array(func_get_arg(0))) {
      $requests = func_get_arg(0);
    } elseif (func_num_args() > 1 && is_numeric(func_get_arg(0))
        && is_numeric(func_get_arg(1))
    ) {
      $return_array = false;
      $requests = array(
        array('latitude' => func_get_arg(0), 'longitude' => func_get_arg(1))
      );
      if (func_num_args() > 2 && is_int(func_get_arg(2))) {
        $requests[0]['time'] = func_get_arg(2);
      }
      if (func_num_args() > 3 && is_string(func_get_arg(3))) {
        $requests[0]['units'] = func_get_arg(3);
      }
      if (func_num_args() > 4 && is_string(func_get_arg(4))) {
        $requests[0]['exclude'] = func_get_arg(4);
      }
      if (func_num_args() > 5 && is_string(func_get_arg(5))) {
        $requests[0]['extend'] = func_get_arg(5);
      }
      if (func_num_args() > 6 && is_string(func_get_arg(6))) {
        $requests[0]['callback'] = func_get_arg(6);
      }
    } else {
      include_once 'ForecastException.php';
      throw new ForecastException(__FUNCTION__ . " called with invalid parameters.");
    }

    $json_results = $this->_request($requests);

    // Wrap JSON responses in ForecastResponse objects or leave as false and
    // log the error to the error log
    $conditions = array();
    include_once 'ForecastResponse.php';
    foreach ($json_results as $result) {
      if ($result !== false) {
        $conditions[] = new ForecastResponse($result);
      } else {
        $conditions[] = false;
        trigger_error(__FILE__ . ':L' . __LINE__ . ": Failed to retrieve conditions.\n");
      }
    }

    // if request included a single API call and was not wrapped in an array,
    // return the ForecastResponse or false alone, otherwise return array
    if ($return_array) {
      return $conditions;
    } else {
      return $conditions[0];
    }

  }

}

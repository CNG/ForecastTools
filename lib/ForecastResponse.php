<?php
/**
 * ForecastResponse.php
 */

/**
 * ForecastTools: Response
 *
 * A ForecastResponse object is used to access the various data blocks returned
 * from Forecast.io for a given request. In general, to determine the weather
 * at a given point in time, one should examine the highest-precision data block
 * defined (minutely, hourly, and daily respectively), taking any data available
 * from from it and falling back to the next-highest precision data block for
 * any properties that are missing for the point in time desired.
 *
 * @package ForecastTools
 * @author  Charlie Gorichanaz <charlie@gorichanaz.com>
 * @license http://opensource.org/licenses/MIT The MIT License
 * @version 1.0
 * @link    http://github.com/CNG/ForecastTools
 * @example ../example.php 
 */
class ForecastResponse
{

  private $_response;

  /**
   * Create ForecastResponse object
   * 
   * @param object $response Entire JSON decoded response from API
   */
  public function __construct($response)
  {
    $this->_response = $response;
  }

  /**
   * Get a JSON formatted object that is the entire response from Forecast.io.
   * This is useful if you do not wish to use any of the get methods provided
   * by this class or for accessing new or otherwise not otherwise accessible
   * data in the response.
   *
   * @return Object JSON-formatted object with the following properties defined:
   * latitude, longitude, timezone, offset, currently[, minutely, hourly, daily,
   * alerts, flags]
   */
  public function getRawData()
  {
    return $this->_response;
  }

  /**
   * The requested latitude.
   *
   * @return float The requested latitude
   */
  public function getLatitude()
  {
    $field = 'latitude';
    return property_exists($this->_response->$field);
  }

  /**
   * The requested longitude.
   *
   * @return float The requested longitude
   */
  public function getLongitude()
  {
    $field = 'longitude';
    return property_exists($this->_response->$field);
  }

  /**
   * The IANA timezone name for the requested location (e.g. America/New_York).
   * This is the timezone used for text forecast summaries and for determining
   * the exact start time of daily data points. (Developers are advised to rely
   * on local system settings rather than this value if at all possible: users
   * may deliberately set an unusual timezone, and furthermore are likely to
   * know what they actually want better than our timezone database does.)
   *
   * @return string The IANA timezone name for the requested location
   */
  public function getTimezone()
  {
    $field = 'timezone';
    return property_exists($this->_response->$field);
  }

  /**
   * The current timezone offset in hours from GMT.
   *
   * @return string The current timezone offset in hours from GMT.
   */
  public function getOffset()
  {
    $field = 'offset';
    return property_exists($this->_response->$field);
  }

  /**
   * Get number of ForecastDataPoint objects that exist within specified block
   *
   * @param string $type Type of data block
   * 
   * @return int Returns number of ForecastDataPoint objects that exist within
   * specified block
   */
  public function getCount($type)
  {
    $response = $this->_response;
    return empty($response->$type->data) ? false : count($response->$type->data);
  }

  /**
   * Get ForecastDataPoint object for current or specified time
   *
   * @return ForecastDataPoint ForecastDataPoint object for current or specified time
   */
  public function getCurrently()
  {
    include_once 'ForecastDataPoint.php';
    return new ForecastDataPoint($this->_response->currently);
  }

  /**
   * Get ForecastDataPoint object(s) desired within the specified block
   *
   * @param string $type  Type of data block (
   * @param int    $index Optional numeric index of desired data point in block 
   * beginning with 0
   * 
   * @return array|ForecastDataPoint|bool Returns an array of ForecastDataPoint 
   * objects within the block OR a single ForecastDataPoint object for specified
   * block OR false if no applicable block
   */
  private function _getBlock($type, $index = null)
  {

    if ($this->getCount($type)) {

      include_once 'ForecastDataPoint.php';
      $block_data = $this->_response->$type->data;
      if (is_null($index)) {
        $points = array();
        foreach ($block_data as $point_data) {
          $points[] = new ForecastDataPoint($point_data);
        }
        return $points;
      } elseif (is_int($index) && $this->getCount($type) > $index) {
        return new ForecastDataPoint($block_data[$index]);
      }

    }
    return false; // if no block, block but no data, or invalid index specified

  }

  /**
   * Get ForecastDataPoint object(s) desired within the minutely block, which is
   * weather conditions minute-by-minute for the next hour.
   *
   * @param int $index Optional numeric index of desired data point in block 
   * beginning with 0
   * 
   * @return array|ForecastDataPoint|bool Returns an array of ForecastDataPoint 
   * objects within the block OR a single ForecastDataPoint object for specified
   * block OR false if no applicable block
   */
  public function getMinutely($index = null)
  {
    $type = 'minutely';
    return $this->_getBlock($type, $index);
  }

  /**
   * Get ForecastDataPoint object(s) desired within the hourly block, which is
   * weather conditions hour-by-hour for the next two days.
   *
   * @param int $index Optional numeric index of desired data point in block 
   * beginning with 0
   * 
   * @return array|ForecastDataPoint|bool Returns an array of ForecastDataPoint 
   * objects within the block OR a single ForecastDataPoint object for specified 
   * block OR false if no applicable block
   */
  public function getHourly($index = null)
  {
    $type = 'hourly';
    return $this->_getBlock($type, $index);
  }

  /**
   * Get ForecastDataPoint object(s) desired within the daily block, which is
   * weather conditions day-by-day for the next week.
   *
   * @param int $index Optional numeric index of desired data point in block 
   * beginning with 0
   * 
   * @return array|ForecastDataPoint|bool Returns an array of ForecastDataPoint 
   * objects within the block OR a single ForecastDataPoint object for specified
   * block OR false if no applicable block
   */
  public function getDaily($index = null)
  {
    $type = 'daily';
    return $this->_getBlock($type, $index);
  }

  /**
   * Get an array of ForecastAlert objects, which, if present, contain any
   * severe weather alerts, issued by a governmental weather authority,
   * pertinent to the requested location.
   *
   * @return array|bool Array of ForecastAlert objects OR false if none
   */
  public function getAlerts()
  {

    if (!empty($this->_response->alert)) {
      include_once 'ForecastAlert.php';
      $alerts = array();
      foreach ($this->_response->alert as $alert) {
        $alerts[] = new ForecastAlert($alert);
      }
      return $alerts;
    } else {
      return false;
    }

  }

  /**
   * Get ForecastFlags object of miscellaneous metadata concerning this request.
   *
   * @return ForecastFlags|bool ForecastFlags object OR false if none
   */
  public function getFlags()
  {

    if (!empty($this->_response->flags)) {
      include_once 'ForecastFlags.php';
      return new ForecastFlags($this->_response->flags);
    } else {
      return false;
    }

  }

}

<?php
/**
 * ForecastAlert.php
 */

/**
 * ForecastTools: Alert
 *
 * An alert object represents a severe weather warning issued for the requested
 * location by a governmental authority (for a list of which authorities we
 * currently support, please see data sources at
 * https://developer.forecast.io/docs/v2
 *
 * @package ForecastTools
 * @author  Charlie Gorichanaz <charlie@gorichanaz.com>
 * @license http://opensource.org/licenses/MIT The MIT License
 * @version 1.0
 * @link    http://github.com/CNG/ForecastTools
 * @example ../example.php 
 */
class ForecastAlert
{

  private $_alert;

  /**
   * Create ForecastAlert object
   * 
   * @param object $alert JSON decoded alert from API response
   */
  public function __construct($alert)
  {
    $this->_alert = $alert;
  }

  /**
   * A short text summary of the alert.
   *
   * @return string|bool alert “title” data or false if none
   */
  public function getTitle()
  {
    $field = 'title';
    return empty($this->_alert->$field) ? false : $this->_alert->$field;
  }

  /**
   * The UNIX time (that is, seconds since midnight GMT on 1 Jan 1970) at which 
   * the alert will cease to be valid.
   *
   * @return int|bool alert “expires” data or false if none
   */
  public function getExpires()
  {
    $field = 'expires';
    return empty($this->_alert->$field) ? false : $this->_alert->$field;
  }

  /**
   * A detailed text description of the alert from appropriate weather service.
   *
   * @return string|bool alert “description” data or false if none
   */
  public function getDescription()
  {
    $field = 'description';
    return empty($this->_alert->$field) ? false : $this->_alert->$field;
  }

  /**
   * An HTTP(S) URI that contains detailed information about the alert.
   *
   * @return string|bool alert “URI” data or false if none
   */
  public function getURI()
  {
    $field = 'uri';
    return empty($this->_alert->$field) ? false : $this->_alert->$field;
  }


}

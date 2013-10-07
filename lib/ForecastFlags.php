<?php
/**
 * ForecastFlags.php
 */

/**
 * ForecastTools: Flags
 *
 * The flags object contains various metadata information related to the request.
 *
 * @package ForecastTools
 * @author  Charlie Gorichanaz <charlie@gorichanaz.com>
 * @license http://opensource.org/licenses/MIT The MIT License
 * @version 1.0
 * @link    http://github.com/CNG/ForecastTools
 * @example ../example.php 
 */
class ForecastFlags
{

  private $_flags;

  /**
   * Create ForecastFlags object
   * 
   * @param object $flags JSON decoded flags from API response
   */
  public function __construct($flags)
  {
    $this->_flags = $flags;
  }

  /**
   * The presence of this property indicates that the Dark Sky data source 
   * supports the given location, but a temporary error (such as a radar station 
   * being down for maintenace) has made the data unavailable.
   *
   * @return bool true if flags object has “darksky-unavailable” property or 
   * false if not
   */
  public function getDarkskyUnavailable()
  {
    $field = 'darksky-unavailable';
    return property_exists($this->_flags->$field);
  }

  /**
   * This property contains an array of IDs for each radar station utilized in 
   * servicing this request.
   *
   * @return string|bool flags object “darksky-stations” data or false if none
   */
  public function getDarkskyStations()
  {
    $field = 'darksky-stations';
    return empty($this->_flags->$field) ? false : $this->_flags->$field;
  }

  /**
   * This property contains an array of IDs for each DataPoint station utilized 
   * in servicing this request.
   *
   * @return string|bool flags object “datapoint-stations” data or false if none
   */
  public function getDatapointStations()
  {
    $field = 'datapoint-stations';
    return empty($this->_flags->$field) ? false : $this->_flags->$field;
  }

  /**
   * This property contains an array of IDs for each ISD station utilized in 
   * servicing this request.
   *
   * @return string|bool flags object “isd-stations” data or false if none
   */
  public function getISDStations()
  {
    $field = 'isd-stations';
    return empty($this->_flags->$field) ? false : $this->_flags->$field;
  }

  /**
   * This property contains an array of IDs for each LAMP station utilized in 
   * servicing this request.
   *
   * @return string|bool flags object “lamp-stations” data or false if none
   */
  public function getLAMPStations()
  {
    $field = 'lamp-stations';
    return empty($this->_flags->$field) ? false : $this->_flags->$field;
  }

  /**
   * This property contains an array of IDs for each METAR station utilized in 
   * servicing this request.
   *
   * @return string|bool flags object “metar-stations” data or false if none
   */
  public function getMETARStations()
  {
    $field = 'metar-stations';
    return empty($this->_flags->$field) ? false : $this->_flags->$field;
  }

  /**
   * The presence of this property indicates that data from api.met.no was
   * utilized in order to facilitate this request (as per their license 
   * agreement).
   *
   * @return bool true if flags object has “metno-license” property or false if 
   * not
   */
  public function getMetnoLicense()
  {
    $field = 'metno-license';
    return property_exists($this->_flags->$field);
  }

  /**
   * This property contains an array of IDs for each data source utilized in 
   * servicing this request.
   *
   * @return string|bool flags object “sources” data or false if none
   */
  public function getSources()
  {
    $field = 'sources';
    return empty($this->_flags->$field) ? false : $this->_flags->$field;
  }

  /**
   * The presence of this property indicates which units were used for the data 
   * in this request.
   *
   * @return string|bool flags object “units” data or false if none
   */
  public function getUnits()
  {
    $field = 'units';
    return empty($this->_flags->$field) ? false : $this->_flags->$field;
  }

}

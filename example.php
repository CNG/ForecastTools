<?php
/**
 * example.php
 */

require_once 'lib/Forecast.php';

header('Content-Type: text/html; charset=utf-8');

// Forecast.io API key from http://developer.forecast.io/
$api_key = 'YOUR_API_KEY_HERE';

/**
 * Get GPS coordinates from location string using the Google Maps API
 *
 * @param string $addr Address, ZIP code, etc.
 *
 * @return array latitude and longitude
 */
function geoLocate($addr)
{
  $geoapi = "http://maps.googleapis.com/maps/api/geocode/json";
  $params = 'address='.str_replace(" ", "+", $addr).'&sensor=false';
  $response = file_get_contents("$geoapi?$params");
  $json = json_decode($response);
  return array(
    $json->results[0]->geometry->location->lat,
    $json->results[0]->geometry->location->lng
  );
}

?><!DOCTYPE html>
<html>
<head>
  <title>ForecastTools example</title>
  <style>
    body { font-family: "sans-serif"; }
    table, td { border: 1px solid #999; }
    table { border-collapse: collapse; border-spacing:0;}
    .label, .ave { font-weight: bold; }
    .val { font-family: "monospace"; }
  </style>
</head>
<body>

<?php
// example of getting GPS coordinates from an address
list($latitude, $longitude) = geoLocate('2300 Market St., San Francisco, CA');

// build array of requests for each month going back 75 years
$this_day_in_history = array(); // will hold 900 items
for ($i = 0; $i < 75*12; $i++) {
  $this_day_in_history[] = array(
    'latitude'  => $latitude,
    'longitude' => $longitude,
    'time'      => strtotime("-$i months"),
  );
}

$requests = array(1, 10, 100);
$threads  = array(1, 5, 10, 25, 50, 100);
$trials   = 2;

$index = 0; // count how many trials we did
echo "Of " . count($requests) * count($threads) * $trials . " trials, running:";

$results = array(); // $results['num_requests']['num_threads']['trial']
for ($i = 0; $i < count($requests); $i++) {
  for ($j = 0; $j < count($threads); $j++) {
    for ($k = 0; $k < $trials; $k++) {
      $index++;
      echo " $index";
      $sample    = array_slice($this_day_in_history, 0, $requests[$i]);
      $start     = microtime(true);
      $forecast  = new Forecast($api_key, $threads[$j]);
      $responses = $forecast->getData($sample);
      $end       = microtime(true);
      $results[$i][$j][$k] = $end - $start; // duration
      echo ";";
      sleep(20);
    }
  }
}

echo "<br />\n";
?>

<!--
<?php print_r($results); ?>
-->

<h1>Request processing time in seconds</h1>

<table>
  <tr>
    <td></td>
    <td></td>
    <td class="label" colspan="<?php echo count($requests); ?>">Requests</td>
  </tr>
  <tr>
    <td class="label">Threads</td>
    <td class="label">Trial</td>
    <?php
    for ($j = 0; $j < count($requests); $j++) {
      echo '<td class="label">'.$requests[$j]."</td>";
    }
    ?>
  </tr>
  <?php
  for ($j = 0; $j < count($threads); $j++) {
  ?>
  <tr>
    <td class="label" rowspan="<?php echo $trials + 1; ?>"><?php echo $threads[$j]; ?></td>
    <?php
    for ($k = 0; $k < $trials; $k++) {
      echo '<td>'.($k+1)."</td>";
      for ($i = 0; $i < count($requests); $i++) {
        echo '<td class="val">'.sprintf('%04.6f', $results[$i][$j][$k])."</td>";
      }
    ?>
    </tr>
    <tr>
    <?php
    }
    ?>
    <td class="ave">Ave</td>
    <?php
    for ($i = 0; $i < count($requests); $i++) {
      $average = array_sum($results[$i][$j]) / count($results[$i][$j]);
      echo '<td class="ave val">'.sprintf('%04.6f', $average)."</td>";
    }
    ?>
  </tr>
  <?php
  }
  ?>
</table>

<h1>Temperature history</h1>
<?php
foreach ($responses as $response) {
  if (!empty($response)) {
    $currently = $response->getCurrently();
    $time = date("Y-m-d H:i:s", $currently->getTime());
    $temp = number_format($currently->getTemperature(), 2);
    echo "$time: $temp&#8457;<br />\n";
  }
}
?>

</body>
</html>
<?php
$refresh_interval = isset($_ENV['REFRESH_INTERVAL']) ? $_ENV['REFRESH_INTERVAL'] : 5;
$refresh = isset($_GET['norefresh']) ? false : true;

// Search for a base url with multiple LB paths
$baseurl = isset($_ENV['HAPROXY_BASE_URL']) ? $_ENV['HAPROXY_BASE_URL'] : "";
$lb_paths = isset($_ENV['HAPROXY_PATHS']) ? $_ENV['HAPROXY_PATHS'] : "";
if (substr($baseurl, -1) != "/")
    $baseurl .= "/";

// Search for full LB URLS, something like LB_URL_1=Balancer1#http...
$lb_urls = array_filter($_ENV, function ($key) {
    return strpos($key, 'LB_URL_') === 0;
}, ARRAY_FILTER_USE_KEY);

if (count($lb_urls) == 0 && ($lb_paths == "" || $baseurl == ""))
{
    die("Missing envs HAPROXY_PATHS, HAPROXY_BASE_URL or LB_URL_*");
}

$lbs_array = array();
$lbs_data = array();


if ($lb_paths != "")
{
    $lb_paths_array = explode(",", $lb_paths);
    foreach ($lb_paths_array as $lb_path) {
        array_push($lbs_array, array('name'=> $lb_path, 'url' => $baseurl . trim($lb_path)));
    }
}
ksort($lb_urls);
foreach ($lb_urls as $key => $value) {
    $value_parts = explode("#", $value);
    $name = str_replace('_', ' ',$value_parts[0]);
    $url = $value_parts[1];
    array_push($lbs_array, array('name'=> $name, 'url' => $url));
}

foreach ($lbs_array as $lb) {
    $data = file_get_contents($lb['url'] . ";csv;norefresh");
    $lbs_data[$lb['name']] = csv_to_array(substr($data, 2));
}

function csv_to_array($csvData, $delimiter=',')
{
	$header = array();
    $data = array();
    $lines = explode("\n", $csvData);

    foreach ($lines as $line)
    {
        if (strlen($line) > 0)
        {
            $line_decoded = str_getcsv($line, ",");
            if(!$header)
                $header = $line_decoded;
            else
                $data[] = array_combine($header, $line_decoded);
        }
    }
	return $data;
}

function secondsToTime($seconds) {
    $dtF = new \DateTime('@0');
    $dtT = new \DateTime("@$seconds");
    return $dtF->diff($dtT)->format('%ad%hh%im%ss');
}

?>

<html>
    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=0.7">
        <?php if ($refresh) echo '<META HTTP-EQUIV="Refresh" CONTENT="' . $refresh_interval . '">' ?>
        <title>HaProxy Visualizer </title>
        <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/css/bootstrap.min.css" integrity="sha384-Vkoo8x4CGsO3+Hhxv8T/Q5PaXtkKtu6ug5TOeNV6gBiFeWPGFN9MuhOf23Q9Ifjh" crossorigin="anonymous">
        <style>
        td {
            padding: 5px !important;
        }

        .table-box {
            min-width: 520px;
            padding: 0 10px;
        }

        .refresh-tooltip {
            font-size: 12px;
            position: absolute;
            top: 10px;
            right: 20px;
            font-style: italic;
        }
        </style>
    </head>
    <body>
        <div class="container-fluid" style="text-align: center;">
            <h1 style="margin-top: 15px;margin-bottom: 10px;">Haproxy Visualizer</h1>
            <a class="refresh-tooltip" href="?<?php echo ($refresh) ? 'norefresh' : '' ?>">
                <?php echo ($refresh) ? 'Auto-Refresh Enabled' : 'Auto-Refresh Disabled'; ?>  </a>
            <div class="">
            <div class="d-flex flex-row flex-wrap justify-content-center">
            <?php
                foreach ($lbs_data as $lb_name=>$lb_stats) {
                    echo '<div class="table-box">';
                    echo '<h3>' . $lb_name . '</h3>' . PHP_EOL;
                    echo '<table style="margin: 0 auto;width: 100%;font-size: 14px;" class="table table-sm">';
                    echo '<thead><tr><th>Server</th><th>Status</th><th>Uptime</th><th>LastChk</th><th>Down</th></tr></thead><tbody>';

                    $lb_servers = array_filter($lb_stats, function ($var) {
                        return ($var['pxname'] == 'servers');
                    });

                    foreach ($lb_servers as $lb_server) {

                        $rowStyle = "background: " . (($lb_server['status'] == 'UP') ? 'lightgreen' : 'lightcoral');


                        echo '<tr style="'. $rowStyle . '"><td>'  . $lb_server['svname']. '</td><td> '
                                    . $lb_server['status'] . '</td><td>'
                                    . secondsToTime( $lb_server['lastchg']) . '</td><td>'
                                    . $lb_server['check_status'] . "/"
                                    . $lb_server['check_code'] . " in "
                                    . $lb_server['check_duration'] . 'ms</td><td>'
                                    . secondsToTime($lb_server['downtime']) . '</td></tr>';
                    }

                    echo '</tbody></table>';
                    echo '</div>';
                }
            ?>
            </div>
            </div>
        </div>
        <script src="https://code.jquery.com/jquery-3.4.1.slim.min.js" integrity="sha384-J6qa4849blE2+poT4WnyKhv5vZF5SrPo0iEjwBvKU7imGFAV0wwj1yYfoRSJoZ+n" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.0/dist/umd/popper.min.js" integrity="sha384-Q6E9RHvbIyZFJoft+2mJbHaEWldlvI9IOYy5n3zV9zzTtmI3UksdQRVvoxMfooAo" crossorigin="anonymous"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/js/bootstrap.min.js" integrity="sha384-wfSDF2E50Y2D1uUdj0O3uMBJnjuUD4Ih7YwaYd1iqfktj0Uod8GCExl3Og8ifwB6" crossorigin="anonymous"></script>
    </body>

 </html>

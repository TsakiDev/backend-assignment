<?php
    include_once '../../config/Database.php';
    include_once '../../models/Vessel.php';
    include_once '../../models/Logger.php';

    //Headers
    header('Access-Control-Allow-Origin: *');
    if (check_if_content_type()) {
        if (getallheaders()['Type'] == 'application/id+json') {
            header('Content-Type: application/id+json');
        }
        else if (getallheaders()['Type'] == 'application/xml') {
            header('Content-Type: application/xml');
        }
        else if (getallheaders()['Type'] == 'application/json') {
            header('Content-Type: application/json');
        }
        else if (getallheaders()['Type'] == 'text/csv') {
            header('Content-Type: text/csv');
        }
        else {
            header('Content-Type: application/json');
            unsupported_content_type();
            die();
        }
    }
    else {
        header('Content-Type: application/json');
    }


    //Instantiate DB & connect
    $database = new Database();
    $db = $database->connect();

    //Instantiate logger
    $logger = new Logger($db);
    
    //Check 
    if ($logger->count_recent_visits($_SERVER['REMOTE_ADDR']) > 10) {
        request_limit_reached();
    }
    
    //Log request
    $logger->write_log($_SERVER['REMOTE_ADDR']);

    //Instantiate vessel object
    $vessel = new Vessel($db);

    //Set filter params
    $vessel->mmsis = isset($_GET['mmsis']) ? $_GET['mmsis'] : null;
    $vessel->minLat = isset($_GET['minLat']) ? $_GET['minLat'] : null;
    $vessel->maxLat = isset($_GET['maxLat']) ? $_GET['maxLat'] : null;
    $vessel->minLon = isset($_GET['minLon']) ? $_GET['minLon'] : null;
    $vessel->maxLon = isset($_GET['maxLon']) ? $_GET['maxLon'] : null;
    $vessel->startDatetime = isset($_GET['startDatetime']) ? date($_GET['startDatetime']) : null;
    $vessel->endDatetime = isset($_GET['endDatetime']) ? date($_GET['endDatetime']) : null;

    $result = $vessel->read_filtered();
    
    //Get row count
    $num = $result->rowCount();

    //Check if result is empty
    if ($num > 0) {

        // vessels array
        $vesselsArr = array();

        while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
            extract($row);

            $data = array(
                'stationId' => $STATION_ID,
                'status' => $STATUS,
                'mmsi' => $MMSI,
                'speed' => $SPEED,
                'lat' => $LAT,
                'lon' => $LON,
                'course' => $COURSE,
                'heading' => $HEADING,
                'rot' => $ROT,
                'timestamp' => $TIMESTAMP
            );

            array_push($vesselsArr, $data);
        }

        //Encode
        if (check_if_content_type() && getallheaders()['Type'] == 'application/id+json') {
            echo json_encode($vesselsArr, JSON_FORCE_OBJECT);
        }
        else if (check_if_content_type() && getallheaders()['Type'] == 'application/xml') {
            echo arrayToXml($vesselsArr);
        }
        else if (check_if_content_type() && getallheaders()['Type'] == 'text/csv') {
            echo encode_csv($vesselsArr);
        }
        else {
            echo json_encode($vesselsArr);
        }
    }
    else {
        no_vessels_found();
    }

    //------------------------------------ HELPERS ------------------------------------
    function no_vessels_found()
    {
        echo json_encode(
            array('message' => 'No vessels found')
        );
    }

    function unsupported_content_type()
    {
        echo json_encode(
            array('message' => 'Unsupported content type')
        );
    }

    function request_limit_reached()
    {
        echo json_encode(
            array('message' => 'You are limited to 10 requests per hour')
        );
        die();
    }

    function check_if_content_type()
    {
        if (isset(getallheaders()['Type'])) {
           return true;
        }
        return false;
    }

    function arrayToXml($array, $rootElement = null, $xml = null) { 
        $_xml = $xml; 

        // If there is no Root Element then insert root
        if ($_xml === null) {
            $_xml = new SimpleXMLElement($rootElement !== null ? $rootElement : '<vessels/>');
        }

        // Visit all key value pair
        foreach ($array as $k => $v) {
            // If there is nested array then
            if (is_array($v)) {
                // Call function for nested array
                arrayToXml($v, $k, $_xml->addChild('vessel'));
            }
            else {
                // Simply add child element.
                $_xml->addChild($k, $v);
            }
        }

        return $_xml->asXML();
    }

    function encode_csv($data) {
        $fh = fopen('php://temp', 'rw');

        fputcsv($fh, array_keys(current($data)));

        foreach ( $data as $row ) {
                fputcsv($fh, $row);
        }
        rewind($fh);
        $csv = stream_get_contents($fh);
        fclose($fh);

        return $csv;
}
?>
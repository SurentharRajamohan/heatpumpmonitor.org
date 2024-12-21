<?php

// no direct access
defined('EMONCMS_EXEC') or die('Restricted access');

// Histogram controller
function histogram_controller() {
    global $route, $session, $system_stats;

    // HTML view
    if ($route->action == "") {
        $route->format = "html";
        return view("Modules/histogram/histogram_view.php", array("userid" => $session['userid']));
    }

    // System id required
    $systemid = (int)get('id', true);
    // Load system config
    $config = $system_stats->get_system_config($session['userid'], $systemid);

    // Centralized logic for histogram actions
    $histogramActions = [
        "kwh_at_cop" => [
            "endpoint" => "kwh_at_cop",
            "params" => [
                "elec" => $config->elec,
                "heat" => $config->heat,
                "div" => 0.1,
            ]
        ],
        "kwh_at_flow" => [
            "endpoint" => "kwh_at_temperature",
            "params" => [
                "power" => $config->heat,
                "temperature" => $config->flowT,
                "div" => 0.5,
            ]
        ],
        "kwh_at_outside" => [
            "endpoint" => "kwh_at_temperature",
            "params" => [
                "power" => $config->heat,
                "temperature" => $config->outsideT,
                "div" => 0.5,
            ]
        ],
        "kwh_at_flow_minus_outside" => [
            "endpoint" => "kwh_at_flow_minus_outside",
            "params" => [
                "power" => $config->heat,
                "flow" => $config->flowT,
                "outside" => $config->outsideT,
                "div" => 0.5,
            ]
        ],
        "kwh_at_ideal_carnot" => [
            "endpoint" => "kwh_at_ideal_carnot",
            "params" => [
                "power" => $config->heat,
                "flow" => $config->flowT,
                "outside" => $config->outsideT,
                "div" => 0.1,
            ]
        ],
        "flow_temp_curve" => [
            "endpoint" => "flow_temp_curve",
            "params" => [
                "outsideT" => $config->outsideT,
                "flowT" => $config->flowT,
                "heat" => $config->heat,
                "div" => 0.5,
            ]
        ]
    ];

    if (isset($histogramActions[$route->action])) {
        return fetch_histogram_data($config, $histogramActions[$route->action]);
    }

    return false;
}

/**
 * Fetches histogram data based on the configuration and action details.
 * 
 * @param object $config The system configuration.
 * @param array $actionDetails The details of the action to perform.
 * @return mixed The output from the endpoint or an error message.
 */
function fetch_histogram_data($config, $actionDetails) {
    global $route;

    $route->format = "json";

    // Merge common and specific parameters
    $params = array_merge($actionDetails['params'], [
        "start" => get('start', true),
        "end" => get('end', true),
        "interval" => 300,
        "x_min" => get('x_min', true),
        "x_max" => get('x_max', true)
    ]);

    // Add API key if available
    if ($config->apikey != "") {
        $params['apikey'] = $config->apikey;
    }

    // Construct the endpoint URL
    $url = "$config->server/histogram/data/{$actionDetails['endpoint']}?" . http_build_query($params);

    // Fetch the data
    $result = file_get_contents($url);
    $output = json_decode($result);

    // Return the result or raw response if JSON decoding fails
    return $output === null ? $result : $output;
}

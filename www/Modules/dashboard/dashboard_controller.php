<?php

// no direct access
defined('EMONCMS_EXEC') or die('Restricted access');

class DashboardController
{
    private $route;
    private $session;
    private $system;

    public function __construct($route, $session, $system)
    {
        $this->route = $route;
        $this->session = $session;
        $this->system = $system;
    }

    public function handleRequest()
    {
        // Check if action is empty (default view)
        if (empty($this->route->action)) {
            return $this->viewDashboard();
        }

        // Additional actions can be handled here in the future
        return $this->handleUnknownAction();
    }

    private function viewDashboard()
    {
        // Ensure system ID is provided
        $systemId = $this->getSystemId();
        if (!$systemId) {
            return $this->renderError("System ID is missing.");
        }

        // Fetch system data
        $systemData = $this->system->get($this->session['userid'], $systemId);
        if (!$systemData) {
            return $this->renderError("System data not found for ID: $systemId");
        }

        // Render the dashboard view
        return $this->renderView("Modules/dashboard/myheatpump.php", [
            "id" => $systemId,
            "system_data" => $systemData,
        ]);
    }

    private function handleUnknownAction()
    {
        // Handle unrecognized actions gracefully
        return $this->renderError("Unknown action: {$this->route->action}");
    }

    private function getSystemId()
    {
        // Validate and return the system ID
        return isset($_GET['id']) ? filter_var($_GET['id'], FILTER_SANITIZE_NUMBER_INT) : null;
    }

    private function renderView($viewPath, $data = [])
    {
        // Set the format to HTML for rendering
        $this->route->format = "html";

        // Extract data into view scope
        extract($data);

        // Include the specified view file
        return include $viewPath;
    }

    private function renderError($message)
    {
        // Log the error
        error_log("DashboardController Error: $message");

        // Return an error view or message
        return $this->renderView("Modules/dashboard/error.php", [
            "error_message" => $message,
        ]);
    }
}

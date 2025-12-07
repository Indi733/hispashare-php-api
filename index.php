<?php

// Include the class
require 'HispaShare.php';

// REPLACE THESE WITH YOUR ACCOUNT DETAILS
$username = 'YOUR_USERNAME_HERE';
$password = 'YOUR_PASSWORD_HERE';

try {
    // Initialize (Pass true as 3rd argument to enable logging)
    $api = new HispaShare($username, $password, true);
    
    echo "<h1>HispaShare API Test</h1>";
    
    // 1. Login
    if ($api->login()) {
        echo "<p style='color:green'>Login Successful</p>";
        
        // 2. Search
        $movieName = 'Matrix';
        echo "<h3>Searching for: $movieName</h3>";
        $results = $api->search($movieName);
        
        echo "<pre>" . print_r($results, true) . "</pre>";
        
        // 3. Get Links for the first result
        if (!empty($results)) {
            $firstUrl = $results[0]['url'];
            echo "<h3>Fetching links for: " . $results[0]['title'] . "</h3>";
            
            $links = $api->getLinks($firstUrl);
            echo "<pre>" . print_r($links, true) . "</pre>";
        }
        
    } else {
        echo "<p style='color:red'>Login Failed</p>";
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>

# HispaShare PHP API Wrapper

An unofficial PHP library to interact with HispaShare.org. It handles authentication, searches for content, and extracts `ed2k` links by parsing the legacy HTML structure.

## Features
* **Authentication:** Handles login and cookie management automatically.
* **Search:** Supports advanced search parameters (Year, IMDb score, Duration).
* **Link Extraction:** Solves the site's AJAX requests to retrieve real `ed2k` hashes.
* **Metadata:** Extracts audio languages, subtitles, and IMDb IDs.

## Usage

```php
require 'HispaShare.php';

// Initialize
$hs = new HispaShare('my_username', 'my_password');

// Login
if ($hs->login()) {
    // Search for a movie
    $results = $hs->search('Inception');
    
    // Get links for the first result
    $links = $hs->getLinks($results[0]['url']);
    
    print_r($links);
}

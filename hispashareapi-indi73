<?php
class HispaShare
{
    private $username;
    private $password;
    private $cookieFile;
    private $baseUrl = 'https://www.hispashare.org';
    private $debug;

    // Modified constructor to remove hardcoded credentials and allow debug toggle
    public function __construct($username, $password, $debug = false)
    {
        if (empty($username) || empty($password)) {
            throw new Exception("Username and Password are required.");
        }
        
        $this->username = $username;
        $this->password = $password;
        $this->debug = $debug;
        
        // Use system temp dir for cookies
        $this->cookieFile = sys_get_temp_dir() . '/cookies_' . md5($username) . '.txt';
    }

    private function log($message)
    {
        // Only write logs if debug is enabled
        if (!$this->debug) {
            return;
        }
        
        // Write to a log file in the temp directory, not the project root
        $logFile = sys_get_temp_dir() . '/debug_hispashare.log';
        $timestamp = date('Y-m-d H:i:s');
        file_put_contents($logFile, "[$timestamp] $message" . PHP_EOL, FILE_APPEND);
    }

    public function login()
    {
        $postData = [
            'username' => $this->username,
            'password' => $this->password,
            'login' => 'Iniciar'
        ];

        $response = $this->_request($this->baseUrl . '/', 'POST', $postData);

        if (strpos($response, 'logout') !== false || strpos($response, 'Cerrar') !== false) {
            return true;
        }

        if (strpos($response, 'name="login"') !== false || strpos($response, 'Nombre de usuario') !== false) {
            $this->log("Login check failed.");
            return false;
        }

        return true;
    }

    public function search($query, $params = [])
    {
        $queryParams = [
            'view' => 'advsearch',
            'advsrch' => '1',
            'advtit' => $query 
        ];

        if (isset($params['imdb_min'])) $queryParams['advscomin'] = $params['imdb_min'];
        if (isset($params['imdb_max'])) $queryParams['advscomax'] = $params['imdb_max'];
        if (isset($params['duration_min'])) $queryParams['advdurmin'] = $params['duration_min'];
        if (isset($params['duration_max'])) $queryParams['advdurmax'] = $params['duration_max'];
        if (isset($params['year_min'])) $queryParams['advdatmin'] = $params['year_min'];
        if (isset($params['year_max'])) $queryParams['advdatmax'] = $params['year_max'];

        $url = $this->baseUrl . '/?' . http_build_query($queryParams);
        $this->log("Searching URL: $url");
        $html = $this->_request($url);

        if (strpos($html, 'name="login"') !== false) {
            $this->log("Login required during search. Attempting re-login.");
            if ($this->login()) {
                $html = $this->_request($url);
            } else {
                return [];
            }
        }

        $dom = new DOMDocument();
        @$dom->loadHTML($html);
        $xpath = new DOMXPath($dom);

        $results = [];
        $nodes = $xpath->query('//a[starts-with(@href, "?view=title&id=")]');

        foreach ($nodes as $node) {
            if ($node->getElementsByTagName('small')->length > 0) {
                $fullTitle = trim($node->textContent);
                $href = $this->baseUrl . '/' . $node->getAttribute('href');

                $year = null;
                if (preg_match('/\(.*?(\d{4})\)$/', $fullTitle, $matches)) {
                    $year = (int) $matches[1];
                }

                $results[] = [
                    'title' => $fullTitle,
                    'year' => $year,
                    'url' => $href
                ];
            }
        }
        return $results;
    }

    public function getLinks($url)
    {
        $this->log("Getting links from: $url");
        $html = $this->_request($url);

        if (strpos($html, 'name="login"') !== false) {
            if ($this->login()) {
                $html = $this->_request($url);
            } else {
                return [];
            }
        }

        $dom = new DOMDocument();
        @$dom->loadHTML($html);
        $xpath = new DOMXPath($dom);

        // Extract IMDb ID
        $imdbId = null;
        $imdbNodes = $xpath->query('//a[contains(@href, "imdb.com/title/")]');
        if ($imdbNodes->length > 0) {
            $imdbHref = $imdbNodes->item(0)->getAttribute('href');
            if (preg_match('/tt\d+/', $imdbHref, $matches)) {
                $imdbId = $matches[0];
            }
        }

        $links = [];
        $rows = $xpath->query('//table[@class="T2"]//tr[td[@class="ICON"]]');

        foreach ($rows as $row) {
            $size = '';
            $downloads = '';
            $sizeNodes = $xpath->query('.//td[@class="SIZE"]', $row);
            if ($sizeNodes->length >= 1) $size = trim($sizeNodes->item(0)->textContent);
            if ($sizeNodes->length >= 2) $downloads = trim($sizeNodes->item(1)->textContent);

            $anchor = $xpath->query('.//a[starts-with(@href, "javascript:Download")]', $row)->item(0);
            if (!$anchor) continue;

            $href = $anchor->getAttribute('href');
            if (preg_match("/javascript:Download\((\d+)\s*,\s*['\"](\d+)['\"]\)/i", $href, $matches)) {
                $id = $matches[1];
                $code = $matches[2];

                // Languages
                $audio = [];
                $subtitles = [];
                $t2Div = $xpath->query('ancestor::div[table[@class="T2"]]', $row)->item(0);
                
                if ($t2Div) {
                    $nextDiv = $t2Div->nextSibling;
                    while ($nextDiv && ($nextDiv->nodeType !== XML_ELEMENT_NODE || $nextDiv->tagName !== 'div')) {
                        $nextDiv = $nextDiv->nextSibling;
                    }
                    if ($nextDiv) {
                        $flags = $xpath->query('.//table[@class="T3"]//img[@class="FLAG"]', $nextDiv);
                        foreach ($flags as $flag) {
                            $alt = strtolower($flag->getAttribute('alt'));
                            if (strpos($alt, 'sub') !== false || strpos($alt, 'for') !== false) {
                                $subtitles[] = $alt;
                            } else {
                                $audio[] = $alt;
                            }
                        }
                    }
                }

                // AJAX Request
                $ajaxUrl = $this->baseUrl . "/ajax/download.php?id=$id&code=$code&nocache=" . mt_rand();
                $ajaxContent = $this->_request($ajaxUrl);

                preg_match_all('/ed2k:\/\/\|file\|[^|]+\|\d+\|\w+\|(?:\/|h=\w+\|(?:\/)?)/i', $ajaxContent, $ed2kMatches);

                foreach ($ed2kMatches[0] as $ed2k) {
                    $parts = explode('|', $ed2k);
                    $title = isset($parts[2]) ? urldecode($parts[2]) : 'Unknown';
                    
                    // Simple dupe check
                    $isDupe = false;
                    foreach ($links as $l) { if ($l['url'] === $ed2k) $isDupe = true; }
                    
                    if (!$isDupe) {
                        $links[] = [
                            'type' => 'ed2k',
                            'title' => $title,
                            'url' => $ed2k,
                            'size' => $size,
                            'downloads' => $downloads,
                            'audio' => $audio,
                            'subtitles' => $subtitles,
                            'imdb_id' => $imdbId
                        ];
                    }
                }
            }
        }
        return $links;
    }

    private function _request($url, $method = 'GET', $data = [])
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_COOKIEJAR, $this->cookieFile);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $this->cookieFile);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)');
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        }

        $output = curl_exec($ch);
        curl_close($ch);
        return $output;
    }
}
?>

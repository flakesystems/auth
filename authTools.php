<?php
    class AuthTools {
        public function modifyCurrentUrl($new_params = []): string {
            $scheme = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
            $host = $_SERVER['HTTP_HOST'];
            $path = $_SERVER['REQUEST_URI'];
            
            $parsed_url = parse_url($scheme . "://" . $host . $path);
            $query_params = [];
            if (isset($parsed_url['query'])) {
                parse_str($parsed_url['query'], $query_params);
            }
            
            $query_params = array_merge($query_params, $new_params);
            $new_query_string = http_build_query($query_params);
            
            $new_url = $scheme . "://" . $host . $parsed_url['path'];
            if (!empty($new_query_string)) {
                $new_url .= '?' . $new_query_string;
            }
    
            return $new_url;
        }

        public function removeAllParamsAndPathFromUrl($url): string {
            $parsedUrl = parse_url($url);
            $newUrl = $parsedUrl['scheme'] . "://" . $parsedUrl['host'];
            return $newUrl;
        }

        private function getRequest($url) {

            // Initialize cURL session
            $ch = curl_init();
    
            // Set cURL options
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPGET, true);
    
            // Execute cURL session and get the response
            $response = curl_exec($ch);
    
            // Check for cURL errors
            if (curl_errno($ch)) {
            //echo 'cURL error: ' . curl_error($ch);
            curl_close($ch);
            return;
            } else {
            //echo 'Response: ' . $response;
            curl_close($ch);
            return $response;
            }
        }

        public function validateRedirectURL($url, $fallback): string {
            //echo "<script>console.log(`validateUrl...`);</script>";
            $apiUrl = "https://backend.flake-systems.de/api/collections/validUrls/records";
            
            // Fetch valid URLs from the API
            $response = $this->getRequest($apiUrl);
    
            //echo "<script>console.log(`response: $response`);</script>";
            if ($response === false) {
                return $fallback; // Fallback in case of API failure
            }
            
            $data = json_decode($response, true);
            if (!isset($data['items']) || !is_array($data['items'])) {
                return $fallback;
            }
            
            // Extract valid hostnames
            $validHosts = array_map(function ($item) {
                return parse_url($item['url'], PHP_URL_HOST);
            }, $data['items']);
            /*
            foreach ($validHosts as $host) {
                echo "<script>console.log(`validhost: $host`);</script>";
            }
            */
            // Get the hostname from the given URL
            $parsedUrl = parse_url($url);
            if (!isset($parsedUrl['host'])) {
                return $fallback;
            }
    
            //echo "<script>console.log(`parsedUrl: $parsedUrl`);</script>";
            
            $hostname = $parsedUrl['host'];
            //echo "<script>console.log(`hostname: $hostname`);</script>";
            
            // Check if the hostname (including subdomains) belongs to a valid domain
            foreach ($validHosts as $validHost) {
                if (str_ends_with($hostname, $validHost)) {
                    return $url;
                }
            }
            
            return $fallback;
        }

        //Function to get the current URL
        function getCurrentUrl() {
            $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https" : "http";
            $host = $_SERVER['HTTP_HOST'];
            $uri = $_SERVER['REQUEST_URI'];
        
            return "$protocol://$host$uri";
        }

        public function removeQueryParams($url, $paramsToRemove) {
            // Parse the URL into components
            $parsedUrl = parse_url($url);
            
            // If no query parameters exist, return the original URL
            if (!isset($parsedUrl['query'])) {
                return $url;
            }
            
            // Parse query string into an associative array
            parse_str($parsedUrl['query'], $queryParams);
            
            // Remove the specified parameters
            foreach ((array) $paramsToRemove as $param) {
                unset($queryParams[$param]);
            }
            
            // Rebuild the query string
            $newQuery = http_build_query($queryParams);
            
            // Reconstruct the URL
            $newUrl = $parsedUrl['scheme'] . '://' . $parsedUrl['host'];
            if (isset($parsedUrl['port'])) {
                $newUrl .= ':' . $parsedUrl['port'];
            }
            if (isset($parsedUrl['path'])) {
                $newUrl .= $parsedUrl['path'];
            }
            if ($newQuery) {
                $newUrl .= '?' . $newQuery;
            }
            if (isset($parsedUrl['fragment'])) {
                $newUrl .= '#' . $parsedUrl['fragment'];
            }
            
            return $newUrl;
        }

        public function callUserDetailsApi($userId, $token): array {
            echo "<script>console.log(Calling Api with userId: $userId, token: $token);</script>";
            $url = "https://backend.flake-systems.de/api/collections/users/records/" . $userId;
        
            $ch = curl_init($url);
        
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                "Authorization: Bearer " . $token,
                "Accept: application/json"
            ]);
        
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
            curl_close($ch);
        
            if ($httpCode !== 200) {
                echo "<script>console.log(Failed with code: $httpCode);</script>";
                return ["error" => "Request failed"];
            }
        
            $jsonDecode = json_decode($response, true) ?? ["error" => "Invalid JSON response"];
            echo "<script>console.log($jsonDecode, $response);</script>";
            return $jsonDecode;
        }

        //GET USER INFO

        public function getUsername($userId, $token): string {
            $jsonDecode = $this->callUserDetailsApi($userId, $token);
            return $jsonDecode['name'] ?? "Unknown user";
        }

        public function getUserEmail($userId, $token): string {
            $jsonDecode = $this->callUserDetailsApi($userId, $token);
            return $jsonDecode['email'] ?? "unknown@unknown.com";
        }
        public function getProfilePicUrl($userId, $token): string {
            $jsonDecode = $this->callUserDetailsApi($userId, $token);
            $filename = $jsonDecode['avatar'] ?? "";
            return "https://backend.flake-systems.de/api/files/users/" . $userId . "/" . $filename;
        }

        //EMAIL VERIFICATION
        public function requestVerification($email) {
            $url = "https://backend.flake-systems.de/api/collections/users/request-verification";
            $data = [
                "email" => $email
            ];
    
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
            curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/x-www-form-urlencoded"]);

            $response = curl_exec($ch);

            curl_close($ch);
            return $response;
        }

        //PASSWORD RESET        
        public function requestPasswordReset($email) {
            $url = "https://backend.flake-systems.de/api/collections/users/request-password-reset";
            $data = [
                "email" => $email
            ];
    
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
            curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/x-www-form-urlencoded"]);

            $response = curl_exec($ch);

            curl_close($ch);
            return $response;
        }
    }
?>
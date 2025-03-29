<!DOCTYPE html>
<?php
    function modify_current_url($new_params = []) {
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

    function removeAllParamsAndPathFromUrl($url): string {
        $parsedUrl = parse_url($url);
        $newUrl = $parsedUrl['scheme'] . "://" . $parsedUrl['host'];
        return $newUrl;
    }

    function getRequest($url) {

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

    function validateRedirectURL($url): string {
        //echo "<script>console.log(`validateUrl...`);</script>";
        $apiUrl = "https://backend.flake-systems.de/api/collections/validUrls/records";
        
        // Fetch valid URLs from the API
        $response = getRequest($apiUrl);

        //echo "<script>console.log(`response: $response`);</script>";
        if ($response === false) {
            return "https://accounts.flake-systems.de"; // Fallback in case of API failure
        }
        
        $data = json_decode($response, true);
        if (!isset($data['items']) || !is_array($data['items'])) {
            return "https://accounts.flake-systems.de";
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
            return "https://accounts.flake-systems.de";
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
        
        return "https://accounts.flake-systems.de";
    }
    
    function login($mail, $password, $redirect_url = "https://accounts.flake-systems.de"){
        $url = "https://backend.flake-systems.de/api/collections/users/auth-with-password";
        $data = [
            "identity" => $mail,
            "password"=> $password
        ];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/x-www-form-urlencoded"]);

        $response = curl_exec($ch);
        echo "<script>console.log(`$response`);</script>";
        if (curl_errno($ch)) {
            http_response_code(500);
            $GLOBALS['login_error'] = "Failed to login. Curl Error: " . curl_error($ch);
            header("Location: " . modify_current_url(['redirect_url' => $redirect_url]));
            exit;
        }
        curl_close($ch);

        $data = json_decode($response, true);
        if (isset($data['token'])) {
            $newToken = $data['token'];
            $newUserId = $data['record']['id'];
            setcookie("auth_token", $newToken, time()+60*60*24*30, "/", "", true, true);
            setcookie("user_id", $newUserId, time()+60*60*24*30, "/", "", true, true);
            header("Location: " . (validateRedirectURL($redirect_url)) . "?set_cookie_with_auth_token=$newToken" . "&set_cookie_with_user_id=$newUserId");
            exit;
        } else {
            http_response_code(401);
            $GLOBALS['login_error'] = "Failed to login. Please check your credentials.";
            header("Location: " . modify_current_url(['redirect_url' => $redirect_url]));
            exit;
        }
    }

    if ($_SERVER["REQUEST_METHOD"] === "POST") {
        error_log("postpsotpsot");
        $email = isset($_POST['email']) ? trim($_POST['email']) : '';
        $password = isset($_POST['password']) ? $_POST['password'] : '';
        $redirect_url = isset($_POST['redirect_url']) ? $_POST['redirect_url'] : '';
    
        if (empty($email) || empty($password)) {
            http_response_code(400);
            $GLOBALS['login_error'] = "Email and password are required.";
            header("Location: " . modify_current_url(['redirect_url' => $redirect_url]));
            exit;
        }
        login($email, $password, $redirect_url);
    } else {
        if (!($_GET['disable_redirect'] == "true")) {
            if ((isset($_COOKIE['auth_token'])) && (isset ($_COOKIE['user_id']))) {
                header("Location: https://auth.flake-systems.de/continue.php?redirect_url=" . (validateRedirectURL($_GET['redirect_url'])) . "&id=" . $_COOKIE['auth_token'] . "&user_id=" . $_COOKIE['user_id']);
            }
        }
    }
?>

<html>
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link rel="icon" type="image/x-icon" href="https://flake-systems.de/flakecraftbig.png">
        <title>FlakeAccounts Login</title>
        <script src="https://cdn.tailwindcss.com"></script>
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@davidui/david-ui@1.0.5/dist/david-ui.min.css">
        <script>
            if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
                document.documentElement.classList.add('dark');
            }
        </script>
        <style>
            .primary-color-fsystems {
                background: #0080ff;
            }
        </style>
    </head>
    <body class="bg-gray-50 dark:bg-gray-900 flex items-center justify-center h-screen">
        <div class="max-w-sm w-full bg-white dark:bg-gray-800 shadow-lg rounded-lg p-8">
            <div class="flex justify-center mb-8">
                <img src="https://flake-systems.de/flakecraftlogo.png" alt="Logo" class="h-10">
            </div>

            <form action="login.php" method="POST" id="login-form" class="space-y-6">
                <h2 class="text-2xl font-semibold text-center text-gray-900 dark:text-white">FlakeAccounts Login</h2>

                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Email</label>
                    <input type="email" id="email" name="email" required class="block w-full mt-1 px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:text-white dark:focus:ring-indigo-400 dark:focus:border-indigo-400">
                </div>

                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Password</label>
                    <input type="password" id="password" name="password" required class="block w-full mt-1 px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:text-white dark:focus:ring-indigo-400 dark:focus:border-indigo-400">
                </div>

                <div id="error-message" class="text-red-600 text-sm text-center <?php echo isset($GLOBALS['login_error']) ? '' : 'hidden'; ?>">
                    <?php echo isset($GLOBALS['login_error']) ? $GLOBALS['login_error'] : ''; ?>
                </div>

                <input type="hidden" id="redirect-url" name="redirect_url" value="<?php echo $_GET['redirect_url'];?>">

                <div>
                    <button type="submit" class="primary-color-fsystems w-full text-white py-2 rounded-md shadow-md focus:outline-none focus:ring-2 focus:ring-opacity-50">
                    Log in
                    </button>
                </div>
            </form>
            <p class="text-center text-sm text-gray-500 mt-4 dark:text-gray-400">Don't have an account? <a href="./signup.php<?php echo "?" . $_SERVER['QUERY_STRING']; ?>" class="text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 dark:hover:text-indigo-500">Sign up</a></p>
        </div>
    </body>
</html>
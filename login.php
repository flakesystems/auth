<!DOCTYPE html>
<?php
    //We need the authTools Library to validate the redirect Url and for some other handy features
    require('./authTools.php');
    $authTools = new AuthTools();
    $fallBackUrl = "https://accounts.flake-systems.de";

    function authWithPassword($mail, $password) {
        //URL to Authentication API
        $apiUrl = "https://backend.flake-systems.de/api/collections/users/auth-with-password";
        //Pocketbase Auth-With-Password requires the email to be "identity"
        $data = [
            "identity" => $mail,
            "password"=> $password
        ];

        //Open a curl session
        $ch = curl_init($apiUrl);
        //Configure curl: POST Request with $data
        curl_setopt($ch, CURLOPT_RETURNTRANSFER,true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/x-www-form-urlencoded"]);

        //Check for curl errors
        $response = curl_exec($ch);
        if (curl_errno($ch)) {
            http_response_code(500);
            return;
        }
        //Close the curl session
        curl_close($ch);

        $data = json_decode($response, true);
        return $data;
    }

    function registerApplink($applink, $authToken, $userId) {
        // API endpoint with path variables
        $url = "https://backend.flake-systems.de/api/collections/appLink/records/{$applink}";
        
        // Body variables (data to update)
        $data = [
            'user' => $userId,
            'userToken' => $authToken
        ];
        
        // Initialize cURL session
        $ch = curl_init($url);
        
        // Set cURL options
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PATCH");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/x-www-form-urlencoded'
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        
        // Execute the request
        $response = curl_exec($ch);
        
        // Error handling
        if (curl_errno($ch)) {
            echo 'Error: ' . curl_error($ch);
        } else {
            echo 'Response: ' . $response;
        }
        
        // Close cURL session
        curl_close($ch);
    }

    function login($mail, $password, $redirect_url, $authTools, $fallBackUrl, $applink) {
        //Get data from ApiCall
        $data = authWithPassword($mail, $password);
        //If "token" is in the response from the API it will redirect to the "redirect_url" with set_cookie_with_auth_token and set_cookie_with_user_id parameters.
        //Set cookies for auth.flake-systems.de
        if (isset($data["token"])) {
            $token = $data["token"];
            $userId = $data["record"]["id"];

            //Set cookies
            setcookie("auth_token", $token, time()+60*60*24*30, "/", "auth.flake-systems.de", true, true);
            setcookie("user_id", $userId, time()+60*60*24*30, "/", "auth.flake-systems.de", true, true);

            registerApplink(applink: $applink, authToken: $token, userId: $userId);

            //Redirect to "redirect_url" with token and userId parameters
            header("Location: " . ($authTools->validateRedirectURL($redirect_url, $fallBackUrl) . "?set_cookie_with_auth_token=" . $token . "&set_cookie_with_user_id=" . $userId));
        } else {
            //Let the user know, that something didn't go right
            $GLOBALS['login_error'] = "Failed to login. Please check your credentials.";
            //Add the "redirect_url" parameter back to the current url
            header("Location: " . $authTools->modify_current_url(['redirect_url' => $redirect_url]));
        }
    }

    //Check if the form was sent
    //Run the login() code if all values are not null
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        //Set variables to POST Request values. If there is not a value it defaults to an empty string.
        $mail = isset($_POST['email']) ? trim($_POST['email']) : '';
        $password = isset($_POST['password']) ? trim($_POST['password']) :'';
        $redirect_url = isset($_POST['redirect_url']) ? trim($_POST['redirect_url']) :'';
        $applink = isset($_POST['applink']) ? trim($_POST['applink']) :'';

        //If one of the variables is an empty string, the user will be confronted with an error.
        if (empty($mail) || empty($password) || empty($redirect_url)) {
            $GLOBALS['login_error'] = "Failed to login. Please enter your email and password.";
        } else {
            //Login
            login(mail: $mail, password: $password, redirect_url: $redirect_url, authTools: $authTools, fallBackUrl: $fallBackUrl, applink: $applink);
        }
    } else {
        //If disable_redirect isn't true, it will look for existing cookies on the client and redirect to the continue page if both token and userId is found.
        if (!($_GET['disable_redirect'] == "true")) {
            if ((isset($_COOKIE['auth_token'])) && (isset ($_COOKIE['user_id']))) {
                header("Location: https://auth.flake-systems.de/continue.php?redirect_url=" . ($authTools->validateRedirectURL($_GET['redirect_url'], $fallBackUrl)));
            }
        }
    }

    $redirect_url_check = ($authTools->validateRedirectURL($_GET['redirect_url'], $fallBackUrl) . "?set_cookie_with_auth_token=" . $_COOKIE['auth_token'] . "&set_cookie_with_user_id=" . $_COOKIE['user_id']);
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
                <? 
                    if (isset($_GET['applink'])) {
                        echo `<input type="hidden" id="applink" name="applink" value="` . $_GET['applink'] . `">`;
                    }
                ?>
                <p class="text-center text-sm text-gray-500 mt-4 dark:text-gray-400">Forgot your password? <a href="./passwordReset.php<?php echo "?" . $_SERVER['QUERY_STRING']; ?>" class="text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 dark:hover:text-indigo-500">Reset</a></p>

                <div>
                    <button type="submit" class="primary-color-fsystems w-full text-white py-2 rounded-md shadow-md focus:outline-none focus:ring-2 focus:ring-opacity-50">
                    Log in
                    </button>
                </div>
            </form>
            <p class="text-center text-sm text-gray-500 mt-4 dark:text-gray-400">Don't have an account? <a href="./signup.php<?php echo "?" . $_SERVER['QUERY_STRING']; ?>" class="text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 dark:hover:text-indigo-500">Sign up</a></p>
            <a type="hidden" class="hidden text-center text-sm text-gray-500 mt-4 dark:text-gray-400" href="<?php echo $redirect_url_check; ?>">hello</a>
        </div>
    </body>
</html>
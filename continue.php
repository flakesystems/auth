<?php
    //Require authTools to do some fancy sh*t
    require("./authTools.php");
    $authTools = new AuthTools();
    //Set a fallback Url if Url validation fails
    $fallBackUrl = "https://accounts.flake-systems.de";
    $userName = $authTools->getUsername($_COOKIE['user_id'], $_COOKIE['auth_token']);
    $email = $authTools->getUserEmail($_COOKIE['user_id'], $_COOKIE['auth_token']);
    $profilePicUrl = $authTools->getProfilePicUrl($_COOKIE['user_id'], $_COOKIE['auth_token']);
/*
    //Save id & userId
    $token = $_GET['id'];
    $userId = $_GET['user_id'];

    function saveUrlParametersAndDeleteThemAfterwards($authTools) {
        //Remove id & userId from current Url
        if (isset($_GET['id'])) {
            $newUrl = $authTools->removeQueryParams($authTools->getCurrentUrl(), ['id']);
            header("Location: $newUrl");
        }

        if (isset($_GET['user_id'])) {
            $newUrl = $authTools->removeQueryParams($authTools->getCurrentUrl(), ['user_id']);
            header("Location: $newUrl");
        }
    }

    saveUrlParametersAndDeleteThemAfterwards($authTools);
*/
    //Login
    function login($authTools, $redirect_url, $fallBackUrl) {
        $newRedirect_url = ($authTools->validateRedirectURL($redirect_url, $fallBackUrl) . "?set_cookie_with_auth_token=" . $_COOKIE['auth_token'] . "&set_cookie_with_user_id=" . $_COOKIE['user_id']);
        header('Location: '. $newRedirect_url);
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

    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $redirect_url = isset($_POST['redirect_url']) ? trim($_POST['redirect_url']) :'';
        $applink = isset($_POST['applink']) ? trim($_POST['applink']) :'';

        if ($redirect_url != '') {
            login($GLOBALS['authTools'], $redirect_url, $GLOBALS['fallBackurl']);
        }
        if ($applink != '') {
            registerApplink($_GET['applink'], $_COOKIE['auth_token'], $_COOKIE['user_id']);
        }
    } else {
        if (!($_GET['disable_redirect'] == "true")) {
            if (!(isset($_COOKIE['auth_token'])) || !(isset ($_COOKIE['user_id']))) {
                //header("Location: https://auth.flake-systems.de/login.php?redirect_url=" . ($authTools->validateRedirectURL($_GET['redirect_url'], $fallBackUrl)));
                header("Location: https://auth.flake-systems.de/login.php?disable_redirect=true&" . $_SERVER['QUERY_STRING']);
            } else {
                if ($authTools->callUserDetailsApi($_COOKIE['user_id'], $_COOKIE['auth_token']) == ["error" => "Request failed"]) {
                    header("Location: https://auth.flake-systems.de/login.php?disable_redirect=true&" . $_SERVER['QUERY_STRING']);
                }
            }
        }
    }
?>

<!DOCTYPE html>
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
            <div class="flex justify-center place-content-center mb-8">
                <img onerror="this.style.display='none'" alt="avatar" src="<? echo $GLOBALS['profilePicUrl'] . "?"; ?>" class="w-10 h-10 mr-6 rounded-full">
                <img src="https://flake-systems.de/flakecraftlogo.png" alt="Logo" class="h-10">
            </div>
            <form action="continue.php" method="POST" id="login-form" class="space-y-6">
            <h2 class="text-2xl font-semibold text-center text-gray-900 dark:text-white">FlakeAccounts Login</h2>
            <p class="text-xl font-semibold text-center text-gray-900 dark:text-white">Continue as <? echo $GLOBALS['email']; ?></p>
                <div>
                    <button type="submit" class="primary-color-fsystems w-full text-white py-2 rounded-md shadow-md focus:outline-none focus:ring-2 focus:ring-opacity-50">
                    Continue
                    </button>
                </div>
                <input type="hidden" id="redirect-url" name="redirect_url" value="<?php echo $_GET['redirect_url'];?>">
                <input type="hidden" id="applink" name="applink" value="<?php echo $_GET['applink'];?>">
            </form>
            <p class="text-center text-sm text-gray-500 mt-4 dark:text-gray-400">Not <? echo $GLOBALS['userName'] . "?"; ?> <a href="./login.php<?php echo "?" . $_SERVER['QUERY_STRING'] . "&disable_redirect=true"; ?>" class="text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 dark:hover:text-indigo-500">Use another account</a></p>
            <a type="hidden" class="hidden text-center text-sm text-gray-500 mt-4 dark:text-gray-400" href="<?php echo $redirect_url_check; ?>">hello</a>
        </div>
    </body>
</html>
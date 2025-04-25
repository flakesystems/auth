<!DOCTYPE html>
<?php
    require('./authTools.php');
    $authTools = new AuthTools();
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

    function signUpUser($email, $password, $confirm_password, $name) {
        $url = "https://backend.flake-systems.de/api/collections/users/records";
        $data = [
            "email" => $email,
            "password" => $password,
            "passwordConfirm" => $confirm_password,
            "role" => "default",
            "name" => $name,
        ];

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));

        $response = curl_exec($ch);

        if (curl_errno($ch)) {
            return ['message' => curl_error($ch)];
        }

        curl_close($ch);

        return json_decode($response, true);
    }

    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $name = $_POST['name'];
        $email = $_POST['email'];
        $password = $_POST['password'];
        $confirm_password = $_POST['confirm-password'];
        $redirect_url = isset($_POST['redirect_url']) ? $_POST['redirect_url'] : '';
        $applink = isset($_POST['applink']) ? $_POST['applink'] : '';
        $terms = isset($_POST['terms']) ? true : false;
    
        if ($password !== $confirm_password) {
            $error_message = "Passwords do not match!";
            header("Location: " . modify_current_url(['redirect_url' => $redirect_url, 'applink' => $applink]));
        } elseif (!$terms) {
            $error_message = "You must agree to the Terms and Conditions!";
            header("Location: " . modify_current_url(['redirect_url' => $redirect_url, 'applink' => $applink]));
        } else {
            $response = signUpUser($email, $password, $confirm_password, $name);
    
            if ($response && isset($response['id'])) {
                $success_message = "Account created successfully! Please log in.";
                header("Location: https://auth.flake-systems.de/sendEmail.php?redirect_url=$redirect_url&email=$email&applink=$applink");
            } else {
                $error_message = "Error creating user: " . ($response['message'] ?? 'Unknown error');
                header("Location: " . modify_current_url(['redirect_url' => $redirect_url, 'applink' => $applink]));
            }
        }
    } else {
        //If disable_redirect isn't true, it will look for existing cookies on the client and redirect to the continue page if both token and userId is found.
        if ($_GET['disable_redirect'] != "true") {
            if ((isset($_COOKIE['auth_token'])) && (isset ($_COOKIE['user_id']))) {
                header("Location: https://auth.flake-systems.de/continue.php?redirect_url=" . ($authTools->validateRedirectURL($_GET['redirect_url'], $fallBackUrl)));
            }
        }
    }
?>

<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link rel="icon" type="image/x-icon" href="https://flake-systems.de/flakecraftbig.png">
        <title>FlakeAccounts Sign Up</title>
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
            <!-- Logo (if you want to add a logo) -->
            <div class="flex justify-center mb-8">
                <img src="https://flake-systems.de/flakecraftlogo.png" alt="Logo" class="h-10">
            </div>

            <!-- Sign Up Form -->
            <form action="signup.php" method="POST" id="register-form" class="space-y-6">
                <h2 class="text-2xl font-semibold text-center text-gray-900 dark:text-white">FlakeAccounts Sign Up</h2>

                <!-- Name Input -->
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Full Name</label>
                    <input type="text" name="name" id="name" required class="block w-full mt-1 px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:text-white dark:focus:ring-indigo-400 dark:focus:border-indigo-400">
                </div>

                <!-- Email Input -->
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Email</label>
                    <input type="email" name="email" id="email" required class="block w-full mt-1 px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:text-white dark:focus:ring-indigo-400 dark:focus:border-indigo-400">
                </div>

                <!-- Password Input -->
                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Password</label>
                    <input type="password" name="password" id="password" required class="block w-full mt-1 px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:text-white dark:focus:ring-indigo-400 dark:focus:border-indigo-400">
                </div>

                <!-- Confirm Password Input -->
                <div>
                    <label for="confirm-password" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Confirm Password</label>
                    <input type="password" name="confirm-password" id="confirm-password" required class="block w-full mt-1 px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:text-white dark:focus:ring-indigo-400 dark:focus:border-indigo-400">
                </div>

                <!-- Terms & Conditions Checkbox -->
                <div class="flex items-center">
                    <input type="checkbox" name="terms" id="terms" class="h-4 w-4 text-indigo-600" required>
                    <label for="terms" class="ml-2 text-sm text-gray-700 dark:text-gray-300">I agree to the <a href="#" class="text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 dark:hover:text-indigo-500">Terms and Conditions</a></label>
                </div>

                <!-- Error Message -->
                <?php if (isset($error_message)): ?>
                    <div class="text-red-600 text-center"><?php echo $error_message; ?></div>
                <?php endif; ?>

                <!-- Success Message -->
                <?php if (isset($success_message)): ?>
                    <div class="text-green-600 text-center"><?php echo $success_message; ?></div>
                <?php endif; ?>

                <input type="hidden" id="redirect-url" name="redirect_url" value="<?php echo $_GET['redirect_url'];?>">
                <input type="hidden" id="applink" name="applink" value="<?php echo $_GET['applink'];?>">

                <!-- Submit Button -->
                <div>
                    <button type="submit" class="primary-color-fsystems w-full text-white py-2 rounded-md shadow-md focus:outline-none focus:ring-2 focus:ring-opacity-50">
                    Sign Up
                    </button>
                </div>
            </form>
            <p class="text-center text-sm text-gray-500 mt-4 dark:text-gray-400">
            Already have an account? <a href="./login.php<?php echo "?" . $_SERVER['QUERY_STRING']; ?>" class="text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 dark:hover:text-indigo-500">Log in</a>
            </p>
        </div>

    </body>
</html>

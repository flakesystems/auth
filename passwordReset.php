<?php
    //Require authTools to do some fancy sh*t
    require("./authTools.php");
    $authTools = new AuthTools();

    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $redirect_url = isset($_POST['redirect_url']) ? trim($_POST['redirect_url']) :'';
        $email = isset($_POST['email']) ? trim($_POST['email']) :'';
        echo "<script>console.log(`Email: $email`);</script>";
        $request = $authTools->requestPasswordReset($email);
        header("Location: ./passwordResetSuccessful.php?redirect_url=" . $redirect_url . "&email=" . $email);
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
                <img src="https://flake-systems.de/flakecraftlogo.png" alt="Logo" class="h-10">
            </div>
            <form action="passwordReset.php" method="POST" class="space-y-6">
                <div>
                    <h2 class="text-2xl font-semibold text-center text-gray-900 dark:text-white">FlakeAccounts Login</h2>
                    <p class="text-xl text-center text-gray-900 dark:text-white">Reset password</p>
                </div>
                <div>
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Your account email</label>
                        <input type="email" name="email" id="email" required class="mb-6 block w-full mt-1 px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:text-white dark:focus:ring-indigo-400 dark:focus:border-indigo-400">
                    </div>
                    <button type="submit" class="primary-color-fsystems w-full text-white py-2 rounded-md shadow-md focus:outline-none focus:ring-2 focus:ring-opacity-50">
                    Send reset email
                    </button>
                </div>
                <input type="hidden" id="redirect-url" name="redirect_url" value="<?php echo $_GET['redirect_url'];?>">
            </form>
        </div>
    </body>
</html>
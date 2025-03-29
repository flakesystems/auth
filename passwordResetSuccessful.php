<?php
    //Require authTools to do some fancy sh*t
    require("./authTools.php");
    $authTools = new AuthTools();

    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $redirect_url = isset($_POST['redirect_url']) ? trim($_POST['redirect_url']) :'';
        $email = isset($_POST['email']) ? trim($_POST['email']) :'';
        $request = $authTools->requestPasswordReset($email);
        echo "<script>console.log(`Sent!`);</script>";
        header("Location: ./signupSuccessful.php?redirect_url=" . $redirect_url . "&email=" . $email . "&resent_mail=true");
    }
?>

<!DOCTYPE html>
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

            function redirect(url) {
                window.location.href = "https://auth.flake-systems.de/login.php<?php echo "?" . $_SERVER['QUERY_STRING']; ?>";
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

            <form action="signupSuccessful.php" method="POST" class="space-y-6">
                <div>
                    <h2 class="text-2xl font-semibold text-center text-gray-900 dark:text-white">Password reset email sent!</h2>
                    <p class="mb-3 text-xs text-center text-gray-900 dark:text-white">If this email belongs to an account</p>
                    <p class="text-xl font-semibold text-center text-gray-900 dark:text-white">Please check your mail (also spam)</p>
                </div>
                <input type="hidden" id="redirect-url" name="redirect_url" value="<?php echo $_GET['redirect_url'];?>">
                <input type="hidden" id="email" name="email" value="<?php echo $_GET['email'];?>">
                <div>
                    <a href="./login.php<?php echo "?" . $_SERVER['QUERY_STRING']; ?>"><button type="button" class="mb-3 primary-color-fsystems w-full text-white py-2 rounded-md shadow-md focus:outline-none focus:ring-2 focus:ring-opacity-50">
                    Go to login
                    </button></a>
                </div>
            </form>
        </div>

    </body>
</html>

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
        
            function getCookie(name) {
                return document.cookie.split(';').some(c => {
                    return c.trim().startsWith(name + '=');
                });
            }

            function deleteCookie(name) {
                document.cookie = name + "=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;";
                console.log("Cookie deleted:", name);
            }

            function logout() {
                deleteCookie("auth_token");
                location.reload();
                console.log("Logout");
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

            <h2 class="text-2xl font-semibold text-center text-gray-900 dark:text-white mb-4">Access Denied. You don't have the permission to view this website.</h2>
            <div class="mb-4">
                <a href="https://flake-systems.de"><button class="primary-color-fsystems w-full text-white py-2 rounded-md shadow-md focus:outline-none focus:ring-2 focus:ring-opacity-50">
                Go to flake-systems.de
                </button></a>
            </div>
            <div class="mb-4">
                <a href="<?php echo "https://auth.flake-systems.de/login.php?redirect_url=" . $_GET['origin'];?>"><button onclick="logout();" class="primary-color-fsystems w-full text-white py-2 rounded-md shadow-md focus:outline-none focus:ring-2 focus:ring-opacity-50">
                Use another Account
                </button></a>
            </div>
        </div>

    </body>
</html>

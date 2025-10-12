<?php
    require __DIR__ . "/../vendor/autoload.php";

    $client = new Google\Client();
    $client->setClientId("704460822405-0gjtdkl1acustankf6k9p3o3444lpb7g.apps.googleusercontent.com");
    $client->setClientSecret("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");
    
    // Set redirect URI based on environment
    if ($_SERVER['HTTP_HOST'] === 'localhost:8000' || $_SERVER['HTTP_HOST'] === 'localhost:8080') {
        $client->setRedirectUri("http://localhost:8000/php/authorized.php");
    } elseif (strpos($_SERVER['HTTP_HOST'], 'github.dev') !== false) {
        // GitHub Codespaces
        $client->setRedirectUri("https://" . $_SERVER['HTTP_HOST'] . "/php/authorized.php");
    } elseif (strpos($_SERVER['HTTP_HOST'], 'vercel.app') !== false) {
        // Vercel deployment
        $client->setRedirectUri("https://" . $_SERVER['HTTP_HOST'] . "/php/authorized.php");
    } else {
        // Production or other environments
        $client->setRedirectUri("https://al-ghaya.vercel.app/php/authorized.php");
    }

    $client->addScope("email");
    $client->addScope("profile");
?>
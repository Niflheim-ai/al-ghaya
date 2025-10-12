<?php
    require __DIR__ . "/../vendor/autoload.php";

    $client = new Google\Client();
    $client->setClientId("704460822405-0gjtdkl1acustankf6k9p3o3444lpb7g.apps.googleusercontent.com");
    $client->setClientSecret("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");
    if ($_SERVER['HTTP_HOST'] === 'localhost:8080') {
        $client->setRedirectUri("http://localhost:8080/al-ghaya-2/php/authorized.php");
    } else {
        $client->setRedirectUri("https://alghaya-2.loca.lt/al-ghaya-2/php/authorized.php");
    }


    $client->addScope("email");
    $client->addScope("profile");
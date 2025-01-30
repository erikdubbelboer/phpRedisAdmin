<?php

function authCheck($login)
{
    global $redis, $server;

    // $server['username'] = $login['username'];
    // $server['password'] = $login['password'];

    // $redis = new Predis\Client($server);

    // try {
    //     $redis->connect();
    // } catch (Predis\CommunicationException $exception) {
    //     die('ERROR: ' . $exception->getMessage());
    // }

    try {
        $redis->auth($login['username'], $login['password']);
    } catch (Predis\Response\ServerException $exception) {
        return false;
    }
    return true;
}

// This fill will perform HTTP digest authentication. This is not the most secure form of authentication so be carefull when using this.
function authHttpDigest()
{
    $realm = 'phpRedisAdmin';

    if (!isset($_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW'])) {
        header('HTTP/1.1 401 Unauthorized');
        header('WWW-Authenticate: Basic realm="' . $realm . '"');
        die('NOAUTH -- Authentication required');
    }

    $login = [
        'username' => $_SERVER['PHP_AUTH_USER'],
        'password' => $_SERVER['PHP_AUTH_PW'],
    ];

    if (!authCheck($login)) {
        header('HTTP/1.1 401 Unauthorized');
        header('WWW-Authenticate: Basic realm="' . $realm . '"');
        die('NOAUTH -- Authentication required');
    }

    return $login;
}

// Perform auth using a standard HTML <form> submission and cookies to save login state
function authCookie()
{
    if (!empty($_SESSION['phpRedisAdminLogin'])) {
        // We have a cookie; is it correct?
        // Cookie value looks like "username:password-hash"
        $login = explode(':', $_SESSION['phpRedisAdminLogin']);
        if (count($login) === 2) {
            $login = [
                'username' => $login[0],
                'password' => $login[1],
            ];
            if (authCheck($login)) {
                return $login;
            }
        }
    }

    if (isset($_POST['username'], $_POST['password'])) {
        // Login form submitted; correctly?
        $login = [
            'username' => $_POST['username'],
            'password' => $_POST['password'],
        ];

        if (authCheck($login)) {
            $_SESSION['phpRedisAdminLogin'] =  $login['username'] . ':' . $login['password'];
            // This should be an absolute URL, but that's a bit of a pain to generate; this will work
            header("Location: index.php");
            die();
        }
    }

    // If we're here, we don't have a valid login cookie and we don't have a
    //  valid form submission, so redirect to the login page if we aren't
    //  already on that page
    if (!defined('LOGIN_PAGE')) {
        header("Location: login.php");
        die();
    }

    // We must be on the login page without a valid cookie or submission
    return null;
}

if (!empty($config['cookie_auth'])) {
    $login = authCookie();
} else {
    $login = authHttpDigest();
}

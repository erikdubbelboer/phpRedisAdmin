<?php

function authCheck($login)
{
    global $redis;

    try {
        $redis->auth($login['username'], $login['password']);
    } catch (Predis\Response\ServerException $exception) {
        return false;
    }
    return true;
}

// This fill will perform HTTP basic authentication. The authentication data will be sent and stored as plaintext
// Please make sure to use HTTPS proxy such as Apache or Nginx to prevent traffic eavesdropping.
function authHttpBasic()
{
    $realm = 'phpRedisAdmin';

    if (!isset($_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW'])) {
        try {
            global $redis;
            $redis->ping();
        } catch (Predis\Response\ServerException $exception) {
            header('HTTP/1.1 401 Unauthorized');
            header('WWW-Authenticate: Basic realm="' . $realm . '"');
            die('NOAUTH -- Authentication required');
        }
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
    if (!empty($_COOKIE['phpRedisAdminLogin'])) {
        // We have a cookie; is it correct?
        // Cookie value looks like "username:password-hash"
        $login = explode(':', $_COOKIE['phpRedisAdminLogin'], 2);
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
            setcookie('phpRedisAdminLogin', $login['username'] . ':' . $login['password']);
            // This should be an absolute URL, but that's a bit of a pain to generate; this will work
            header("Location: index.php");
            die();
        }
    }

    try {
        global $redis;
        $redis->ping();
    } catch (Predis\Response\ServerException $exception) {
        // If we're here, we don't have a valid login cookie and we don't have a
        // valid form submission, so redirect to the login page if we aren't
        // already on that page
        if (!defined('LOGIN_PAGE')) {
            header("Location: login.php");
            die();
        }
    }

    // We must be on the login page without a valid cookie or submission
    return null;
}

if (!empty($config['cookie_auth'])) {
    $login = authCookie();
} else {
    $login = authHttpBasic();
}

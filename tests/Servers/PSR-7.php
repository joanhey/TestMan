<?php

// TODO conver to use PSR-7

// HTTP methods
const AVAILABLE_METHODS = ['GET', 'POST', 'PUT', 'DELETE', 'PATCH', 'HEAD', 'OPTIONS'];

if (!in_array($_SERVER['REQUEST_METHOD'], AVAILABLE_METHODS)) {
    http_response_code(400);
    echo '';
    return;
}

// Parse JSON
if(isset($_SERVER['CONTENT_TYPE']) && $_SERVER['CONTENT_TYPE'] == 'application/json') {
    $_POST = \json_decode(file_get_contents('php://input'), true) ?? [];
    file_put_contents("php://stdout", "\nRequested: $_POST");
}


// Router
$response = match (parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH)) {
    '/'          => 'Hello World!',
    '/get'       => json_encode($_GET),
    '/post'      => json_encode($_POST),
    '/headers'   => json_encode(getallheaders()),
    '/method'    => $_SERVER['REQUEST_METHOD'],
    '/server_ip' => $_SERVER['SERVER_ADDR'],
    '/ip'        => $_SERVER['REMOTE_ADDR'],
    '/cookies'   => cookies(),
    '/session/start' => sessionStart(),
    '/session'   => session(),
    '/upload'    => json_encode($_FILES),

    //Info
    '/info'      => info(),
    '/globals'   => globals(),
    '/extensions'=> json_encode(get_loaded_extensions()),
    '/phpinfo'   => php_info(),

    default => (static function (): string {
        http_response_code(404);
        return '404 Not Found';
    })(),
};

echo $response;


function info(): string
{
    return json_encode([
        'PHP_VERSION'       => PHP_VERSION,
        'PHP_VERSION_ID'    => PHP_VERSION_ID,
        'PHP_MAJOR_VERSION' => PHP_MAJOR_VERSION,
        'PHP_MINOR_VERSION' => PHP_MINOR_VERSION,
        'PHP_SAPI'          => PHP_SAPI,
        'PHP_OS'            => PHP_OS,
        'PHP_OS_FAMILY'     => PHP_OS_FAMILY,

        //Manual change
        'PSR-7'             => false,
        'SERVER'            => 'cli-server',
        'FRAMEWORK'         => '',

        'GLOBALS'           => $GLOBALS,
        //'PHP_CLI_PROCESS_TITLE' => PHP_CLI_PROCESS_TITLE,
    ]);
}

function globals(): string
{
    ob_start();
    print_r($GLOBALS);
    return ob_get_clean();
}

function cookies(): string
{
    if($_GET === []) {
        return json_encode($_COOKIE);
    }

    if(isset($_GET['set'])) {
        foreach($_GET['set'] as $name => $value) {
            setcookie($name, $value);
        }
        return json_encode($_COOKIE);
    }

    if(isset($_GET['delete'])) {
        foreach($_GET['delete'] as $name) {
            if (isset($_COOKIE[$name])) {
                unset($_COOKIE[$name]); 
                setcookie($name, '', -1);
            }
        }
        return json_encode($_COOKIE);
    }

}

function sessionStart(): string
{
    //session_id('asdf131sd3f132sd1f3as');
    return json_encode([
        'start'  => session_start(),
        'name'   => session_name(),
        'id'     => session_id(),
        'status' => session_status(),
        'cookies'=> $_COOKIE,
        'cookie' => session_get_cookie_params(),
        'data'   => $_SESSION, 
    ]);
}

function session(): string
{
    session_start();

    if($_GET === []) {
        return json_encode($_SESSION);
    }

    if(isset($_GET['set'])) {
        foreach($_GET['set'] as $name => $value) {
            $_SESSION[$name] = $value;
        }
        //Worker::safeEcho(print_r($_SESSION));
        return json_encode($_SESSION);
    }

    if(isset($_GET['delete'])) {
        foreach($_GET['delete'] as $name) {
            if (isset($_SESSION[$name])) {
                unset($_SESSION[$name]); 
            }
        }
        return json_encode($_SESSION);
    }

}

function php_info(): string
{
    ob_start();
    phpinfo();
    return ob_get_clean();
}

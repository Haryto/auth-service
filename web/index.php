<?php
require_once(__DIR__ . '/../vendor/autoload.php');
require_once('User.php');

$page = explode('/', trim($_SERVER['REQUEST_URI'], '/'));

switch($page[1]) {
    case 'login':
        $user = new \AuthService\User();
        $user->nickname = $_POST['nickname'];
        $user->password = $_POST['password'];

        $response = $user->login();
        break;
    case 'register':
        $user = new \AuthService\User();
        $user->firstname = $_POST['firstname'];
        $user->lastname = $_POST['lastname'];
        $user->nickname = $_POST['nickname'];
        $user->age = $_POST['age'];
        $user->password = $_POST['password'];

        $response = $user->register();
        break;
    case 'action':
        $source = $_POST['source'];

        // If user is logged in, get ID from cookies.
        if (!empty($_COOKIE['user_id'])) {
            $userID = $_COOKIE['user_id'];
        }
        else {
            // Otherwise generate random ID and save it in user cookies.
            $userID = md5(rand(0, 100000));
            setcookie('user_id', $userID);
        }

        $user = new \AuthService\User();

        $response = $user->recordAction($userID, $source);
        break;
    default:
        $response = [];
        $response['code'] = 400;
        $response['data']['message'] = 'Unknown method';
}

http_response_code($response['code']);
if (!empty($response['data']))
    print_r(json_encode($response['data']));
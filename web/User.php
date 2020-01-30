<?php

namespace AuthService;

require_once('./QuickStorage.php');

/**
 * User class.
 * It is used to create user object.
 * Provides methods for user login, registration and activity recording.
 */
class User
{
    /**
     * File where all users are stored.
     */
    public const USER_FILE = __DIR__ . '/../storage/users.json';

    /**
     * File where all user action records are stored.
     */
    public const RECORD_FILE = __DIR__ . '/../storage/records.json';

    /**
     * User first name.
     * <tt>null</tt> if is not set.
     *
     * @var string|null
     */
    public $firstname;

    /**
     * User last name.
     * <tt>null</tt> if is not set.
     *
     * @var string|null
     */
    public $lastname;

    /**
     * User nickname.
     * <tt>null</tt> if is not set.
     *
     * @var string|null
     */
    public $nickname;

    /**
     * User age.
     * <tt>null</tt> if is not set.
     *
     * @var int|null
     */
    public $age;

    /**
     * User password.
     * <tt>null</tt> if is not set.
     *
     * @var string|null
     */
    public $password;

    /**
     * Storage which is used to store users data.
     *
     * @var \SocialTech\StorageInterface
     */
    private $storage;

    /**
     * User constructor.
     */
    public function __construct()
    {
        $this->storage = new QuickStorage();
    }

    /**
     * Logs user in.
     *
     * @return array
     */
    public function login(): array
    {
        $response = [];

        if (empty($this->nickname) || empty($this->password)) {
            $response['code'] = 400;
            $response['data']['message'] = 'Necessary data is not provided';
            return $response;
        }

        if (!$this->storage->exists(User::USER_FILE)) {
            $response['code'] = 404;
            $response['data']['message'] = 'User does not exist';
            return $response;
        }

        $users = json_decode($this->storage->load(User::USER_FILE));
        if ($users) {
            foreach ($users as $user) {
                if ($user->nickname == $this->nickname) {
                    if ($user->password == $this->password) {
                        setcookie('user_id', $user->id);
                        $response['code'] = 200;
                        $response['data']['id'] = $user->id;
                    }
                    else {
                        $response['code'] = 403;
                        $response['data']['message'] = 'Password is incorrect';
                        break;
                    }
                }
            }
            if (!isset($response['code'])) {
                $response['code'] = 404;
                $response['data']['message'] = 'User does not exist';
            }
        }
        else {
            // JSON is invalid.
            $response['code'] = 500;
            $response['data']['message'] = 'Internal server error';
        }

        return $response;
    }

    /**
     * Registers not existing user.
     * If successfully registered, logs user in.
     *
     * @return array
     */
    public function register(): array
    {
        $response = [];

        if (empty($this->firstname) ||
            empty($this->lastname) ||
            empty($this->nickname) ||
            empty($this->age) ||
            empty($this->password)
        ) {
            $response['code'] = 400;
            $response['data']['message'] = 'Necessary data is not provided';
            return $response;
        }

        // If file exists, then this user is not first one.
        if ($this->storage->exists(User::USER_FILE)) {
            $users = json_decode($this->storage->load(User::USER_FILE));
            // Continue if JSON is valid.
            if ($users) {
                $id = 0;
                foreach ($users as $user) {
                    // If user already exists, return.
                    if ($user->nickname == $this->nickname) {
                        $response['code'] = 200; // HTTP code 204 matches better, but Postman had some troubles with it.
                        $response['data']['message'] = 'User already exists';
                        return $response;
                    }
                    $id = $user->id;
                }
                // ID of new user is incremented ID of last user in file.
                $id++;
            }
            else {
                // JSON is not valid.
                $response['code'] = 500;
                $response['data']['message'] = 'Internal server error';
                return $response;
            }
        }
        else {
            // If file does not exist yet, this user is first one.
            $id = 1;
            $users = [];
        }

        $users[] = [
            'id' => $id,
            'firstname' => $this->firstname,
            'lastname' => $this->lastname,
            'nickname' => $this->nickname,
            'age' => $this->age,
            'password' => $this->password
        ];

        $this->storage->store(User::USER_FILE, json_encode($users));
        setcookie('user_id', $id);

        $response['code'] = 200;
        $response['data']['id'] = $id;
        return $response;
    }

    /**
     * Records user activity on page (button click, page switch etc.).
     * If user is not logged in, generated random ID (string with 16-symbols length).
     *
     * @param string|int $userID
     * @param string $source
     * @return array
     */
    public function recordAction($userID, $source): array
    {
        $response = [];

        // If file exists, then this record is not first one.
        if ($this->storage->exists(User::RECORD_FILE)) {
            $records = json_decode($this->storage->load(User::RECORD_FILE));
            // Continue if JSON is valid.
            if ($records) {
                $id = 0;
                foreach ($records as $record) {
                    $id = $record->id;
                }
                // ID of new record is incremented ID of last record in file.
                $id++;
            }
            else {
                // JSON is not valid.
                $response['code'] = 500;
                $response['data']['message'] = 'Internal server error';
                return $response;
            }
        }
        else {
            // If file does not exist yet, this user is first one.
            $id = 1;
            $records = [];
        }

        $dateTime = date('Y-m-d H:i:s');

        $records[] = [
            'id' => $id,
            'id_user' => $userID,
            'source_label' => $source,
            'date_created' => $dateTime
        ];

        $this->storage->store(User::RECORD_FILE, json_encode($records));

        $response['code'] = 200;
        $response['data']['id'] = $id;
        return $response;
    }
}
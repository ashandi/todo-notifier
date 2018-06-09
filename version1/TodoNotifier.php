<?php

namespace Notifier;

use Todo\Todo;
use Todo\TodoComment;
use Todo\TodoService;
use Todo\TodoServiceClient;

/**
 * Class TodoNotifier
 * @package Notifier
 */
class TodoNotifier implements NotifierInterface
{
    /**
     * @var int
     */
    public $taskId;

    /**
     * @var TodoComment
     */
    public $comment;

    /**
     * @var array
     */
    public $tmpFiles = [];

    /**
     * Method sends email with given $subject and $body
     * for all recipients from $addresses array
     *
     * @param string $subject
     * @param string $body
     * @param array $addresses
     *
     * @return mixed
     */
    public static function sendEmail($subject, $body, array $addresses)
    {
        $mailer = new phpmailer();
        $mailer->IsSMTP();
        $mailer->IsHTML(true);
        $mailer->CharSet = 'utf-8';
        $mailer->Host = self::getConfig('smtp_host');
        $mailer->Subject = $subject ?: 'Email notification';
        $mailer->Body = $body;

        foreach ($addresses as $address) {
            $mailer->AddAddress($address, '', 0);
        }

        return $mailer->Send();
    }

    /**
     * Method tries to get parameter from config
     * by given $key
     *
     * @param string $key
     *
     * @return mixed
     */
    private static function getConfig(string $key)
    {
        $cfg = \Configuration::getInstance();

        return $cfg[$key] ?? '';
    }

    /**
     * Method attaches given $file to current TodoComment
     *
     * @param $file
     *
     * @return $this
     */
    public function attachFile($file)
    {
        $this->getComment()->attachFile($file);
        $this->tmpFiles[] = $file;

        return $this;
    }

    /**
     * Method returns current TodoComment
     * If it doesn't exists method will create it
     *
     * @return TodoComment
     */
    private function getComment()
    {
        if (!$this->comment) {
            $this->comment = $this->getTodo()->addComment('');
        }

        return $this->comment;
    }

    /**
     * Method returns Todo
     * for host and user from config
     *
     * @return Todo
     */
    private function getTodo()
    {
        $config = self::getConfig('twgate');
        $host = $config['host'] ?? '';
        $user = $config['user'] ?? '';

        $todoService = new TodoService(new TodoServiceClient($host, $user));

        return $todoService->getTask($this->taskId);
    }

    /**
     * Method creates new or updates existing file
     * with $fileLabel name in tmp directory
     * In second case method fully replaces file's content
     * by given $string
     *
     * @param string $fileLabel
     * @param string $string
     *
     * @return $this
     * @throws \Exception
     */
    public function attachString($fileLabel, $string)
    {
        $tmpFilePath = $this->getTmpFilePath($fileLabel);

        file_put_contents($tmpFilePath, $string);

        $this->getComment()->attachFile($tmpFilePath);

        $this->tmpFiles[] = $tmpFilePath;

        return $this;
    }

    /**
     * Method tries to touch file with given $filename
     * in tmp directory
     * In success case it returns full path for this file
     *
     * @param $filename
     *
     * @return string
     * @throws \Exception
     */
    private function getTmpFilePath($filename)
    {
        $filename = str_replace('..', '', $filename);
        $tmpFilePath = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $filename;
        if (!touch($tmpFilePath)) {
            throw new \Exception('Unable to touch target file');
        }

        return $tmpFilePath;
    }

    /**
     * Method saves new comment for todo
     * with given $authorEmail and $message
     *
     * @param string $authorEmail
     * @param null $message
     *
     * @return bool
     */
    public function notify($authorEmail, $message = null)
    {
        if ($message !== null) {
            $this->setMessage($message);
        }

        return $this->isTestNotification($authorEmail)
            ? true
            : $this->sendRealNotification($authorEmail);
    }

    /**
     * Returns true if current notification query is test
     *
     * @param $authorEmail
     *
     * @return bool
     */
    private function isTestNotification($authorEmail)
    {
        return strpos($authorEmail, "test") !== false;
    }

    /**
     * Method sends real notification
     *
     * @param $authorEmail
     *
     * @return bool
     */
    private function sendRealNotification($authorEmail)
    {
        $auth = $this->createAuthentication($authorEmail);
        $result = $this->getComment()->save($authorEmail, $auth);

        $this->removeTmpFiles();

        return $result;
    }

    /**
     * Method returns data for authentication
     *
     * @param $email
     *
     * @return string
     */
    private function createAuthentication($email)
    {
        $connection = $this->getConnection();
        $statement = $connection->prepare('SELECT * FROM user_salts WHERE email = :email');
        $statement->execute(compact('email'));

        $result = $statement->fetchAll();

        return substr(crc32($result[0]['salt'] . $email), 0, 3);
    }

    /**
     * Method establishes connection to database
     *
     * @return \PDO
     */
    protected function getConnection()
    {
        $driver = self::getConfig('db_driver');
        $host = self::getConfig('db_host');
        $database = self::getConfig('db_name');
        $dsn = "$driver:dbname=$database;host=$host";

        $user = self::getConfig('db_user');
        $password = self::getConfig('db_password');

        return new \PDO($dsn, $user, $password);
    }

    /**
     * Method removes all files from $this->tmpFiles
     */
    private function removeTmpFiles()
    {
        foreach ($this->tmpFiles as $file) {
            if (file_exists($file)) {
                unlink($file);
            }
        }
    }

    /**
     * Setter for comment field
     *
     * @param $message
     *
     * @return $this
     */
    public function setMessage($message)
    {
        $this->getComment()->setBody($message);

        return $this;
    }

    /**
     * Getter for comment field
     *
     * @return mixed
     */
    public function getMessage()
    {
        return $this->getComment()->getBody();
    }

    /**
     * Setter for taskId field
     *
     * @param $taskId
     *
     * @return $this
     */
    public function setTaskId($taskId)
    {
        $this->taskId = $taskId;

        return $this;
    }

    /**
     * Getter for taskId field
     *
     * @return int
     */
    public function getTaskId()
    {
        return $this->taskId;
    }
}

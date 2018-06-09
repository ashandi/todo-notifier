<?php

namespace Task;

use Notifier\NotifierInterface;

/**
 * Class TaskNotifier
 * @package Task
 */
class TaskNotifier
{
    /**
     * @var NotifierInterface
     */
    private $notifier;

    /**
     * TaskNotifier constructor.
     *
     * @param NotifierInterface $notifier
     */
    public function __construct(NotifierInterface $notifier)
    {
        $this->notifier = $notifier;
    }

    /**
     * Method sends email notifications for all subscribers
     * of task of given comment
     *
     * @param TaskComment $taskComment
     */
    public function notifyAboutNewComment(TaskComment $taskComment)
    {
        $task = $taskComment->getTask();

        $subject = 'New comment for task #' . $task->getNumber();
        $body = $taskComment->getBody();
        $addresses = $task->getSubscribers();

        if (count($addresses) > 0) {
            $this->notifier->sendEmail($subject, $body, $addresses);
        }
    }
}

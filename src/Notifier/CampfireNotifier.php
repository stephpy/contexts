<?php

namespace Sanpi\Behatch\Notifier;

use Behat\Behat\Event\StepEvent;
use Behat\Behat\Event\SuiteEvent;

class CampfireNotifier extends BaseNotifier
{
    private $lastTimeError;

    /**
     * @AfterStep
     */
    public function afterStep(StepEvent $event)
    {
        if ($event->getResult() == StepEvent::FAILED) {
            //spam prevention
            if ($this->lastTimeError == null || time() - $this->lastTimeError > $this->getParameter('campfire', 'spam_timeout')) {
                $message = $this->getParameter('campfire', 'prefix') ? '[' . $this->getParameter('campfire', 'prefix') . '] ' : '';
                $message .= 'Behat is failing...';
                $message .= "\nScenario : ".$event->getStep()->getParent()->getTitle();
                $message .= "\n  ".$event->getStep()->getText();
                $message .= "\n    ".$event->getException()->getMessage();
                $this->notify($message);

                $this->lastTimeError = time();
            }
        }
    }

    /**
     * @AfterSuite
     */
    static public function staticAfterSuite(SuiteEvent $event)
    {
        var_dump($event->getContextParameters());
        $notifier = new self($event->getContextParameters());
        $notifier->afterSuite($event);
    }


    private function afterSuite(SuiteEvent $event)
    {
        if ($event->isCompleted()) {
            $prefix = $this->getParameter('campfire', 'prefix') ? '['.$this->getParameter('campfire', 'prefix').'] ' : '';
            $statuses = $event->getLogger()->getScenariosStatuses();
            if ($statuses['failed'] > 0) {
                $this->notify($prefix . 'Behat suite finished :thumbsdown::shit:');
            }
            else {
                $this->notify($prefix . 'Behat suite finished :thumbsup::sparkles:');
            }
        }
    }

    protected function notify($message)
    {
        $campfireUrl =   $this->getParameter('campfire', 'url');
        $campfireToken = $this->getParameter('campfire', 'token');
        $campfireRoom =  $this->getParameter('campfire', 'room');

        if ($campfireUrl == null) {
            throw new \Exception('You must set a campfire URL in behat.yml');
        }

        if ($campfireToken == null) {
            throw new \Exception("You must set a campfire room in behat.yml");
        }

        if ($campfireRoom == null) {
            throw new \Exception("You must set a campfire token in behat.yml");
        }

        $cmd = sprintf("curl -s -u %s:X -H 'Content-Type: application/json' -d %s %s/room/%s/speak.xml",
            $campfireToken,
            escapeshellarg(json_encode(array('message' => array('body' => $message)))),
            trim($campfireUrl, '/'),
            $campfireRoom);
        exec($cmd, $output, $return);
        if ($return != 0) {
            throw new \Exception(sprintf("Unable to send campfire notification with curl :\n%s",
                implode("\n", $output)));
        }
    }
}

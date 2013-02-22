<?php

namespace Sanpi\Behatch\Notifier;

use Sanpi\Behatch\Context\BaseContext;

abstract class BaseNotifier extends BaseContext
{
    abstract protected function notify($message);

    protected function getParameter($extension, $name)
    {
        return $this->getMainContext()->getSubContext('behatch')
            ->getParameter('notifiers', $extension, $name);
    }

}

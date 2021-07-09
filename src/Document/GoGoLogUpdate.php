<?php

namespace App\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;

/**
 * Special log for code update messages.
 *
 * @MongoDB\Document
 */
class GoGoLogUpdate extends GoGoLog
{
    public function displayTimestamp()
    {
        return false;
    }
}

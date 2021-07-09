<?php

namespace App\Controller\Admin;

abstract class InteractType
{
    const Deleted = -1;
    const Add = 0;
    const Edit = 1;
    const Import = 4;
    const Restored = 5;
    const ModerationResolved = 6;
}

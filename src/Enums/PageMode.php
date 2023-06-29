<?php

declare(strict_types=1);

namespace araise\CrudBundle\Enums;

enum PageMode: string implements PageModeInterface
{
    case NORMAL = 'normal';
    case MODAL = 'modal';
}

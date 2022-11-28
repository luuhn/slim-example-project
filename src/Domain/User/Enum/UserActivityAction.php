<?php

namespace App\Domain\User\Enum;

enum UserActivityAction: string
{
    case CREATED = 'created';
    case UPDATED = 'updated';
    case DELETED = 'deleted';
    case READ = 'read';
}

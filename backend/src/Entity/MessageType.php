<?php

namespace App\Entity;

enum MessageType: string
{
    case USER = 'user';
    case SYSTEM = 'system';
    case BOT = 'bot';
}

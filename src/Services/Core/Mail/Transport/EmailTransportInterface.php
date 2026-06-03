<?php

declare(strict_types=1);

namespace App\Services\Core\Mail\Transport;

use App\Services\Core\Mail\EmailMessage;

interface EmailTransportInterface
{
    public function send(EmailMessage $message): void;
}

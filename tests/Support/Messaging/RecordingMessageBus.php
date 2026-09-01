<?php

declare(strict_types=1);

namespace App\Tests\Support\Messaging;

use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;

/**
 * Bus de test : enregistre les messages dispatchés sans les router (vérification du couplage
 * par événement, US-060).
 */
final class RecordingMessageBus implements MessageBusInterface
{
    /** @var list<object> */
    public array $dispatched = [];

    public function dispatch(object $message, array $stamps = []): Envelope
    {
        $this->dispatched[] = $message;

        return $message instanceof Envelope ? $message : new Envelope($message);
    }
}

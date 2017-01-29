<?php

namespace Spatie\MailableTest;

use Exception;
use ReflectionClass;
use ReflectionParameter;
use Illuminate\Mail\Mailable;

class MailableFactory
{
    /** @var \Spatie\MailableTest\ArgumentValueProvider */
    protected $argumentValueProvider;

    public function __construct(ArgumentValueProvider $argumentValueProvider)
    {
        $this->argumentValueProvider = $argumentValueProvider;
    }

    public function getInstance(string $mailableClass, string $toEmail, $defaultValues): Mailable
    {
        if (! class_exists($mailableClass)) {
            throw new Exception("Mailable `{$mailableClass}` does not exist.");
        }

        $argumentValues = $this->getArguments($mailableClass, $defaultValues);

        $mailableInstance = new $mailableClass(...$argumentValues);

        $mailableInstance = $this->setRecipient($mailableInstance, $toEmail);

        return $mailableInstance;
    }

    public function getArguments(string $mailableClass, array $defaultValues)
    {
        $parameters = (new ReflectionClass($mailableClass))
            ->getConstructor()
            ->getParameters();

        return collect($parameters)
            ->map(function (ReflectionParameter $reflectionParameter) use ($mailableClass, $defaultValues) {
                return $this->argumentValueProvider->getValue(
                    $mailableClass,
                    $reflectionParameter->getName(),
                    (string) $reflectionParameter->getType(),
                    $defaultValues[$reflectionParameter->getName()] ?? null
                );
            });
    }

    protected function setRecipient(Mailable $mailableInstance, string $toEmail): Mailable
    {
        $mailableInstance->to($toEmail);
        $mailableInstance->cc([]);
        $mailableInstance->bcc([]);

        return $mailableInstance;
    }
}

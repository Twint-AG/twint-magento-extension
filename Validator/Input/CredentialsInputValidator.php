<?php

declare(strict_types=1);

namespace Twint\Magento\Validator\Input;

use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Twint\Sdk\Value\Environment;

class CredentialsInputValidator extends InputValidator
{
    private ValidatorInterface $validator;

    public function validate(array $certificate, string $environment, string $storeUuid): array
    {
        $this->validator = Validation::createValidator();

        return array_merge(
            $this->formatViolations('Certificate', $this->validateCertificate($certificate)),
            $this->formatViolations('Environment', $this->validateEnvironment($environment)),
            $this->formatViolations('Store UUID', $this->validateStoreUuid($storeUuid))
        );
    }

    private function validateCertificate(array $cert): ConstraintViolationListInterface
    {
        $constraints = new Assert\Collection([
            'certificate' => [
                new Assert\NotBlank([
                    'message' => 'The certificate field cannot be blank.',
                ]),
                new Assert\Length([
                    'max' => 8192,
                    'maxMessage' => 'The certificate must be at most {{ limit }} characters long.',
                ]),
            ],
            'passphrase' => [
                new Assert\NotBlank([
                    'message' => 'The certificate passphrase field cannot be blank.',
                ]),
                new Assert\Length([
                    'max' => 4096,
                    'maxMessage' => 'The certificate passphrase must be at most {{ limit }} characters long.',
                ]),
            ],
        ]);

        return $this->validator->validate($cert, $constraints);
    }

    private function validateEnvironment(string $environment): ConstraintViolationListInterface
    {
        $constraints = [
            new Assert\NotBlank(),
            new Assert\Choice([
                'choices' => [Environment::PRODUCTION, Environment::TESTING],
                'message' => 'The environment field must be either "PRODUCTION" or "TESTING".',
            ]),
        ];

        return $this->validator->validate($environment, $constraints);
    }

    private function validateStoreUuid(string $uuid): ConstraintViolationListInterface
    {
        $constraints = [
            new Assert\NotBlank([
                'message' => 'The Store UUID field cannot be blank.',
            ]),
            new Assert\Regex([
                'pattern' => '/^[a-f0-9]{8}-[a-f0-9]{4}-4[a-f0-9]{3}-[89ab][a-f0-9]{3}-[a-f0-9]{12}$/i',
                'message' => 'The value "{{ value }}" is not a valid UUID v4.',
            ]),
        ];

        return $this->validator->validate($uuid, $constraints);
    }
}

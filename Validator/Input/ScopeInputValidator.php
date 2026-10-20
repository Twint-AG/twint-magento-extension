<?php

declare(strict_types=1);

namespace Twint\Magento\Validator\Input;

use Symfony\Component\Validator\Constraints\Choice;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ScopeInputValidator extends InputValidator
{
    private ValidatorInterface $validator;

    public function validate(string $scope): array
    {
        $this->validator = Validation::createValidator();

        return $this->formatViolations('Scope', $this->validateScope($scope));
    }

    private function validateScope(string $scope): ConstraintViolationListInterface
    {
        $constraints = [
            new Choice([
                'choices' => ['', 'websites', 'stores'],
                'message' => 'The scope field must be either "" or "websites" or "stores".',
            ]),
        ];

        return $this->validator->validate($scope, $constraints);
    }
}

<?php

declare(strict_types=1);

namespace Twint\Magento\Validator\Input;

use Symfony\Component\Validator\ConstraintViolationListInterface;

abstract class InputValidator
{
    protected function formatViolations(string $input, ConstraintViolationListInterface $violations): array
    {
        $errors = [];
        foreach ($violations as $violation) {
            // Get the exact field name that has an error
            $field = $violation->getPropertyPath();
            $message = $violation->getMessage();

            // Combine field name and message
            $errors[] = sprintf("{$input} %s: %s", $field, $message);
        }

        return $errors;
    }
}

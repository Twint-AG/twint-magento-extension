<?php

declare(strict_types=1);

namespace Twint\Magento\Validator\Input;

use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class CertificateFileValidator extends InputValidator
{
    private ValidatorInterface $validator;

    public function validate(array $file, string $password): array
    {
        $this->validator = Validation::createValidator();

        return array_merge(
            $this->formatViolations('File', $this->validateFile($file)),
            $this->formatViolations('Password', $this->validatePassword($password))
        );
    }

    private function validateFile(array $fileData): ConstraintViolationListInterface
    {
        $constraints = new Assert\Collection([
            'name' => new Assert\NotBlank(),
            'full_path' => new Assert\NotBlank(),
            'type' => new Assert\EqualTo([
                'value' => 'application/x-pkcs12',
                'message' => 'Only .p12 files are allowed.',
            ]),
            'tmp_name' => new Assert\NotBlank(),
            'error' => new Assert\EqualTo([
                'value' => 0,
                'message' => 'File upload encountered an error.',
            ]),
            'size' => new Assert\LessThanOrEqual([
                'value' => 128 * 1024, // 128 KB limit
                'message' => 'File size must be less than 128 KB.',
            ]),
        ]);

        return $this->validator->validate($fileData, $constraints);
    }

    private function validatePassword(string $password): ConstraintViolationListInterface
    {
        $constraints = [
            new Assert\NotBlank(),
            new Assert\Length([
                'max' => 128,
                'minMessage' => 'Password cannot longer than 128 characters.',
            ]),
        ];

        return $this->validator->validate($password, $constraints);
    }
}

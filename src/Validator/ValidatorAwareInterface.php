<?php

declare(strict_types=1);

namespace Dynamite\Validator;

use Symfony\Component\Validator\Validator\ValidatorInterface;

interface ValidatorAwareInterface
{
    public function setValidator(ValidatorInterface $validator): void;
}

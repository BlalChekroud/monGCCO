<?php

namespace App\Validator;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Form\FormInterface;

class PasswordMatchValidator extends ConstraintValidator
{
    public function validate($value, Constraint $constraint)
    {
        /* @var PasswordMatch $constraint */

        // Assurer que la contrainte est bien une instance de PasswordMatch
        if (!$constraint instanceof PasswordMatch) {
            throw new UnexpectedTypeException($constraint, PasswordMatch::class);
        }

        // Obtiens le formulaire racine
        /** @var FormInterface $form */
        $form = $this->context->getRoot();
        
        // Assurer que les champs sont présents
        if (!$form instanceof FormInterface || !$form->has('plainPassword') || !$form->has('passwordConfirm')) {
            return;
        }

        $plainPassword = $form['plainPassword']->getData();
        $passwordConfirm = $form['passwordConfirm']->getData();

        if ($plainPassword !== $passwordConfirm) {
            // TODO: implement the validation here
            $this->context->buildViolation($constraint->message)
                ->atPath('passwordConfirm')
                ->addViolation();
        }

    }
}

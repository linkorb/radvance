<?php
namespace Radvance\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class CodeConstraint extends Constraint
{
    public $message = 'The string "%string%" contains an illegal character: it can only contain small letters, numbers or - sign.';
}

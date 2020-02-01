<?php


namespace App\Form;


use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

class GoogleAuthenticatorType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);

        $builder->add("code", TextType::class, [
            "label" => "Verification code",
            "attr" => [
                "placeholder" => "XXXXXX",
            ]
        ]);
    }
}
<?php
/**
 * Created by PhpStorm.
 * User: beathan
 * Date: 28.07.2018
 * Time: 21:52
 */

namespace AppBundle\Form\Extension;


use Gregwar\CaptchaBundle\Type\CaptchaType;
use Sylius\Bundle\CoreBundle\Form\Type\ContactType;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;

class ContactTypeExtension extends AbstractTypeExtension
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        // Adding new fields works just like in the parent form type.
        $builder->add('captcha', CaptchaType::class, [
////            'reload' => true,
////            'as_url' => true,
            'max_front_lines' => 15,
            'max_behind_lines' => 15,
            'background_color'=>[255,212,93],
            'font'=>'../web/assets/shop/css/themes/anc/assets/fonts/captcha.ttf',
            'charset'=>'1234567890',
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getExtendedType(): string
    {
        return ContactType::class;
    }

}
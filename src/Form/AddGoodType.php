<?php

namespace App\Form;

use App\Entity\Goods;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AddGoodType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $date = new \DateTime();
        $date->modify('+1 day');

        $builder
            ->add('images',FileType::class , [

                'mapped' => false,

                'required' => false,
                'constraints' => [
                    new File([

                        'mimeTypes' =>[
                            'image/png',
                            'image/jpeg',

                        ],
                        'mimeTypesMessage' => 'Поддерживается только загрузка изображений!',
                    ])
                ],


            ])
            ->add('name'  )
            ->add('cost', IntegerType::class, [
                    'attr' => [
                        'min' => 100,
                        'max' => 100000,
                    ]
                ])
            ->add('last_date', DateType::class,[
                'widget' => 'single_text',
                'data'   => $date,
                'attr'   => [
                    'min' => $date->format('Y-m-d')
                ]
            ])

        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Goods::class,
        ]);
    }
}

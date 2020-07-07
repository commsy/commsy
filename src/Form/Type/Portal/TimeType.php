<?php
namespace App\Form\Type\Portal;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type as Types;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\DateType;

class TimeType extends AbstractType
{

    private $securityContext;

    public function __construct(Security $securityContext)
    {
        $this->securityContext = $securityContext;
    }
    /**
     * Builds the form.
     *
     * @param  FormBuilderInterface $builder The form builder
     * @param  array                $options The options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('showTime', Types\ChoiceType::class, [
                'label' => 'Show time',
                'expanded' => true,
                'choices'  => [
                    'Yes' => 1,
                    'No' => 0,
                ],
                'empty_data' => 0,
                'translation_domain' => 'portal',
            ])
            ->add('timeCycleNameGerman', Types\TextType::class, [
                'label' => 'Time cycle name',
                'attr' => array(
                    'placeholder' => 'de'
                ),
                'required' => false,
                'translation_domain' => 'portal',
            ])
            ->add('timeCycleNameEnglish', Types\TextType::class, [
                'label' => ' ',
                'attr' => array(
                    'placeholder' => 'en'
                ),
                'required' => false,
                'translation_domain' => 'portal',
            ])
            ->add('futureTimeCycles', Types\ChoiceType::class,[
                'label' => 'Future time cycles',
                'expanded' => false,
                'choices'  => [
                    '1' => 1,
                    '2' => 2,
                    '3' => 3,
                    '4' => 4,
                    '5' => 5,
                    '6' => 6,
                    '7' => 7,
                    '8' => 8,
                ],
                'required' => true,
            ])
            ->add('token', HiddenType::class, [
                'data' => "0",
            ])
            ->add('deleteLifecycle_0', SubmitType::class, [
                'label' => 'Delete time cycle %company%',
                'translation_domain' => 'portal',
                'label_translation_parameters' => [
                    '%company%' => 1,
                ],
            ])
            ->add('name_field', HiddenType::class, [
                'data' => "",
                'label' => "# 0",
            ])
            ->add('timeCycleNameGerman_0', Types\TextType::class, [
                'label' => ' ',
                'attr' => array(
                    'placeholder' => 'de',
                    'property_path' => false
                ),
                'required' => false,
                'translation_domain' => 'portal',
            ])
            ->add('timeCycleNameEnglish_0', Types\TextType::class, [
                'label' => ' ',
                'attr' => array(
                    'placeholder' => 'en',
                    'property_path' => false,
                ),
                'required' => false,
                'translation_domain' => 'portal',
            ])
            ->add('timeCycleFrom_0', DateType::class, [
                'format' => 'dd.MM.yyyy',
                'attr' => array(
                    'data-uk-datepicker' => '{format:\'DD.MM.YYYY\'}',
                ),
                'widget' => 'single_text',
                'placeholder' => 'dd.MM.yyyy',
                'label' =>'Time cycle from',
                'input'  => 'datetime_immutable',
                'required' => false,
                'translation_domain' => 'portal',
            ])
            ->add('timeCycleTo_0', DateType::class, [
                'format' => 'dd.MM.yyyy',
                'attr' => array(
                    'data-uk-datepicker' => '{format:\'DD.MM.YYYY\'}',
                ),
                'widget' => 'single_text',
                'placeholder' => 'dd.MM.yyyy',
                'label' =>'Time cycle to',
                'input'  => 'datetime_immutable',
                'required' => false,
                'translation_domain' => 'portal',
                'help' => 'Time help',
            ])
            ->add('add_lifecycle', SubmitType::class, [
                'label' => 'Add life cycle',
                'translation_domain' => 'portal',
            ])
            ->add('save', Types\SubmitType::class, [
                'label' => 'save',
                'translation_domain' => 'form',
            ]);

        $factory = $builder->getFormFactory();

        // grab the user, do a quick sanity check that one exists
        $user = $this->securityContext->getToken()->getUser();

        $builder->addEventListener(
            FormEvents::PRE_SUBMIT,
            function(FormEvent $event) use ($user, $factory) {
                $form = $event->getForm();
                $data = $event->getData();

                $sth = "";
                $deletionIndex = null;
                foreach($data as $key => $val)
                {
                    if(strpos($key, 'token') !== false){
                        $sth = $val;
                    }
                    if(strpos($key, 'index_of_lifecycle_deletion') !== false){
                        $deletionIndex = $val;
                    }
                    if(strpos($key, 'deleteLifecycle_') !== false){
                        $deletionIndex = substr($key, strpos($key, "_") + 1);
                    }
                }

                $saveClicked = isset($data['send']);
                $addClicked = isset($data['add_lifecycle']);
                $sth_string = $sth;

                if(!$saveClicked){
                    $sth_array = explode(',',$sth);
                    $last_val = end($sth_array);

                    if($addClicked) {
                        if(!is_numeric($last_val)){
                            $sth_array = array('0');
                        }else{
                            array_push($sth_array, strval($last_val + 1));
                        }
                        $sth_string = implode(",", $sth_array);

                    }

                    if($deletionIndex or $deletionIndex == 0){
                        $removeGerman = 'timeCycleNameGerman_'.$deletionIndex;
                        $removeEnglish = 'timeCycleNameEnglish_'.$deletionIndex;
                        $form->remove($removeGerman);
                        $form->remove($removeEnglish);

                        if (($key = array_search($deletionIndex, $sth_array)) !== false) {
                            unset($sth_array[$key]);
                        }

                        $sth_string = implode(",", $sth_array);

                    }

                    $form->remove('add_lifecycle');
                    $form->remove('save');

                    $form->get('token')->setData($sth_string);
                    $data['token'] = $sth_string;
                    $event->setData($data);
                        foreach($sth_array as $i){
                            $i = intval($i);
                            $form
                                ->add('deleteLifecycle_'.$i, SubmitType::class, [
                                    'label' => 'Delete time cycle %company%',
                                    'translation_domain' => 'portal',
                                    'label_translation_parameters' => [
                                        '%company%' => $i+1,
                                    ],
                                ])
                                ->add('name_field', HiddenType::class, [
                                    'data' => "",
                                    'label' => "#".$i,
                                ])
                                ->add('timeCycleNameGerman_'.$i, Types\TextType::class, [
                                    'label' => ' ',
                                    'attr' => array(
                                        'placeholder' => 'de',
                                        'property_path' => false
                                    ),
                                    'required' => false,
                                    'translation_domain' => 'portal',
                                ])
                                ->add('timeCycleNameEnglish_'.$i, Types\TextType::class, [
                                    'label' => ' ',
                                    'attr' => array(
                                        'placeholder' => 'en',
                                        'property_path' => false,
                                    ),
                                    'required' => false,
                                    'translation_domain' => 'portal',
                                ])
                                ->add('timeCycleFrom_'.$i, DateType::class, [
                                    'format' => 'dd.MM.yyyy',
                                    'attr' => array(
                                        'data-uk-datepicker' => '{format:\'DD.MM.YYYY\'}',
                                    ),
                                    'widget' => 'single_text',
                                    'placeholder' => 'dd.MM.yyyy',
                                    'label' =>'Time cycle from',
                                    'input'  => 'datetime_immutable',
                                    'required' => false,
                                    'translation_domain' => 'portal',
                                ])
                                ->add('timeCycleTo_'.$i, DateType::class, [
                                    'format' => 'dd.MM.yyyy',
                                    'attr' => array(
                                        'data-uk-datepicker' => '{format:\'DD.MM.YYYY\'}',
                                    ),
                                    'placeholder' => 'dd.MM.yyyy',
                                    'widget' => 'single_text',
                                    'label' =>'Time cycle to',
                                    'input'  => 'datetime_immutable',
                                    'required' => false,
                                    'translation_domain' => 'portal',
                                    'help' => 'Time help',
                                ]);
                        }
                    $form
                        ->add('add_lifecycle', SubmitType::class, [
                            'label' => 'Add life cycle',
                            'translation_domain' => 'portal',
                        ])
                        ->add('save', Types\SubmitType::class, [
                            'label' => 'save',
                            'translation_domain' => 'form',
                        ]);
                    }
                }
        );


    }

    public function setDefaultOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => null
        ));
    }

    /**
     * Configures the options for this type.
     *
     * @param  OptionsResolver $resolver The resolver for the options
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => null,
            'translation_domain' => 'portal',
        ]);
    }
}

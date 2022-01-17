<?php


namespace App\Form\Type\Portal;

use App\Entity\IdP;
use App\Form\DataTransformer\IdpTransformer;
use Doctrine\ORM\EntityRepository;
use FOS\CKEditorBundle\Form\Type\CKEditorType;
use Nette\Utils\Callback;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AuthShibbolethType extends AbstractType
{

    private $transformer;

    public function __construct(IdpTransformer $transformer)
    {
        $this->transformer = $transformer;
    }

    /**
     * Builds the form.
     *
     * @param  FormBuilderInterface $builder The form builder
     * @param  array                $options The options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $idpOptions = $options['idps_options_array'];
        $builder
            ->add('typeChoice', ChoiceType::class, [
                'choices'  => [
                    'CommSy' => 'commsy',
                    'LDAP' => 'ldap',
                    'Shibboleth' => 'shib',
                    'Guest' => 'guest',
                ],
                'required' => true,
                'label' => 'Source',
                'mapped' => false,
                'data' => 'shib',
            ])
            ->add('type', SubmitType::class, [
                'attr' => [
                    'class' => 'uk-button-primary',
                    'formnovalidate' => '',
                ],
                'label' => 'Select',
                'validation_groups' => false,
            ])
            ->add('title', TextType::class, [
                'label' => 'Title',
                'required' => true,
            ])
            ->add('description', CKEditorType::class, [
                'label' => 'Description',
                'required' => false,
                'config_name' => 'cs_mail_config',
            ])
            ->add('default', CheckboxType::class, [
                'label' => 'Default',
                'required' => false,
                'help' => 'Pre select this authentication in the login box (deselect all others)'
            ])
            ->add('enabled', CheckboxType::class, [
                'label' => 'Available',
                'required' => false,
            ])
            ->add('loginUrl', UrlType::class, [
                'label' => 'Login URL',
                'help' => 'https://sp.example.org/Shibboleth.sso/Login',
            ])
            ->add('logoutUrl', UrlType::class, [
                'label' => 'Logout URL',
                'help' => 'https://sp.example.org/Shibboleth.sso/Logout',
                'required' => false,
            ])
            ->add('passwordResetURL', UrlType::class, [
                'label' => 'Password reset URL',
                'required' => false,
            ])
            ->add('mappingUsername', TextType::class, [
                'label' => 'Mapping: Username',
                'help' => 'eppn',
            ])
            ->add('mappingFirstname', TextType::class, [
                'label' => 'Mapping: Firstname',
                'help' => 'givenName',
            ])
            ->add('mappingLastname', TextType::class, [
                'label' => 'Mapping: Lastname',
                'help' => 'sn',
            ])
            ->add('mappingEmail', TextType::class, [
                'label' => 'Mapping: Email',
                'help' => 'mail',
            ])
            ->add('createRoom', CheckboxType::class, [
                'label' => 'Users may create rooms',
                'required' => false,
            ])
            ->add('idps', ChoiceType::class, [
                'choices' => $idpOptions,
                'label' => 'Available idps',
            ])
            ->add('save', SubmitType::class, [
                'label' => 'Save',
            ])
        ;

        $builder->get('idps')
            ->addModelTransformer($this->transformer);
    }

    /**
     * Configures the options for this type.
     *
     * @param OptionsResolver $resolver The resolver for the options
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver
            ->setDefaults([
            'translation_domain' => 'portal',
        ])
            ->setRequired('idps_options_array')
        ;
    }
}
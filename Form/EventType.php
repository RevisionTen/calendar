<?php

namespace RevisionTen\Calendar\Form;

use RevisionTen\Calendar\Entity\Event;
use RevisionTen\CMS\Form\Types\UploadType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

class EventType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('title', TextType::class, [
            'label' => 'admin.label.title',
            'translation_domain' => 'cms',
            'constraints' => new NotBlank(),
        ]);

        $builder->add('language', ChoiceType::class, [
            'label' => 'page.label.language',
            'translation_domain' => 'cms',
            'choices' => $options['page_languages'] ?: [
                'English' => 'en',
                'German' => 'de',
            ],
            'choice_translation_domain' => 'messages',
            'placeholder' => 'page.placeholder.language',
            'constraints' => new NotBlank(),
            'attr' => [
                'class' => 'custom-select',
            ],
        ]);

        $builder->add('description', TextareaType::class, [
            'label' => 'calendar.label.description',
            'translation_domain' => 'cms',
            'constraints' => new NotBlank(),
        ]);

        $builder->add('artist', TextType::class, [
            'label' => 'calendar.label.artist',
            'translation_domain' => 'cms',
            'required' => false,
        ]);

        $builder->add('organizer', TextType::class, [
            'label' => 'calendar.label.organizer',
            'translation_domain' => 'cms',
            'required' => false,
        ]);

        $builder->add('salesStatus', ChoiceType::class, [
            'label' => 'calendar.label.salesStatus',
            'translation_domain' => 'cms',
            'constraints' => new NotBlank(),
            'choices' => [
                'calendar.salesStates.'.Event::STATE_SALE => Event::STATE_SALE,
                'calendar.salesStates.'.Event::STATE_PRE_SALE => Event::STATE_PRE_SALE,
                'calendar.salesStates.'.Event::STATE_SOLD => Event::STATE_SOLD,
                'calendar.salesStates.'.Event::STATE_POSTPONED => Event::STATE_POSTPONED,
                'calendar.salesStates.'.Event::STATE_CANCELLED => Event::STATE_CANCELLED,
            ],
        ]);

        $builder->add('image', UploadType::class, [
            'label' => 'calendar.label.image',
            'translation_domain' => 'cms',
            'required' => false,
            'show_file_picker' => true,
            'file_with_meta_data' => true,
        ]);

        $builder->add('save', SubmitType::class, [
            'label' => 'admin.btn.save',
            'translation_domain' => 'cms',
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'page_websites' => null,
            'page_languages' => null,
        ]);
    }
}

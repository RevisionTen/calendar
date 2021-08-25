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
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
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

        $builder->add('bookingInfo', TextareaType::class, [
            'label' => 'calendar.label.bookingInfo',
            'translation_domain' => 'cms',
            'required' => false,
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

        $genresOptions = [
            'label' => 'calendar.label.genres',
            'translation_domain' => 'cms',
            'required' => false,
            'multiple' => true,
            'choices' => [],
            'attr' => [
                'data-widget' => 'select2',
                'data-select2-tags' => 'true',
            ],
        ];
        $builder->add('genres', ChoiceType::class, $genresOptions);
        $builder->addEventListener(FormEvents::PRE_SUBMIT, static function (FormEvent $event) use ($genresOptions) {
            self::choiceAdder($event, 'genres', $genresOptions);
        });
        $builder->addEventListener(FormEvents::PRE_SET_DATA, static function (FormEvent $event) use ($genresOptions) {
            self::choiceAdder($event, 'genres', $genresOptions);
        });

        $keywordsOptions = [
            'label' => 'calendar.label.keywords',
            'translation_domain' => 'cms',
            'required' => false,
            'multiple' => true,
            'choices' => [],
            'attr' => [
                'data-widget' => 'select2',
                'data-select2-tags' => 'true',
            ],
        ];
        $builder->add('keywords', ChoiceType::class, $keywordsOptions);
        $builder->addEventListener(FormEvents::PRE_SUBMIT, static function (FormEvent $event) use ($keywordsOptions) {
            self::choiceAdder($event, 'keywords', $keywordsOptions);
        });
        $builder->addEventListener(FormEvents::PRE_SET_DATA, static function (FormEvent $event) use ($keywordsOptions) {
            self::choiceAdder($event, 'keywords', $keywordsOptions);
        });

        $partnersOptions = [
            'label' => 'calendar.label.partners',
            'translation_domain' => 'cms',
            'required' => false,
            'multiple' => true,
            'choices' => [],
            'attr' => [
                'data-widget' => 'select2',
                'data-select2-tags' => 'true',
            ],
        ];
        $builder->add('partners', ChoiceType::class, $partnersOptions);
        $builder->addEventListener(FormEvents::PRE_SUBMIT, static function (FormEvent $event) use ($partnersOptions) {
            self::choiceAdder($event, 'partners', $partnersOptions);
        });
        $builder->addEventListener(FormEvents::PRE_SET_DATA, static function (FormEvent $event) use ($partnersOptions) {
            self::choiceAdder($event, 'partners', $partnersOptions);
        });

        $builder->add('salesStatus', ChoiceType::class, [
            'label' => 'calendar.label.salesStatus',
            'translation_domain' => 'cms',
            'constraints' => new NotBlank(),
            'expanded' => true,
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

    public static function choiceAdder(FormEvent $event, string $fieldName, array $options): void
    {
        $form = $event->getForm();
        $data = $event->getData()[$fieldName] ?? null;

        if (!empty($data)) {
            $choices = $options['choices'];
            $choices = array_combine($choices, $choices);

            if (is_array($data)) {
                foreach($data as $choice) {
                    $choices[$choice] = $choice;
                }
            } else {
                $choices[$data] = $data;
            }
            $options['choices'] = $choices;
            $form->add($fieldName, ChoiceType::class, $options);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'page_websites' => null,
            'page_languages' => null,
        ]);
    }
}

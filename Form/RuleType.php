<?php

namespace RevisionTen\Calendar\Form;

use RevisionTen\CMS\Form\Types\UploadType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

class RuleType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('ruleTitle', TextType::class, [
            'label' => 'calendar.label.ruleTitle',
            'translation_domain' => 'cms',
            'constraints' => new NotBlank(),
        ]);

        $builder->add('participants', NumberType::class, [
            'label' => 'calendar.label.participants',
            'translation_domain' => 'cms',
            'required' => false,
        ]);

        $builder->add('title', TextType::class, [
            'label' => 'admin.label.title',
            'translation_domain' => 'cms',
            'required' => false,
        ]);

        $builder->add('description', TextareaType::class, [
            'label' => 'calendar.label.description',
            'translation_domain' => 'cms',
            'required' => false,
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

        $builder->add('startDate', DateTimeType::class, [
            'label' => 'calendar.label.startDate',
            'translation_domain' => 'cms',
            'constraints' => new NotBlank(),
            'input' => 'timestamp',
            'date_widget' => 'single_text',
            'time_widget' => 'single_text',
        ]);

        $builder->add('endDate', DateTimeType::class, [
            'label' => 'calendar.label.endDate',
            'translation_domain' => 'cms',
            'constraints' => new NotBlank(),
            'input' => 'timestamp',
            'date_widget' => 'single_text',
            'time_widget' => 'single_text',
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

        $builder->add('frequency', ChoiceType::class, [
            'label' => 'calendar.label.frequency',
            'translation_domain' => 'cms',
            'required' => false,
            'placeholder' => 'calendar.value.frequencyNone',
            'choices' => [
                'calendar.value.hourly' => 'hourly',
                'calendar.value.daily' => 'daily',
                'calendar.value.weekly' => 'weekly',
                'calendar.value.monthly' => 'monthly',
            ],
            'attr' => [
                'class' => 'custom-select',
                'data-condition' => true,
            ],
        ]);

        $formModifier = static function (FormInterface $form = null, $frequency = null) {
            if ($form) {
                if ($frequency) {
                    $repeatEndDateType = DateType::class;
                    $repeatEndDateRequired = false;

                    switch ($frequency) {
                        case 'hourly':
                            $repeatEndDateType = DateTimeType::class;
                            $repeatEndDateRequired = true;
                            $form->add('frequencyHours', NumberType::class, [
                                'label' => 'calendar.label.frequencyHours',
                                'translation_domain' => 'cms',
                            ]);
                            $form->remove('frequencyDays');
                            $form->remove('frequencyWeeks');
                            $form->remove('frequencyWeeksOn');
                            $form->remove('frequencyMonths');
                            $form->remove('frequencyMonthsOn');
                            break;
                        case 'daily':
                            $form->add('frequencyDays', NumberType::class, [
                                'label' => 'calendar.label.frequencyDays',
                                'translation_domain' => 'cms',
                            ]);
                            $form->remove('frequencyHours');
                            $form->remove('frequencyWeeks');
                            $form->remove('frequencyWeeksOn');
                            $form->remove('frequencyMonths');
                            $form->remove('frequencyMonthsOn');
                            break;
                        case 'weekly':
                            $form->add('frequencyWeeks', NumberType::class, [
                                'label' => 'calendar.label.frequencyWeeks',
                                'translation_domain' => 'cms',
                            ]);
                            $form->add('frequencyWeeksOn', ChoiceType::class, [
                                'label' => 'calendar.label.frequencyWeeksOn',
                                'translation_domain' => 'cms',
                                'multiple' => false,
                                'expanded' => true,
                                'choices' => [
                                    'calendar.value.monday' => 'monday',
                                    'calendar.value.tuesday' => 'tuesday',
                                    'calendar.value.wednesday' => 'wednesday',
                                    'calendar.value.thursday' => 'thursday',
                                    'calendar.value.friday' => 'friday',
                                    'calendar.value.saturday' => 'saturday',
                                    'calendar.value.sunday' => 'sunday',
                                ],
                            ]);
                            $form->remove('frequencyHours');
                            $form->remove('frequencyDays');
                            $form->remove('frequencyMonths');
                            $form->remove('frequencyMonthsOn');
                            break;
                        case 'monthly':
                            $form->add('frequencyMonths', NumberType::class, [
                                'label' => 'calendar.label.frequencyMonths',
                                'translation_domain' => 'cms',
                            ]);
                            $form->add('frequencyMonthsOn', ChoiceType::class, [
                                'label' => 'calendar.label.frequencyMonthsOn',
                                'translation_domain' => 'cms',
                                'multiple' => false,
                                'expanded' => false,
                                'choices' => [
                                    '01' => '1',
                                    '02' => '2',
                                    '03' => '3',
                                    '04' => '4',
                                    '05' => '5',
                                    '06' => '6',
                                    '07' => '7',
                                    '08' => '8',
                                    '09' => '9',
                                    '10' => '10',
                                    '11' => '11',
                                    '12' => '12',
                                    '13' => '13',
                                    '14' => '14',
                                    '15' => '15',
                                    '16' => '16',
                                    '17' => '17',
                                    '18' => '18',
                                    '19' => '19',
                                    '20' => '20',
                                    '21' => '21',
                                    '22' => '22',
                                    '23' => '23',
                                    '24' => '24',
                                    '25' => '25',
                                    '26' => '26',
                                    '27' => '27',
                                    '28' => '28',
                                    '29' => '29',
                                    '30' => '30',
                                    '31' => '31',
                                ],
                            ]);
                            $form->remove('frequencyHours');
                            $form->remove('frequencyDays');
                            $form->remove('frequencyWeeks');
                            $form->remove('frequencyWeeksOn');
                            break;
                    }

                    $form->add('repeatEndDate', $repeatEndDateType, [
                        'label' => 'calendar.label.repeatEndDate',
                        'translation_domain' => 'cms',
                        'input' => 'timestamp',
                        'widget' => 'single_text',
                        'required' => $repeatEndDateRequired,
                        'help' => $repeatEndDateRequired ? null : 'calendar.help.repeatEndDate',
                        'constraints' => $repeatEndDateRequired ? new NotBlank() : [],
                    ]);
                } else {
                    $form->remove('repeatEndDate');
                    $form->remove('frequencyHours');
                    $form->remove('frequencyDays');
                    $form->remove('frequencyWeeks');
                    $form->remove('frequencyWeeksOn');
                    $form->remove('frequencyMonths');
                    $form->remove('frequencyMonthsOn');
                }
            }
        };

        $builder->addEventListener(FormEvents::PRE_SET_DATA, static function (FormEvent $event) use ($formModifier) {
            $data = $event->getData();
            $frequency = $data['frequency'] ?? null;
            $formModifier($event->getForm(), $frequency);
        });

        $builder->get('frequency')->addEventListener(FormEvents::POST_SUBMIT, static function (FormEvent $event) use ($formModifier) {
            $frequency = $event->getForm()->getData();
            $formModifier($event->getForm()->getParent(), $frequency);
        });
    }
}

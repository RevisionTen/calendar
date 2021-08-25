<?php

namespace RevisionTen\Calendar\Form;

use RevisionTen\Calendar\Entity\Event;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;

class DeviationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('participants', NumberType::class, [
            'label' => 'calendar.label.participants',
            'translation_domain' => 'cms',
            'required' => false,
        ]);

        $builder->add('salesStatus', ChoiceType::class, [
            'label' => 'calendar.label.salesStatus',
            'translation_domain' => 'cms',
            'required' => false,
            'expanded' => true,
            'choices' => [
                'calendar.salesStates.'.Event::STATE_SALE => Event::STATE_SALE,
                'calendar.salesStates.'.Event::STATE_PRE_SALE => Event::STATE_PRE_SALE,
                'calendar.salesStates.'.Event::STATE_SOLD => Event::STATE_SOLD,
                'calendar.salesStates.'.Event::STATE_POSTPONED => Event::STATE_POSTPONED,
                'calendar.salesStates.'.Event::STATE_CANCELLED => Event::STATE_CANCELLED,
            ],
        ]);

        $builder->add('startDate', DateTimeType::class, [
            'label' => 'calendar.label.startDate',
            'translation_domain' => 'cms',
            'input' => 'timestamp',
            'date_widget' => 'single_text',
            'time_widget' => 'single_text',
            'required' => false,
        ]);

        $builder->add('endDate', DateTimeType::class, [
            'label' => 'calendar.label.endDate',
            'translation_domain' => 'cms',
            'input' => 'timestamp',
            'date_widget' => 'single_text',
            'time_widget' => 'single_text',
            'required' => false,
        ]);

        $builder->add('save', SubmitType::class, [
            'label' => 'admin.btn.save',
            'translation_domain' => 'cms',
        ]);
    }
}

<?php

declare(strict_types=1);

namespace RevisionTen\Calendar\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Ramsey\Uuid\Uuid;
use RevisionTen\Calendar\Command\EventCreateCommand;
use RevisionTen\Calendar\Command\EventDeviationCreateCommand;
use RevisionTen\Calendar\Command\EventDeviationDeleteCommand;
use RevisionTen\Calendar\Command\EventDeviationEditCommand;
use RevisionTen\Calendar\Command\EventRuleCreateCommand;
use RevisionTen\Calendar\Command\EventDeleteCommand;
use RevisionTen\Calendar\Command\EventEditCommand;
use RevisionTen\Calendar\Command\EventRuleDeleteCommand;
use RevisionTen\Calendar\Command\EventRuleEditCommand;
use RevisionTen\Calendar\Entity\Event;
use RevisionTen\Calendar\Entity\EventRead;
use RevisionTen\CMS\Model\UserRead;
use RevisionTen\CQRS\Exception\InterfaceException;
use RevisionTen\CQRS\Services\AggregateFactory;
use RevisionTen\CQRS\Services\CommandBus;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @Route("/admin")
 */
class AdminController extends AbstractController
{
    protected CommandBus $commandBus;

    protected AggregateFactory $aggregateFactory;

    protected EntityManagerInterface $entityManager;

    protected TranslatorInterface $translator;

    public function __construct(CommandBus $commandBus, AggregateFactory $aggregateFactory, EntityManagerInterface $entityManager, TranslatorInterface $translator)
    {
        $this->commandBus = $commandBus;
        $this->aggregateFactory = $aggregateFactory;
        $this->entityManager = $entityManager;
        $this->translator = $translator;
    }

    /**
     * @Route("/calendar/create", name="calendar_event_create")
     *
     * @param Request $request
     *
     * @return Response
     * @throws InterfaceException
     */
    public function createEvent(Request $request): Response
    {
        $this->denyAccessUnlessGranted('create_generic');

        /**
         * @var UserRead $user
         */
        $user = $this->getUser();
        $config = $this->getParameter('cms');
        $currentWebsite = (int) ($request->get('currentWebsite') ?? 1);

        $event = [
            'website' => $currentWebsite,
            'language' => $request->getLocale(),
        ];

        $calendarConfig = $this->getParameter('calendar');
        $formClass = $calendarConfig['event_form_type'];
        $template = $calendarConfig['event_form_template'];

        $form = $this->createForm($formClass, $event, [
            'page_languages' => $config['page_languages'] ?? null,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $payload = $form->getData();

            $eventUuid = Uuid::uuid1()->toString();
            $queueEvents = false;

            $success = $this->commandBus->execute(EventCreateCommand::class, $eventUuid, $payload, $user->getId(), $queueEvents);
            if ($success) {
                $this->addFlash(
                    'success',
                    $this->translator->trans('Event created', [], 'cms')
                );

                return $this->redirectToRoute('calendar_event_edit', [
                    'uuid' => $eventUuid,
                ]);
            }
        }

        return $this->render($template, [
            'title' => $this->translator->trans('calendar.label.createEvent', [], 'cms'),
            'form' => $form->createView(),
            'edit' => false,
        ]);
    }

    /**
     * @Route("/calendar/edit", name="calendar_event_edit")
     *
     * @param Request $request
     *
     * @return Response
     * @throws InterfaceException
     */
    public function editEvent(Request $request): Response
    {
        $this->denyAccessUnlessGranted('edit_generic');

        $eventUuid = $request->get('uuid');
        if (empty($eventUuid)) {
            $id = (int) $request->get('id');
            /**
             * @var EventRead $eventRead
             */
            $eventRead = $this->entityManager->getRepository(EventRead::class)->find($id);
            if (null === $eventRead) {
                throw new NotFoundHttpException();
            }
            $eventUuid = $eventRead->getUuid();
        }

        if (empty($eventUuid)) {
            throw new NotFoundHttpException();
        }
        /**
         * @var Event $event
         */
        $event = $this->aggregateFactory->build($eventUuid, Event::class);

        /**
         * @var UserRead $user
         */
        $user = $this->getUser();
        $config = $this->getParameter('cms');

        $data = [
            'website' => $event->website,
            'language' => $event->language,
            'title' => $event->title,
            'artist' => $event->artist,
            'organizer' => $event->organizer,
            'description' => $event->description,
            'bookingInfo' => $event->bookingInfo,
            'image' => $event->image,
            'salesStatus' => $event->salesStatus,
            'keywords' => $event->keywords,
            'genres' => $event->genres,
            'partners' => $event->partners,
            'venue' => $event->venue,
            'extra' => $event->extra,
        ];

        $calendarConfig = $this->getParameter('calendar');
        $formClass = $calendarConfig['event_form_type'];
        $template = $calendarConfig['event_form_template'];

        $form = $this->createForm($formClass, $data, [
            'page_languages' => $config['page_languages'] ?? null,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $payload = $form->getData();

            $queueEvents = false;
            $success = $this->commandBus->execute(EventEditCommand::class, $eventUuid, $payload, $user->getId(), $queueEvents);
            if ($success) {
                $this->addFlash(
                    'success',
                    $this->translator->trans('Event edited', [], 'cms')
                );
            }
        }

        return $this->render($template, [
            'title' => $this->translator->trans('calendar.label.editEvent', [], 'cms'),
            'form' => $form->createView(),
            'edit' => true,
            'event' => $event,
        ]);
    }

    /**
     * @Route("/calendar/delete", name="calendar_event_delete")
     *
     * @param Request $request
     *
     * @return Response
     * @throws InterfaceException
     */
    public function deleteEvent(Request $request): Response
    {
        $this->denyAccessUnlessGranted('delete_generic');

        $id = (int) $request->get('id');
        /**
         * @var EventRead $eventRead
         */
        $eventRead = $this->entityManager->getRepository(EventRead::class)->find($id);
        if (null === $eventRead) {
            throw new NotFoundHttpException();
        }
        $eventUuid = $eventRead->getUuid();

        /**
         * @var UserRead $user
         */
        $user = $this->getUser();

        $this->commandBus->execute(EventDeleteCommand::class, $eventUuid, [], $user->getId(), false);

        return $this->redirectToRoute('cms_list_entity', [
            'entity' => 'EventRead',
        ]);
    }

    /**
     * @Route("/calendar/rule/{eventUuid}/create", name="calendar_rule_create")
     *
     * @param Request $request
     * @param string $eventUuid
     *
     * @return Response
     * @throws InterfaceException
     */
    public function createRule(Request $request, string $eventUuid): Response
    {
        $this->denyAccessUnlessGranted('edit_generic');

        /**
         * @var Event $event
         */
        $event = $this->aggregateFactory->build($eventUuid, Event::class);

        /**
         * @var UserRead $user
         */
        $user = $this->getUser();

        $data = [
            'startDate' => time(),
            'endDate' => time(),
        ];

        $calendarConfig = $this->getParameter('calendar');
        $formClass = $calendarConfig['rule_form_type'];
        $template = $calendarConfig['rule_form_template'];

        $ignore_validation = $request->get('ignore_validation');
        $form = $this->createForm($formClass, $data, [
            'validation_groups' => $ignore_validation ? false : null,
        ]);
        $form->handleRequest($request);

        if (!$ignore_validation && $form->isSubmitted() && $form->isValid()) {
            $payload = $form->getData();

            $queueEvents = false;
            $success = $this->commandBus->execute(EventRuleCreateCommand::class, $event->uuid, $payload, $user->getId(), $queueEvents);
            if ($success) {
                $this->addFlash(
                    'success',
                    $this->translator->trans('Rule created', [], 'cms')
                );

                return $this->redirectToRoute('calendar_event_edit', [
                    'uuid' => $event->uuid,
                ]);
            }
        }

        return $this->render($template, [
            'title' => $this->translator->trans('calendar.label.addRule', [], 'cms'),
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/calendar/rule/{eventUuid}/edit/{ruleUuid}", name="calendar_rule_edit")
     *
     * @param Request $request
     * @param string $eventUuid
     * @param string $ruleUuid
     *
     * @return Response
     * @throws InterfaceException
     */
    public function editRule(Request $request, string $eventUuid, string $ruleUuid): Response
    {
        $this->denyAccessUnlessGranted('edit_generic');

        /**
         * @var Event $event
         */
        $event = $this->aggregateFactory->build($eventUuid, Event::class);
        $rule = $event->getRule($ruleUuid);

        /**
         * @var UserRead $user
         */
        $user = $this->getUser();

        $data = [
            'uuid' => $rule->uuid,
            'ruleTitle' => $rule->ruleTitle,
            'description' => $rule->description,
            'bookingInfo' => $rule->bookingInfo,
            'venue' => $rule->venue,
            'extra' => $rule->extra,
            'artist' => $rule->artist,
            'organizer' => $rule->organizer,
            'title' => $rule->title,
            'participants' => $rule->participants,
            'startDate' => $rule->startDate->getTimestamp(),
            'endDate' => $rule->endDate->getTimestamp(),
            'repeatEndDate' => $rule->repeatEndDate ? $rule->repeatEndDate->getTimestamp() : null,
            'frequency' => $rule->frequency,
            'frequencyDays' => $rule->frequencyDays,
            'frequencyMonths' => $rule->frequencyMonths,
            'frequencyMonthsOn' => $rule->frequencyMonthsOn,
            'frequencyWeeks' => $rule->frequencyWeeks,
            'frequencyWeeksOn' => $rule->frequencyWeeksOn,
        ];

        $calendarConfig = $this->getParameter('calendar');
        $formClass = $calendarConfig['rule_form_type'];
        $template = $calendarConfig['rule_form_template'];

        $ignore_validation = $request->get('ignore_validation');
        $form = $this->createForm($formClass, $data, [
            'validation_groups' => $ignore_validation ? false : null,
        ]);
        $form->handleRequest($request);

        if (!$ignore_validation && $form->isSubmitted() && $form->isValid()) {
            $payload = $form->getData();

            $queueEvents = false;
            $success = $this->commandBus->execute(EventRuleEditCommand::class, $event->uuid, $payload, $user->getId(), $queueEvents);
            if ($success) {
                $this->addFlash(
                    'success',
                    $this->translator->trans('Rule edited', [], 'cms')
                );

                return $this->redirectToRoute('calendar_event_edit', [
                    'uuid' => $event->uuid,
                ]);
            }
        }

        return $this->render($template, [
            'title' => $this->translator->trans('calendar.label.editRule', [], 'cms'),
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/calendar/rule/{eventUuid}/delete/{ruleUuid}", name="calendar_rule_delete")
     *
     * @param string $eventUuid
     * @param string $ruleUuid
     *
     * @return RedirectResponse
     * @throws InterfaceException
     */
    public function deleteRule(string $eventUuid, string $ruleUuid): RedirectResponse
    {
        $this->denyAccessUnlessGranted('edit_generic');

        /**
         * @var Event $event
         */
        $event = $this->aggregateFactory->build($eventUuid, Event::class);
        $rule = $event->getRule($ruleUuid);

        /**
         * @var UserRead $user
         */
        $user = $this->getUser();

        $payload = [
            'uuid' => $rule->uuid,
        ];

        $queueEvents = false;
        $success = $this->commandBus->execute(EventRuleDeleteCommand::class, $event->uuid, $payload, $user->getId(), $queueEvents);
        if ($success) {
            $this->addFlash(
                'success',
                $this->translator->trans('Rule deleted', [], 'cms')
            );
        }

        return $this->redirectToRoute('calendar_event_edit', [
            'uuid' => $event->uuid,
        ]);
    }

    /**
     * @Route("/calendar/deviation/{eventUuid}/create/{start}/{end}", name="calendar_deviation_create")
     *
     * @param Request $request
     * @param string $eventUuid
     *
     * @return Response
     * @throws InterfaceException
     */
    public function createDeviation(Request $request, string $eventUuid, int $start, int $end): Response
    {
        $this->denyAccessUnlessGranted('edit_generic');

        /**
         * @var Event $event
         */
        $event = $this->aggregateFactory->build($eventUuid, Event::class);

        /**
         * @var UserRead $user
         */
        $user = $this->getUser();

        $data = [
            'deviationStartDate' => $start,
            'deviationEndDate' => $end,
        ];

        $calendarConfig = $this->getParameter('calendar');
        $formClass = $calendarConfig['deviation_form_type'];
        $template = $calendarConfig['deviation_form_template'];

        $ignore_validation = $request->get('ignore_validation');
        $form = $this->createForm($formClass, $data, [
            'validation_groups' => $ignore_validation ? false : null,
        ]);
        $form->handleRequest($request);

        if (!$ignore_validation && $form->isSubmitted() && $form->isValid()) {
            $payload = $form->getData();

            $queueEvents = false;
            $success = $this->commandBus->execute(EventDeviationCreateCommand::class, $event->uuid, $payload, $user->getId(), $queueEvents);
            if ($success) {
                $this->addFlash(
                    'success',
                    $this->translator->trans('Deviation created', [], 'cms')
                );

                return $this->redirectToRoute('calendar_event_edit', [
                    'uuid' => $event->uuid,
                ]);
            }
        }

        return $this->render($template, [
            'title' => $this->translator->trans('calendar.label.addDeviation', [], 'cms'),
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/calendar/deviation/{eventUuid}/edit/{deviationUuid}", name="calendar_deviation_edit")
     *
     * @param Request $request
     * @param string $eventUuid
     * @param string $deviationUuid
     *
     * @return Response
     * @throws InterfaceException
     */
    public function editDeviation(Request $request, string $eventUuid, string $deviationUuid): Response
    {
        $this->denyAccessUnlessGranted('edit_generic');

        /**
         * @var Event $event
         */
        $event = $this->aggregateFactory->build($eventUuid, Event::class);
        $deviation = $event->getDeviation($deviationUuid);

        /**
         * @var UserRead $user
         */
        $user = $this->getUser();

        $data = [
            'uuid' => $deviation->uuid,
            'venue' => $deviation->venue,
            'participants' => $deviation->participants,
            'salesStatus' => $deviation->salesStatus,
            'extra' => $deviation->extra,
            'startDate' => $deviation->startDate ? $deviation->startDate->getTimestamp() : null,
            'endDate' => $deviation->endDate ? $deviation->endDate->getTimestamp() : null,
        ];

        $calendarConfig = $this->getParameter('calendar');
        $formClass = $calendarConfig['deviation_form_type'];
        $template = $calendarConfig['deviation_form_template'];

        $ignore_validation = $request->get('ignore_validation');
        $form = $this->createForm($formClass, $data, [
            'validation_groups' => $ignore_validation ? false : null,
        ]);
        $form->handleRequest($request);

        if (!$ignore_validation && $form->isSubmitted() && $form->isValid()) {
            $payload = $form->getData();

            $queueEvents = false;
            $success = $this->commandBus->execute(EventDeviationEditCommand::class, $event->uuid, $payload, $user->getId(), $queueEvents);
            if ($success) {
                $this->addFlash(
                    'success',
                    $this->translator->trans('Deviation edited', [], 'cms')
                );

                return $this->redirectToRoute('calendar_event_edit', [
                    'uuid' => $event->uuid,
                ]);
            }
        }

        return $this->render($template, [
            'title' => $this->translator->trans('calendar.label.editDeviation', [], 'cms'),
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/calendar/deviation/{eventUuid}/delete/{deviationUuid}", name="calendar_deviation_delete")
     *
     * @param string $eventUuid
     * @param string $deviationUuid
     *
     * @return RedirectResponse
     * @throws InterfaceException
     */
    public function deleteDeviation(string $eventUuid, string $deviationUuid): RedirectResponse
    {
        $this->denyAccessUnlessGranted('edit_generic');

        /**
         * @var Event $event
         */
        $event = $this->aggregateFactory->build($eventUuid, Event::class);
        $deviation = $event->getDeviation($deviationUuid);

        /**
         * @var UserRead $user
         */
        $user = $this->getUser();

        $payload = [
            'uuid' => $deviation->uuid,
        ];

        $queueEvents = false;
        $success = $this->commandBus->execute(EventDeviationDeleteCommand::class, $event->uuid, $payload, $user->getId(), $queueEvents);
        if ($success) {
            $this->addFlash(
                'success',
                $this->translator->trans('Deviation deleted', [], 'cms')
            );
        }

        return $this->redirectToRoute('calendar_event_edit', [
            'uuid' => $event->uuid,
        ]);
    }
}

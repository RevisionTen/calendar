<?php

declare(strict_types=1);

namespace RevisionTen\Calendar\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Ramsey\Uuid\Uuid;
use RevisionTen\Calendar\Command\EventCreateCommand;
use RevisionTen\Calendar\Command\EventDeleteCommand;
use RevisionTen\Calendar\Command\EventEditCommand;
use RevisionTen\Calendar\Entity\Event;
use RevisionTen\Calendar\Entity\EventRead;
use RevisionTen\Calendar\Form\EventType;
use RevisionTen\Calendar\Form\RuleType;
use RevisionTen\CMS\Model\UserRead;
use RevisionTen\CQRS\Exception\InterfaceException;
use RevisionTen\CQRS\Services\AggregateFactory;
use RevisionTen\CQRS\Services\CommandBus;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
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
        $formClass = $calendarConfig['event_form_type'] ?? EventType::class;

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

        return $this->render('@Calendar/Admin/form.html.twig', [
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
            'image' => $event->image,
            'salesStatus' => $event->salesStatus,
            'keywords' => $event->keywords,
            'genres' => $event->genres,
            'partners' => $event->partners,
            'venue' => $event->venue,
            'extra' => $event->extra,
        ];

        $calendarConfig = $this->getParameter('calendar');
        $formClass = $calendarConfig['event_form_type'] ?? EventType::class;

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

        return $this->render('@Calendar/Admin/form.html.twig', [
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

        $data = [];

        $calendarConfig = $this->getParameter('calendar');
        $formClass = $calendarConfig['rule_form_type'] ?? RuleType::class;

        $form = $this->createForm($formClass, $data);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $payload = $form->getData();

            $queueEvents = false;
            $success = $this->commandBus->execute(EventEditCommand::class, $eventUuid, $payload, $user->getId(), $queueEvents);
            if ($success) {
                $this->addFlash(
                    'success',
                    $this->translator->trans('Rule created', [], 'cms')
                );
            }
        }

        return $this->render('@Calendar/Admin/rule_form.html.twig', [
            'title' => $this->translator->trans('calendar.label.addRule', [], 'cms'),
            'form' => $form->createView(),
        ]);
    }
}

<?php

namespace App\Controller;

use App\Entity\Event;
use App\Entity\EventPerson;
use App\Repository\EventRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/events', name: 'api_events_')]
class EventController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private SerializerInterface $serializer,
        private ValidatorInterface $validator,
        private EventRepository $eventRepository
    ) {}

    #[Route('', name: 'list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        $queryBuilder = $this->eventRepository->createQueryBuilder('e')
            ->leftJoin('e.user', 'u')
            ->leftJoin('e.persons', 'p')
            ->addSelect('u', 'p');
        
        // Check if user is authenticated
        $user = $this->getUser();
        if (!$user) {
            // For non-authenticated users, show only demo user events (user_id = 1)
            $queryBuilder->andWhere('u.id = :demoUserId')
                ->setParameter('demoUserId', 1);
        } else {
            // For authenticated users, show only their own events
            $queryBuilder->andWhere('u.id = :userId')
                ->setParameter('userId', $user->getId());
        }
        
        // Exclude hidden events by default
        $queryBuilder->andWhere('e.isHidden = :isHidden')
            ->setParameter('isHidden', false);
        
        $events = $queryBuilder
            ->orderBy('e.datetime', 'ASC')
            ->getQuery()
            ->getResult();
        
        // Clean up the events data to handle empty arrays
        $cleanedEvents = array_map(function($event) {
            // Filter out empty persons
            $persons = $event->getPersons()->filter(function($person) {
                return $person->getPersonFullname() && trim($person->getPersonFullname()) !== '';
            })->toArray();
            
            // Create a clean event object
            return [
                'id' => $event->getId(),
                'title' => $event->getTitle(),
                'description' => $event->getDescription(),
                'location' => $event->getLocation(),
                'datetime' => $event->getDatetime()->format('c'),
                'createdAt' => $event->getCreatedAt()->format('c'),
                'updatedAt' => $event->getUpdatedAt() ? $event->getUpdatedAt()->format('c') : null,
                'user' => $event->getUser() ? [
                    'id' => $event->getUser()->getId(),
                    'email' => $event->getUser()->getEmail(),
                    'name' => $event->getUser()->getFullName()
                ] : null,
                'persons' => array_map(function($person) {
                    return [
                        'id' => $person->getId(),
                        'person_fullname' => $person->getPersonFullname()
                    ];
                }, $persons)
            ];
        }, $events);
        
        return $this->json([
            'success' => true,
            'data' => $cleanedEvents,
            'count' => count($cleanedEvents)
        ], Response::HTTP_OK);
    }

    #[Route('/search', name: 'search', methods: ['GET'])]
    public function search(Request $request): JsonResponse
    {
        try {
            // Get search parameters
            $query = $request->query->get('q');
            $date = $request->query->get('date');
            
            // Validate that at least one parameter is provided
            if ((!$query || trim($query) === '') && !$date) {
                return $this->json([
                    'success' => false,
                    'message' => 'At least one search parameter is required: "q" for text search or "date" for date search'
                ], Response::HTTP_BAD_REQUEST);
            }
            
            $events = [];
            $allEvents = [];
            
            // Text search
            if ($query && trim($query) !== '') {
                $searchTerm = trim($query);
                
                // Find events that match the search criteria (case insensitive)
                $queryBuilder = $this->eventRepository->createQueryBuilder('e')
                    ->leftJoin('e.user', 'u')
                    ->leftJoin('e.persons', 'p')
                    ->addSelect('u', 'p')
                    ->where('LOWER(e.title) LIKE LOWER(:query)')
                    ->orWhere('LOWER(e.description) LIKE LOWER(:query)')
                    ->orWhere('LOWER(e.location) LIKE LOWER(:query)')
                    ->orWhere('LOWER(p.personFullname) LIKE LOWER(:query)')
                    ->setParameter('query', '%' . $searchTerm . '%');
                
                // Check if user is authenticated
                $user = $this->getUser();
                if (!$user) {
                    // For non-authenticated users, show only demo user events (user_id = 1)
                    $queryBuilder->andWhere('u.id = :demoUserId')
                        ->setParameter('demoUserId', 1);
                } else {
                    // For authenticated users, show only their own events
                    $queryBuilder->andWhere('u.id = :userId')
                        ->setParameter('userId', $user->getId());
                }
                
                // Exclude hidden events for text search
                $queryBuilder->andWhere('e.isHidden = :isHidden')
                    ->setParameter('isHidden', false);
                
                $textEvents = $queryBuilder
                    ->orderBy('e.datetime', 'ASC')
                    ->getQuery()
                    ->getResult();
                
                $allEvents = array_merge($allEvents, $textEvents);
            }
            
            // Date search
            if ($date) {
                try {
                    // Parse the date string (supports both YYYY-MM-DD and YYYY-MM-DD HH:MM:SS formats)
                    $searchDate = new \DateTimeImmutable($date);
                    
                    // Extract only the date part (ignore time)
                    $dateOnly = $searchDate->format('Y-m-d');
                    $searchDate = new \DateTimeImmutable($dateOnly);
                    
                    // Create start and end of day for the search date
                    $startOfDay = $searchDate->setTime(0, 0, 0);
                    $endOfDay = $searchDate->setTime(23, 59, 59);
                    
                    // Find events within the date range
                    $dateQueryBuilder = $this->eventRepository->createQueryBuilder('e')
                        ->leftJoin('e.user', 'u')
                        ->leftJoin('e.persons', 'p')
                        ->addSelect('u', 'p')
                        ->andWhere('e.datetime BETWEEN :startDate AND :endDate')
                        ->setParameter('startDate', $startOfDay)
                        ->setParameter('endDate', $endOfDay);
                    
                    // Check if user is authenticated
                    $user = $this->getUser();
                    if (!$user) {
                        // For non-authenticated users, show only demo user events (user_id = 1)
                        $dateQueryBuilder->andWhere('u.id = :demoUserId')
                            ->setParameter('demoUserId', 1);
                    } else {
                        // For authenticated users, show only their own events
                        $dateQueryBuilder->andWhere('u.id = :userId')
                            ->setParameter('userId', $user->getId());
                    }
                    
                    // Check if searching past dates - include hidden events for past dates
                    $today = new \DateTimeImmutable();
                    $todayStart = $today->setTime(0, 0, 0);
                    
                    if ($startOfDay < $todayStart) {
                        // Searching past dates - include hidden events
                        // No additional filter needed - show all events (hidden and visible)
                    } else {
                        // Searching current or future dates - exclude hidden events
                        $dateQueryBuilder->andWhere('e.isHidden = :isHidden')
                            ->setParameter('isHidden', false);
                    }
                    
                    $dateEvents = $dateQueryBuilder
                        ->orderBy('e.datetime', 'ASC')
                        ->getQuery()
                        ->getResult();
                    
                    $allEvents = array_merge($allEvents, $dateEvents);
                } catch (\Exception $e) {
                    return $this->json([
                        'success' => false,
                        'message' => 'Invalid date format. Please use YYYY-MM-DD or YYYY-MM-DD HH:MM:SS format (e.g., 2025-10-23 or 2025-10-21 12:12:21)',
                        'error' => $e->getMessage()
                    ], Response::HTTP_BAD_REQUEST);
                }
            }
            
            // Remove duplicates if both text and date search were performed
            if ($query && $date) {
                $uniqueEvents = [];
                $seenIds = [];
                
                foreach ($allEvents as $event) {
                    if (!in_array($event->getId(), $seenIds)) {
                        $uniqueEvents[] = $event;
                        $seenIds[] = $event->getId();
                    }
                }
                
                $events = $uniqueEvents;
            } else {
                $events = $allEvents;
            }
            
            // Sort events by datetime
            usort($events, function($a, $b) {
                return $a->getDatetime() <=> $b->getDatetime();
            });
            
            // Clean up the events data to handle empty arrays
            $cleanedEvents = array_map(function($event) {
                // Filter out empty persons
                $persons = $event->getPersons()->filter(function($person) {
                    return $person->getPersonFullname() && trim($person->getPersonFullname()) !== '';
                })->toArray();
                
                // Create a clean event object
                return [
                    'id' => $event->getId(),
                    'title' => $event->getTitle(),
                    'description' => $event->getDescription(),
                    'location' => $event->getLocation(),
                    'datetime' => $event->getDatetime()->format('c'),
                    'createdAt' => $event->getCreatedAt()->format('c'),
                    'updatedAt' => $event->getUpdatedAt() ? $event->getUpdatedAt()->format('c') : null,
                    'user' => $event->getUser() ? [
                        'id' => $event->getUser()->getId(),
                        'email' => $event->getUser()->getEmail(),
                        'name' => $event->getUser()->getFullName()
                    ] : null,
                    'persons' => array_map(function($person) {
                        return [
                            'id' => $person->getId(),
                            'person_fullname' => $person->getPersonFullname()
                        ];
                    }, $persons)
                ];
            }, $events);
            
            // Prepare response message
            $message = '';
            if ($query && $date) {
                $message = count($cleanedEvents) > 0 
                    ? 'Events found matching both text and date criteria' 
                    : 'No events found matching both text and date criteria';
            } elseif ($query) {
                $message = count($cleanedEvents) > 0 
                    ? 'Events found matching text search' 
                    : 'No events found matching text search';
            } elseif ($date) {
                $message = count($cleanedEvents) > 0 
                    ? 'Events found for the specified date' 
                    : 'No events found for the specified date';
            }
            
            return $this->json([
                'success' => true,
                'data' => $cleanedEvents,
                'count' => count($cleanedEvents),
                'search_query' => $query,
                'search_date' => $date,
                'message' => $message
            ], Response::HTTP_OK);
            
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'An error occurred during search',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(int $id): JsonResponse
    {
        $event = $this->eventRepository->createQueryBuilder('e')
            ->leftJoin('e.user', 'u')
            ->leftJoin('e.persons', 'p')
            ->addSelect('u', 'p')
            ->andWhere('e.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult();
        
        if (!$event) {
            return $this->json([
                'success' => false,
                'message' => 'Event not found'
            ], Response::HTTP_NOT_FOUND);
        }

        // Clean up the event data to handle empty arrays
        $persons = $event->getPersons()->filter(function($person) {
            return $person->getPersonFullname() && trim($person->getPersonFullname()) !== '';
        })->toArray();

        $cleanedEvent = [
            'id' => $event->getId(),
            'title' => $event->getTitle(),
            'description' => $event->getDescription(),
            'location' => $event->getLocation(),
            'datetime' => $event->getDatetime()->format('c'),
            'createdAt' => $event->getCreatedAt()->format('c'),
            'updatedAt' => $event->getUpdatedAt() ? $event->getUpdatedAt()->format('c') : null,
            'user' => $event->getUser() ? [
                'id' => $event->getUser()->getId(),
                'email' => $event->getUser()->getEmail(),
                'name' => $event->getUser()->getFullName()
            ] : null,
            'persons' => array_map(function($person) {
                return [
                    'id' => $person->getId(),
                    'person_fullname' => $person->getPersonFullname()
                ];
            }, $persons)
        ];

        return $this->json([
            'success' => true,
            'data' => $cleanedEvent
        ], Response::HTTP_OK);
    }

    #[Route('', name: 'create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        
        if (!$data) {
            return $this->json([
                'success' => false,
                'message' => 'Invalid JSON data'
            ], Response::HTTP_BAD_REQUEST);
        }


        $event = new Event();
        $event->setTitle($data['title'] ?? '');
        $event->setDescription($data['description'] ?? null);
        $event->setLocation($data['location'] ?? null);
        
        // Handle datetime with timezone information
        $datetimeValue = $data['datetime'] ?? 'now';
        $timezoneValue = $data['timezone'] ?? 'Europe/Warsaw';
        
        if ($datetimeValue === 'now') {
            $event->setDatetime(new \DateTimeImmutable());
        } else {
            // Parse the datetime string with the provided timezone
            // The datetimetz_immutable type will store the timezone information
            $datetime = new \DateTimeImmutable($datetimeValue, new \DateTimeZone($timezoneValue));
            $event->setDatetime($datetime);
        }

        // Set user (you might want to get this from authentication)
        // For now, we'll assume user_id is provided in the request
        if (isset($data['user_id'])) {
            $user = $this->entityManager->getRepository(\App\Entity\User::class)->find($data['user_id']);
            if ($user) {
                $event->setUser($user);
            }
        }

        // Validate the event
        $errors = $this->validator->validate($event);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[] = $error->getMessage();
            }
            
            return $this->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $errorMessages
            ], Response::HTTP_BAD_REQUEST);
        }

        $this->entityManager->persist($event);
        $this->entityManager->flush();

        // Handle osoby (persons) data
        if (isset($data['osoby']) && is_array($data['osoby'])) {
            foreach ($data['osoby'] as $osoba) {
                if (isset($osoba['name']) && !empty(trim($osoba['name']))) {
                    $eventPerson = new EventPerson();
                    $eventPerson->setEvent($event);
                    $eventPerson->setPersonFullname(trim($osoba['name']));
                    
                    // Validate the event person
                    $errors = $this->validator->validate($eventPerson);
                    if (count($errors) === 0) {
                        $this->entityManager->persist($eventPerson);
                    }
                }
            }
            $this->entityManager->flush();
        }

        return $this->json([
            'success' => true,
            'message' => 'Event created successfully',
            'data' => $event
        ], Response::HTTP_CREATED, [], ['groups' => ['event:read']]);
    }

    #[Route('/{id}', name: 'update', methods: ['PUT', 'PATCH'])]
    public function update(int $id, Request $request): JsonResponse
    {
        $event = $this->eventRepository->find($id);
        
        if (!$event) {
            return $this->json([
                'success' => false,
                'message' => 'Event not found'
            ], Response::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true);
        
        if (!$data) {
            return $this->json([
                'success' => false,
                'message' => 'Invalid JSON data'
            ], Response::HTTP_BAD_REQUEST);
        }

        // Update fields if provided
        if (isset($data['title'])) {
            $event->setTitle($data['title']);
        }
        if (isset($data['description'])) {
            $event->setDescription($data['description']);
        }
        if (isset($data['location'])) {
            $event->setLocation($data['location']);
        }
        if (isset($data['datetime'])) {
            $timezoneValue = $data['timezone'] ?? 'Europe/Warsaw';
            // Parse the datetime string with the provided timezone
            // The datetimetz_immutable type will store the timezone information
            $datetime = new \DateTimeImmutable($data['datetime'], new \DateTimeZone($timezoneValue));
            $event->setDatetime($datetime);
        }

        // Validate the updated event
        $errors = $this->validator->validate($event);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[] = $error->getMessage();
            }
            
            return $this->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $errorMessages
            ], Response::HTTP_BAD_REQUEST);
        }

        // Handle osoby (persons) data update
        if (isset($data['osoby']) && is_array($data['osoby'])) {
            // Remove existing persons for this event
            foreach ($event->getPersons() as $person) {
                $this->entityManager->remove($person);
            }
            
            // Add new persons
            foreach ($data['osoby'] as $osoba) {
                if (isset($osoba['name']) && !empty(trim($osoba['name']))) {
                    $eventPerson = new EventPerson();
                    $eventPerson->setEvent($event);
                    $eventPerson->setPersonFullname(trim($osoba['name']));
                    
                    // Validate the event person
                    $errors = $this->validator->validate($eventPerson);
                    if (count($errors) === 0) {
                        $this->entityManager->persist($eventPerson);
                    }
                }
            }
        }

        $this->entityManager->flush();

        return $this->json([
            'success' => true,
            'message' => 'Event updated successfully',
            'data' => $event
        ], Response::HTTP_OK, [], ['groups' => ['event:read']]);
    }

    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        $event = $this->eventRepository->find($id);
        
        if (!$event) {
            return $this->json([
                'success' => false,
                'message' => 'Event not found'
            ], Response::HTTP_NOT_FOUND);
        }

        $this->entityManager->remove($event);
        $this->entityManager->flush();

        return $this->json([
            'success' => true,
            'message' => 'Event deleted successfully'
        ], Response::HTTP_OK);
    }

    #[Route('/{id}/persons', name: 'add_person', methods: ['POST'])]
    public function addPerson(int $id, Request $request): JsonResponse
    {
        $event = $this->eventRepository->find($id);
        
        if (!$event) {
            return $this->json([
                'success' => false,
                'message' => 'Event not found'
            ], Response::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true);
        
        if (!$data || !isset($data['person_fullname'])) {
            return $this->json([
                'success' => false,
                'message' => 'person_fullname is required'
            ], Response::HTTP_BAD_REQUEST);
        }

        $eventPerson = new EventPerson();
        $eventPerson->setEvent($event);
        $eventPerson->setPersonFullname($data['person_fullname']);

        // Validate the event person
        $errors = $this->validator->validate($eventPerson);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[] = $error->getMessage();
            }
            
            return $this->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $errorMessages
            ], Response::HTTP_BAD_REQUEST);
        }

        $this->entityManager->persist($eventPerson);
        $this->entityManager->flush();

        return $this->json([
            'success' => true,
            'message' => 'Person added to event successfully',
            'data' => $eventPerson
        ], Response::HTTP_CREATED, [], ['groups' => ['event_person:read']]);
    }

    #[Route('/{eventId}/persons/{personId}', name: 'remove_person', methods: ['DELETE'])]
    public function removePerson(int $eventId, int $personId): JsonResponse
    {
        $event = $this->eventRepository->find($eventId);
        
        if (!$event) {
            return $this->json([
                'success' => false,
                'message' => 'Event not found'
            ], Response::HTTP_NOT_FOUND);
        }

        $eventPerson = $this->entityManager->getRepository(EventPerson::class)->find($personId);
        
        if (!$eventPerson || $eventPerson->getEvent()->getId() !== $eventId) {
            return $this->json([
                'success' => false,
                'message' => 'Person not found in this event'
            ], Response::HTTP_NOT_FOUND);
        }

        $this->entityManager->remove($eventPerson);
        $this->entityManager->flush();

        return $this->json([
            'success' => true,
            'message' => 'Person removed from event successfully'
        ], Response::HTTP_OK);
    }

    #[Route('/{id}/hide', name: 'hide', methods: ['POST'])]
    public function hideEvent(int $id): JsonResponse
    {
        $event = $this->eventRepository->find($id);
        
        if (!$event) {
            return $this->json([
                'success' => false,
                'message' => 'Event not found'
            ], Response::HTTP_NOT_FOUND);
        }

        // Check if user is authenticated and owns the event
        $user = $this->getUser();
        if (!$user || $event->getUser()->getId() !== $user->getId()) {
            return $this->json([
                'success' => false,
                'message' => 'Unauthorized to hide this event'
            ], Response::HTTP_FORBIDDEN);
        }

        $event->setIsHidden(true);
        $this->entityManager->flush();

        return $this->json([
            'success' => true,
            'message' => 'Event hidden successfully',
            'data' => [
                'id' => $event->getId(),
                'isHidden' => $event->isHidden()
            ]
        ], Response::HTTP_OK);
    }

    #[Route('/{id}/show', name: 'show_event', methods: ['POST'])]
    public function showEvent(int $id): JsonResponse
    {
        $event = $this->eventRepository->find($id);
        
        if (!$event) {
            return $this->json([
                'success' => false,
                'message' => 'Event not found'
            ], Response::HTTP_NOT_FOUND);
        }

        // Check if user is authenticated and owns the event
        $user = $this->getUser();
        if (!$user || $event->getUser()->getId() !== $user->getId()) {
            return $this->json([
                'success' => false,
                'message' => 'Unauthorized to show this event'
            ], Response::HTTP_FORBIDDEN);
        }

        $event->setIsHidden(false);
        $this->entityManager->flush();

        return $this->json([
            'success' => true,
            'message' => 'Event shown successfully',
            'data' => [
                'id' => $event->getId(),
                'isHidden' => $event->isHidden()
            ]
        ], Response::HTTP_OK);
    }


}


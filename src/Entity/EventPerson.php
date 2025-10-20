<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity]
#[ORM\Table(name: 'event_persons')]
class EventPerson
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    #[Groups(['event_person:read'])]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Event::class, inversedBy: 'persons')]
    #[ORM\JoinColumn(name: 'event_id', referencedColumnName: 'id', nullable: false)]
    #[Groups(['event_person:read'])]
    private Event $event;

    #[ORM\Column(type: 'string', length: 255)]
    #[Assert\NotBlank(message: 'Person full name is required')]
    #[Assert\Length(min: 2, max: 255, minMessage: 'Person full name must be at least 2 characters', maxMessage: 'Person full name must be less than 255 characters')]
    #[Groups(['event_person:read', 'event_person:write'])]
    private string $personFullname;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEvent(): Event
    {
        return $this->event;
    }

    public function setEvent(Event $event): static
    {
        $this->event = $event;

        return $this;
    }

    public function getPersonFullname(): string
    {
        return $this->personFullname;
    }

    public function setPersonFullname(string $personFullname): static
    {
        $this->personFullname = $personFullname;

        return $this;
    }
}

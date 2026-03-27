<?php

namespace App\EventListener;

use App\Entity\Contact;
use App\Entity\WarehouseMovement;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Events;
use Doctrine\ORM\Event\PrePersistEventArgs;

#[AsEntityListener(event: Events::prePersist, method: 'prePersist', entity: WarehouseMovement::class)]
class WarehouseMovementListener
{
    public function prePersist(WarehouseMovement $warehouseMovement, PrePersistEventArgs $event): void
    {
        if (!$warehouseMovement->getContact()) {
            $entityManager = $event->getObjectManager();
            $contact = $entityManager->getRepository(Contact::class)->findOneBy(['name' => 'Conceria Tre Emme S.R.L.']);
            
            if ($contact) {
                $warehouseMovement->setContact($contact);
            }
        }
    }
}

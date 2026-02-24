<?php

namespace App\Security\Voter;

use App\Entity\GroupRoleWorkArea;
use App\Entity\User;
use App\Entity\WorkArea;
use App\Repository\GroupRoleWorkAreaRepository;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class WorkAreaVoter extends Voter
{
    public const GET = 'WORKAREA_GET';
    public const POST = 'WORKAREA_POST';
    public const PUT = 'WORKAREA_PUT';
    public const DELETE = 'WORKAREA_DELETE';

    public function __construct(private GroupRoleWorkAreaRepository $groupRoleWorkAreaRepository)
    {
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, [self::GET, self::POST, self::PUT, self::DELETE])
            && ($subject instanceof WorkArea || $subject === null);
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();
        if (!$user instanceof User) {
            return false;
        }

        // Se l'utente è un super admin (ROLE_ADMIN), potrebbe avere accesso a tutto
        if (in_array('ROLE_ADMIN', $user->getRoles())) {
            return true;
        }

        // Otteniamo i gruppi dell'utente
        $groups = [];
        foreach ($user->getGroupUsers() as $groupUser) {
            $groups[] = $groupUser->getGroupp();
        }

        if (empty($groups)) {
            return false;
        }

        // Cerchiamo le configurazioni GroupRoleWorkArea per i gruppi dell'utente e la WorkArea specifica
        $criteria = ['groupp' => $groups];
        if ($subject instanceof WorkArea) {
            $criteria['workArea'] = $subject;
        }

        $permissions = $this->groupRoleWorkAreaRepository->findBy($criteria);

        foreach ($permissions as $permission) {
            if ($this->checkPermission($attribute, $permission)) {
                return true;
            }
        }

        return false;
    }

    private function checkPermission(string $attribute, GroupRoleWorkArea $permission): bool
    {
        return match ($attribute) {
            self::GET => $permission->isCanGet(),
            self::POST => $permission->isCanPost(),
            self::PUT => $permission->isCanPut(),
            self::DELETE => $permission->isCanDelete(),
            default => false,
        };
    }
}

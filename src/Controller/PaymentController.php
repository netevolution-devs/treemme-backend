<?php
namespace App\Controller;


use App\Entity\Payment;
use App\Service\DoResponseService;
use App\Service\GroupSerializerService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;




class PaymentController extends AbstractController implements TokenAuthenticatedController

{
    private DoResponseService $doResponse;
    private GroupSerializerService $groupSerializer;
    private EntityManagerInterface $doctrine;

    public function __construct(
        EntityManagerInterface   $entityManager,
        DoResponseService        $doResponseService,
        GroupSerializerService   $groupSerializer,
    )
    {
        $this->doctrine = $entityManager;
        $this->doResponse = $doResponseService;
        $this->groupSerializer = $groupSerializer;
    }


    #[Route('/v1/backoffice/payment/{id}',
        name: 'get_backoffice_payment',
        defaults: ['id' => null],
        methods: ['GET', 'HEAD'])]
    public function getPayment(
        ?int $id
    ): JsonResponse
    {
        $objs = $this->doctrine
            ->getRepository(Payment::class);

        $objs = $id ?
            $objs->findBy(['id' => $id]) :
            $objs->findBy([], ['id' => 'ASC']);

        $objs = $this->groupSerializer->serializeGroup($objs, $id ? 'detail' : 'list');


        return new JsonResponse($this->doResponse->doResponse($objs));
    }

}

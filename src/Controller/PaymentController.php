<?php
namespace App\Controller;


use App\Entity\Payment;
use App\Service\CreateMethodsByInput;
use App\Service\DoResponseService;
use App\Service\GroupSerializerService;
use App\Service\ValidatorOutputFormatter;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;


class PaymentController extends AbstractController implements TokenAuthenticatedController

{
    private CreateMethodsByInput $createMethodsByInput;
    private EntityManagerInterface $doctrine;
    private DoResponseService $doResponse;
    private GroupSerializerService $groupSerializer;
    private ValidatorOutputFormatter $validatorOutputFormatter;

    public function __construct(
        CreateMethodsByInput     $createMethodsByInput,
        EntityManagerInterface   $entityManager,
        DoResponseService        $doResponseService,
        GroupSerializerService   $groupSerializer,
        ValidatorOutputFormatter $validatorOutputFormatter,
    )
    {
        $this->createMethodsByInput = $createMethodsByInput;
        $this->doctrine = $entityManager;
        $this->doResponse = $doResponseService;
        $this->groupSerializer = $groupSerializer;
        $this->validatorOutputFormatter = $validatorOutputFormatter;
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

    /**
     * @throws Exception
     */
    #[Route('/v1/backoffice/payment',
        name: 'post_backoffice_payment',
        methods: ['POST'])]
    public function postPayment(
        Request            $request,
        ValidatorInterface $validator
    ): JsonResponse
    {
        $data = $request->request->all();
        $obj = new Payment();

        $createdAt = $data['created_at'] ?? null;
        if ($createdAt) {
            $obj->setCreatedAt(new \DateTime($createdAt));
            unset($data['created_at']);
        }

        $obj = $this->createMethodsByInput->createMethods($obj, $data);

        $errors = $validator->validate($obj);
        if (count($errors) > 0) {
            $errors = $this->validatorOutputFormatter->formatOutput($errors);

            return new JsonResponse($this->doResponse->doErrorResponse($errors));
        }


        $em = $this->doctrine;
        $em->persist($obj);
        $em->flush();

        $result = $this->groupSerializer->serializeGroup($obj, 'detail');


        return new JsonResponse($this->doResponse->doResponse($result));
    }

    #[Route('/v1/backoffice/payment/{id}',
        name: 'put_backoffice_payment',
        methods: ['PUT'])]
    public function putPayment(
        Request            $request,
        ValidatorInterface $validator,
        int                $id
    ): JsonResponse
    {
        $data = $request->request->all();

        $obj = $this->doctrine->getRepository(Payment::class)->find($id);

        if (!$obj) {
            return new JsonResponse($this->doResponse->doErrorResponse('Payment not found'));
        }

        $createdAt = $data['created_at'] ?? null;
        if ($createdAt) {
            $obj->setCreatedAt(new \DateTime($createdAt));
            unset($data['created_at']);
        }

        $obj = $this->createMethodsByInput->createMethods($obj, $data);

        $errors = $validator->validate($obj);
        if (count($errors) > 0) {
            $errors = $this->validatorOutputFormatter->formatOutput($errors);
            return new JsonResponse($this->doResponse->doErrorResponse($errors));
        }

        $em = $this->doctrine;
        $em->persist($obj);
        $em->flush();

        $result = $this->groupSerializer->serializeGroup($obj, 'detail');


        return new JsonResponse($this->doResponse->doResponse($result));
    }

    #[Route('/v1/backoffice/payment/{id}',
        name: 'delete_backoffice_payment',
        methods: ['DELETE'])]
    public function deletePayment(
        int $id
    ): JsonResponse
    {
        $obj = $this->doctrine->getRepository(Payment::class)->find($id);

        if (!$obj) {
            return new JsonResponse($this->doResponse->doErrorResponse('Payment not found'));
        }

        $em = $this->doctrine;
        $em->remove($obj);
        $em->flush();


        return new JsonResponse($this->doResponse->doResponse('delete_successfully'));
    }

}

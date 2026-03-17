<?php

namespace App\Controller;

use App\Entity\Payment;
use App\Service\CreateMethodsByInput;
use App\Service\DoResponseService;
use App\Service\GroupSerializerService;
use App\Service\ValidatorOutputFormatter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class PaymentController extends AbstractController
{
    private $createMethodsByInput;
    private $doctrine;
    private $doResponse;
    private $groupSerializer;
    private $validatorOutputFormatter;

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

    #[Route('/payment/{id}',
        name: 'get_payment',
        defaults: ['id' => null],
        requirements: ['id' => '\d*'],
        methods: ['GET', 'HEAD'])]
    public function getPayment(?int $id): JsonResponse
    {
        $paymentRepository = $this->doctrine->getRepository(Payment::class);

        if ($id) {
            $payment = [$paymentRepository->find($id)];
            if (!$payment[0]) {
                return new JsonResponse($this->doResponse->doErrorResponse('Payment not found', 404));
            }
        } else {
            $payment = $paymentRepository->findBy([], ['id' => 'DESC']);
        }
        $results = $this->groupSerializer->serializeGroup($payment, $id ? 'payment_detail' : 'payment_list');

        if ($id) {
            return new JsonResponse($this->doResponse->doResponse($results[0]));
        }
        return new JsonResponse($this->doResponse->doResponse($results));
    }

    #[Route('/payment',
        name: 'post_payment',
        methods: ['POST'])]
    public function postPayment(
        Request            $request,
        ValidatorInterface $validator,
    ): JsonResponse
    {
        $data = $request->request->all();
        $payment = new Payment();

        try {
            $payment = $this->createMethodsByInput->createMethods($payment, $data);

            $errors = $validator->validate($payment);
            if (count($errors) > 0) {
                $errors = $this->validatorOutputFormatter->formatOutput($errors);
                return new JsonResponse($this->doResponse->doErrorResponse($errors));
            }

            $em = $this->doctrine;
            $em->persist($payment);
            $em->flush();

            $result = $this->groupSerializer->serializeGroup($payment, 'payment_detail');
            return new JsonResponse($this->doResponse->doResponse($result));

        } catch (\Exception $e) {
            return new JsonResponse($this->doResponse->doErrorResponse($e->getMessage()));
        }
    }

    #[Route('/payment/{id}',
        name: 'put_payment',
        methods: ['PUT'])]
    public function modifyPayment(
        Request            $request,
        ValidatorInterface $validator,
        int                $id,
    ): JsonResponse
    {
        $data = $request->toArray();
        $payment = $this->doctrine->getRepository(Payment::class)->find($id);

        if (!$payment) {
            return new JsonResponse($this->doResponse->doErrorResponse('Payment not found', 404));
        }

        try {
            $payment = $this->createMethodsByInput->createMethods($payment, $data);

            $errors = $validator->validate($payment);
            if (count($errors) > 0) {
                $errors = $this->validatorOutputFormatter->formatOutput($errors);
                return new JsonResponse($this->doResponse->doErrorResponse($errors));
            }

            $this->doctrine->persist($payment);
            $this->doctrine->flush();

            $result = $this->groupSerializer->serializeGroup($payment, 'payment_detail');
            return new JsonResponse($this->doResponse->doResponse($result));
        } catch (\Exception $e) {
            return new JsonResponse($this->doResponse->doErrorResponse($e->getMessage()));
        }
    }

    #[Route('/payment/{id}',
        name: 'delete_payment',
        methods: ['DELETE'])]
    public function deletePayment(int $id): JsonResponse
    {
        $payment = $this->doctrine->getRepository(Payment::class)->find($id);
        if (!$payment) {
            return new JsonResponse($this->doResponse->doErrorResponse('Payment not found', 404));
        }

        $this->doctrine->remove($payment);
        $this->doctrine->flush();

        return new JsonResponse($this->doResponse->doResponse('delete_successfully'));
    }
}

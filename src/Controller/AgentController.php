<?php

namespace App\Controller;

use App\Entity\Agent;
use App\Entity\ContactAddress;
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

final class AgentController extends AbstractController
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

    #[Route('/agent/{id}',
        name: 'get_agent',
        defaults: ['id' => null],
        requirements: ['id' => '\d*'],
        methods: ['GET', 'HEAD'])]
    public function getAgent(?int $id): JsonResponse
    {
        $agentRepository = $this->doctrine->getRepository(Agent::class);

        if ($id) {
            $agent = $agentRepository->find($id);
            if (!$agent) {
                return new JsonResponse($this->doResponse->doErrorResponse('Agent not found', 404));
            }
            $results = $this->groupSerializer->serializeGroup($agent, 'agent_detail');
            return new JsonResponse($this->doResponse->doResponse($results[0]));
        }

        $agents = $agentRepository->findBy([], ['id' => 'DESC']);
        $results = $this->groupSerializer->serializeGroup($agents, 'agent_list');
        return new JsonResponse($this->doResponse->doResponse($results));
    }

    #[Route('/agent',
        name: 'post_agent',
        methods: ['POST'])]
    public function postAgent(
        Request            $request,
        ValidatorInterface $validator,
    ): JsonResponse
    {
        $data = $request->request->all();
        if (!$data) {
            $data = json_decode($request->getContent(), true) ?? [];
        }

        $agent = new Agent();

        try {
            $agent = $this->handleRelations($agent, $data);
            $agent = $this->createMethodsByInput->createMethods($agent, $data);

            $errors = $validator->validate($agent);
            if (count($errors) > 0) {
                $errors = $this->validatorOutputFormatter->formatOutput($errors);
                return new JsonResponse($this->doResponse->doErrorResponse($errors));
            }

            $this->doctrine->persist($agent);
            $this->doctrine->flush();

            $result = $this->groupSerializer->serializeGroup($agent, 'agent_detail');
            return new JsonResponse($this->doResponse->doResponse($result));

        } catch (\Exception $e) {
            return new JsonResponse($this->doResponse->doErrorResponse($e->getMessage()));
        }
    }

    #[Route('/agent/{id}',
        name: 'put_agent',
        methods: ['PUT'])]
    public function modifyAgent(
        Request            $request,
        ValidatorInterface $validator,
        int                $id,
    ): JsonResponse
    {
        $data = json_decode($request->getContent(), true) ?? [];

        $agent = $this->doctrine->getRepository(Agent::class)->find($id);
        if (!$agent) {
            return new JsonResponse($this->doResponse->doErrorResponse('Agent not found', 404));
        }

        try {
            $agent = $this->handleRelations($agent, $data);
            $agent = $this->createMethodsByInput->createMethods($agent, $data);

            $errors = $validator->validate($agent);
            if (count($errors) > 0) {
                $errors = $this->validatorOutputFormatter->formatOutput($errors);
                return new JsonResponse($this->doResponse->doErrorResponse($errors));
            }

            $this->doctrine->flush();

            $result = $this->groupSerializer->serializeGroup($agent, 'agent_detail');
            return new JsonResponse($this->doResponse->doResponse($result));
        } catch (\Exception $e) {
            return new JsonResponse($this->doResponse->doErrorResponse($e->getMessage()));
        }
    }

    #[Route('/agent/{id}', name: 'delete_agent', methods: ['DELETE'])]
    public function deleteAgent(int $id): JsonResponse
    {
        $agent = $this->doctrine->getRepository(Agent::class)->find($id);
        if (!$agent) {
            return new JsonResponse($this->doResponse->doErrorResponse('Agent not found', 404));
        }

        $this->doctrine->remove($agent);
        $this->doctrine->flush();

        return new JsonResponse($this->doResponse->doResponse('Agent deleted'));
    }

    private function handleRelations(Agent $agent, array $data): Agent
    {
        if (isset($data['address_id'])) {
            $address = $this->doctrine->getRepository(ContactAddress::class)->find($data['address_id']);
            if ($address) {
                $agent->setAddress($address);
            }
        }

        if (isset($data['payment_id'])) {
            $payment = $this->doctrine->getRepository(Payment::class)->find($data['payment_id']);
            if ($payment) {
                $agent->setPayment($payment);
            }
        }

        return $agent;
    }
}

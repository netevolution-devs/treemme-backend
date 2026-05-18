<?php

namespace App\Controller;

use App\Entity\SeaPort;
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

final class SeaPortController extends AbstractController
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

    #[Route('/sea-port/{id}',
        name: 'get_sea_port',
        defaults: ['id' => null],
        requirements: ['id' => '\d*'],
        methods: ['GET', 'HEAD'])]
    public function getSeaPort(?int $id): JsonResponse
    {
        $repository = $this->doctrine->getRepository(SeaPort::class);

        if ($id) {
            $seaPort = $repository->find($id);
            if (!$seaPort) {
                return $this->doResponse->doErrorJsonResponse('SeaPort not found', 404);
            }
            $seaPorts = [$seaPort];
        } else {
            $seaPorts = $repository->findBy([], ['name' => 'ASC']);
        }

        $results = $this->groupSerializer->serializeGroup($seaPorts, $id ? 'sea_port_detail' : 'sea_port_list');

        if ($id) {
            return new JsonResponse($this->doResponse->doResponse($results[0]));
        }
        return new JsonResponse($this->doResponse->doResponse($results));
    }

    #[Route('/sea-port',
        name: 'post_sea_port',
        methods: ['POST'])]
    public function postSeaPort(
        Request            $request,
        ValidatorInterface $validator,
    ): JsonResponse
    {
        $data = $request->request->all();
        $seaPort = new SeaPort();

        try {
            $seaPort = $this->createMethodsByInput->createMethods($seaPort, $data);

            $errors = $validator->validate($seaPort);
            if (count($errors) > 0) {
                $errors = $this->validatorOutputFormatter->formatOutput($errors);
                return $this->doResponse->doErrorJsonResponse($errors);
            }

            $this->doctrine->persist($seaPort);
            $this->doctrine->flush();

            $result = $this->groupSerializer->serializeGroup($seaPort, 'sea_port_detail');
            return new JsonResponse($this->doResponse->doResponse($result));

        } catch (\Exception $e) {
            return $this->doResponse->doErrorJsonResponse($e->getMessage());
        }
    }

    #[Route('/sea-port/{id}',
        name: 'put_sea_port',
        methods: ['PUT'])]
    public function modifySeaPort(
        Request            $request,
        ValidatorInterface $validator,
        int                $id,
    ): JsonResponse
    {
        $data = $request->toArray();
        $seaPort = $this->doctrine->getRepository(SeaPort::class)->find($id);

        if (!$seaPort) {
            return $this->doResponse->doErrorJsonResponse('SeaPort not found', 404);
        }

        try {
            $seaPort = $this->createMethodsByInput->createMethods($seaPort, $data);

            $errors = $validator->validate($seaPort);
            if (count($errors) > 0) {
                $errors = $this->validatorOutputFormatter->formatOutput($errors);
                return $this->doResponse->doErrorJsonResponse($errors);
            }

            $this->doctrine->persist($seaPort);
            $this->doctrine->flush();

            $result = $this->groupSerializer->serializeGroup($seaPort, 'sea_port_detail');
            return new JsonResponse($this->doResponse->doResponse($result));
        } catch (\Exception $e) {
            return $this->doResponse->doErrorJsonResponse($e->getMessage());
        }
    }

    #[Route('/sea-port/{id}',
        name: 'delete_sea_port',
        methods: ['DELETE'])]
    public function deleteSeaPort(int $id): JsonResponse
    {
        $seaPort = $this->doctrine->getRepository(SeaPort::class)->find($id);
        if (!$seaPort) {
            return $this->doResponse->doErrorJsonResponse('SeaPort not found', 404);
        }

        $this->doctrine->remove($seaPort);
        $this->doctrine->flush();

        return new JsonResponse($this->doResponse->doResponse('delete_successfully'));
    }
}


<?php

namespace App\Controller;

use App\Entity\WarehouseMovement;
use App\Entity\WarehouseMovementReason;
use App\Entity\BatchComposition;
use App\Entity\Batch;
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

final class BatchCompositionController extends AbstractController
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

    #[Route('/batch-composition/{id}',
        name: 'get_batch_composition',
        defaults: ['id' => null],
        requirements: ['id' => '\d*'],
        methods: ['GET', 'HEAD'])]
    public function getBatchComposition(?int $id): JsonResponse
    {
        $batchCompositionRepository = $this->doctrine->getRepository(BatchComposition::class);

        if ($id) {
            $batchComposition = [$batchCompositionRepository->find($id)];
            if (!$batchComposition[0]) {
                return new JsonResponse($this->doResponse->doErrorResponse('BatchComposition not found', 404));
            }
        } else {
            $batchComposition = $batchCompositionRepository->findBy([], ['id' => 'DESC']);
        }
        $results = $this->groupSerializer->serializeGroup($batchComposition, $id ? 'batch_composition_detail' : 'batch_composition_list');

        if ($id) {
            return new JsonResponse($this->doResponse->doResponse($results[0]));
        }
        return new JsonResponse($this->doResponse->doResponse($results));
    }

    #[Route('/batch-composition',
        name: 'post_batch_composition',
        methods: ['POST'])]
    public function postBatchComposition(
        Request            $request,
        ValidatorInterface $validator,
    ): JsonResponse
    {
        $data = $request->request->all();
        $batchComposition = new BatchComposition();

        try {
            $batchComposition = $this->handleRelations($batchComposition, $data);
            $batchComposition = $this->createMethodsByInput->createMethods($batchComposition, $data);

            $errors = $validator->validate($batchComposition);
            if (count($errors) > 0) {
                $errors = $this->validatorOutputFormatter->formatOutput($errors);
                return new JsonResponse($this->doResponse->doErrorResponse($errors));
            }

            $em = $this->doctrine;
            $em->persist($batchComposition);
            $em->flush();

            $this->createMovements($batchComposition);

            $result = $this->groupSerializer->serializeGroup($batchComposition, 'batch_composition_detail');
            return new JsonResponse($this->doResponse->doResponse($result));

        } catch (\Exception $e) {
            return new JsonResponse($this->doResponse->doErrorResponse($e->getMessage()));
        }
    }

    #[Route('/batch-composition/{id}',
        name: 'put_batch_composition',
        methods: ['PUT'])]
    public function modifyBatchComposition(
        Request            $request,
        ValidatorInterface $validator,
        int                $id,
    ): JsonResponse
    {
        $data = $request->toArray();
        $batchComposition = $this->doctrine->getRepository(BatchComposition::class)->find($id);

        if (!$batchComposition) {
            return new JsonResponse($this->doResponse->doErrorResponse('BatchComposition not found', 404));
        }

        try {
            $batchComposition = $this->handleRelations($batchComposition, $data);
            $batchComposition = $this->createMethodsByInput->createMethods($batchComposition, $data);

            $errors = $validator->validate($batchComposition);
            if (count($errors) > 0) {
                $errors = $this->validatorOutputFormatter->formatOutput($errors);
                return new JsonResponse($this->doResponse->doErrorResponse($errors));
            }

            $this->deleteExistingMovements($batchComposition);
            $this->doctrine->persist($batchComposition);
            $this->doctrine->flush();

            $this->createMovements($batchComposition);

            $result = $this->groupSerializer->serializeGroup($batchComposition, 'batch_composition_detail');
            return new JsonResponse($this->doResponse->doResponse($result));
        } catch (\Exception $e) {
            return new JsonResponse($this->doResponse->doErrorResponse($e->getMessage()));
        }
    }

    #[Route('/batch-composition/{id}',
        name: 'delete_batch_composition',
        methods: ['DELETE'])]
    public function deleteBatchComposition(int $id): JsonResponse
    {
        $batchComposition = $this->doctrine->getRepository(BatchComposition::class)->find($id);
        if (!$batchComposition) {
            return new JsonResponse($this->doResponse->doErrorResponse('BatchComposition not found', 404));
        }

        $this->deleteExistingMovements($batchComposition);
        $this->doctrine->remove($batchComposition);
        $this->doctrine->flush();

        return new JsonResponse($this->doResponse->doResponse('delete_successfully'));
    }

    private function createMovements(BatchComposition $batchComposition): void
    {
        $fatherBatch = $batchComposition->getFatherBatch();
        $batch = $batchComposition->getBatch();
        $pieces = $batchComposition->getFatherBatchPiece();
        $quantity = $batchComposition->getFatherBatchQuantity();

        if (!$fatherBatch || !$batch) {
            return;
        }

        $reasonRepo = $this->doctrine->getRepository(WarehouseMovementReason::class);

        // Scarico dal padre
        $outReason = $reasonRepo->createQueryBuilder('r')
            ->join('r.reason_type', 't')
            ->where('r.name = :name')
            ->andWhere('t.movement_type = :type')
            ->setParameter('name', 'Scarico Lavorazione')
            ->setParameter('type', '-')
            ->getQuery()
            ->getOneOrNullResult()
            ?? $reasonRepo->findOneBy(['name' => 'Scarico Lavorazione'])
            ?? $reasonRepo->findOneBy(['name' => 'Lavorazione Esterna (Uscita)']);

        if ($outReason) {
            $outMovement = new WarehouseMovement();
            $outMovement->setBatch($fatherBatch);
            $outMovement->setReason($outReason);
            $outMovement->setQuantity($quantity);
            $outMovement->setPiece($pieces);
            $outMovement->setDate(new \DateTime());
            $outMovement->setMovementNote('Scarico per composizione lotto ' . $batch->getBatchCode() . ' (ID Comp: ' . $batchComposition->getId() . ')');
            $this->doctrine->persist($outMovement);
        }

        // Carico nel figlio
        $inReason = $reasonRepo->createQueryBuilder('r')
            ->join('r.reason_type', 't')
            ->where('r.name = :name')
            ->andWhere('t.movement_type = :type')
            ->setParameter('name', 'Carico Lavorazione')
            ->setParameter('type', '+')
            ->getQuery()
            ->getOneOrNullResult()
            ?? $reasonRepo->findOneBy(['name' => 'Carico Lavorazione'])
            ?? $reasonRepo->findOneBy(['name' => 'Lavorazione Esterna (Entrata)']);

        if ($inReason) {
            $inMovement = new WarehouseMovement();
            $inMovement->setBatch($batch);
            $inMovement->setReason($inReason);
            $inMovement->setQuantity($quantity);
            $inMovement->setPiece($pieces);
            $inMovement->setDate(new \DateTime());
            $inMovement->setMovementNote('Carico da composizione lotto ' . $fatherBatch->getBatchCode() . ' (ID Comp: ' . $batchComposition->getId() . ')');
            $this->doctrine->persist($inMovement);
        }

        $this->doctrine->flush();
    }

    private function deleteExistingMovements(BatchComposition $batchComposition): void
    {
        $movementRepo = $this->doctrine->getRepository(WarehouseMovement::class);
        $noteToSearch = '(ID Comp: ' . $batchComposition->getId() . ')';

        $movements = $movementRepo->createQueryBuilder('m')
            ->where('m.movement_note LIKE :note')
            ->setParameter('note', '%' . $noteToSearch . '%')
            ->getQuery()
            ->getResult();

        foreach ($movements as $movement) {
            $this->doctrine->remove($movement);
        }
        $this->doctrine->flush();
    }

    private function handleRelations(BatchComposition $batchComposition, array &$data): BatchComposition
    {
        if (isset($data['batch_id'])) {
            $batch = $this->doctrine->getRepository(Batch::class)->find($data['batch_id']);
            if ($batch) {
                $batchComposition->setBatch($batch);
            }
            unset($data['batch_id']);
        }

        if (isset($data['father_batch_id'])) {
            $fatherBatch = $this->doctrine->getRepository(Batch::class)->find($data['father_batch_id']);
            if ($fatherBatch) {
                $batchComposition->setFatherBatch($fatherBatch);
            }
            unset($data['father_batch_id']);
        }

        return $batchComposition;
    }
}

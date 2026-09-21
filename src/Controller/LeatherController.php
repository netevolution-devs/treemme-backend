<?php

namespace App\Controller;

use App\Entity\Leather;
use App\Entity\LeatherWeight;
use App\Entity\LeatherSpecies;
use App\Entity\Contact;
use App\Entity\LeatherThickness;
use App\Entity\LeatherFlay;
use App\Entity\LeatherProvenance;
use App\Entity\LeatherType;
use App\Entity\LeatherStatus;
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
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'leather')]
final class LeatherController extends AbstractController
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

    #[Route('/leather/{id}',
        name: 'get_leather',
        defaults: ['id' => null],
        requirements: ['id' => '\d*'],
        methods: ['GET', 'HEAD'])]
    public function getLeather(?int $id, Request $request): JsonResponse
    {
        $leatherRepository = $this->doctrine->getRepository(Leather::class);

        if ($id) {
            $leather = [$leatherRepository->find($id)];
            if (!$leather[0]) {
                return $this->doResponse->doErrorJsonResponse('Leather not found', 404);
            }
        } else {
            $filters = [];
            $allowedFilters = [
                'weight', 'species', 'contact', 'thickness', 'supplier',
                'flay', 'provenance', 'type', 'status', 'provenance_area'
            ];

            foreach ($allowedFilters as $filter) {
                if ($request->query->has($filter)) {
                    $filters[$filter] = $request->query->get($filter);
                }
            }

            if (!empty($filters)) {
                $leather = $leatherRepository->findWithFilters($filters);
            } else {
                $leather = $leatherRepository->findBy([], ['name' => 'ASC']);
            }
        }
        $results = $this->groupSerializer->serializeGroup($leather, $id ? 'leather_detail' : 'leather_list');

        if ($id) {
            return new JsonResponse($this->doResponse->doResponse($results[0]));
        }
        return new JsonResponse($this->doResponse->doResponse($results));
    }

    #[Route('/leather',
        name: 'post_leather',
        methods: ['POST'])]
    public function postLeather(
        Request            $request,
        ValidatorInterface $validator,
    ): JsonResponse
    {
        $data = $request->request->all();
        $leather = new Leather();

        try {
            $leather = $this->handleRelations($leather, $data);
            $leather = $this->createMethodsByInput->createMethods($leather, $data);

            $leather->setName($leather->generateName());
            
            $supplierName = strtoupper(trim((string) $leather->getSupplier()?->getName()));
            $supplierCode = mb_substr(str_replace(' ', '', $supplierName), 0, 3);

            $typeCode = strtoupper(trim((string) $leather->getType()?->getCode()));
            $typeCode = $typeCode === '' ? '' : (mb_strlen($typeCode) === 1 ? $typeCode . $typeCode : mb_substr($typeCode, 0, 2));

            $speciesCode = strtoupper(trim((string) $leather->getSpecies()?->getCode()));
            $speciesCode = mb_substr($speciesCode, 0, 3);

            $nationCode = strtoupper(trim((string) $leather->getProvenance()?->getNation()?->getName()));
            $nationCode = mb_substr(str_replace(' ', '', $nationCode), 0, 3);

            $weightCode = strtoupper(trim((string) $leather->getWeight()?->getName()));

            $thicknessValue = (string) (method_exists($leather, 'getThicknessMm') ? $leather->getThicknessMm() : '');
            $thicknessCode = preg_replace('/[^\d]/', '', $thicknessValue) ?? '';
            if ($thicknessCode === '' || (int) $thicknessCode === 0) {
                $thicknessCode = strtoupper(trim((string) $leather->getThickness()?->getName()));
            }

            $flayCode = strtoupper(trim((string) $leather->getFlay()?->getCode()));

            $code = $supplierCode . $typeCode . $speciesCode . $nationCode . $weightCode . $thicknessCode . $flayCode;

            $code = preg_replace('/[^A-Za-z0-9]/', '', $code);
            $leather->setCode($code);

            $errors = $validator->validate($leather);
            if (count($errors) > 0) {
                $errors = $this->validatorOutputFormatter->formatOutput($errors);
                return $this->doResponse->doErrorJsonResponse($errors);
            }

            $em = $this->doctrine;
            $em->persist($leather);
            $em->flush();

            $result = $this->groupSerializer->serializeGroup($leather, 'leather_detail');
            return new JsonResponse($this->doResponse->doResponse($result));

        } catch (\Exception $e) {
            return $this->doResponse->doErrorJsonResponse($e->getMessage());
        }
    }

    #[Route('/leather/{id}',
        name: 'put_leather',
        methods: ['PUT'])]
    public function modifyLeather(
        Request            $request,
        ValidatorInterface $validator,
        int                $id,
    ): JsonResponse
    {
        $data = $request->toArray();
        $leather = $this->doctrine->getRepository(Leather::class)->find($id);

        if (!$leather) {
            return $this->doResponse->doErrorJsonResponse('Leather not found', 404);
        }

        try {
            $leather = $this->handleRelations($leather, $data);
            $leather = $this->createMethodsByInput->createMethods($leather, $data);

            $leather->setName($leather->generateName());

            $supplierName = strtoupper(trim((string) $leather->getSupplier()?->getName()));
            $supplierCode = mb_substr(str_replace(' ', '', $supplierName), 0, 3);

            $typeCode = strtoupper(trim((string) $leather->getType()?->getCode()));
            $typeCode = $typeCode === '' ? '' : (mb_strlen($typeCode) === 1 ? $typeCode . $typeCode : mb_substr($typeCode, 0, 2));

            $speciesCode = strtoupper(trim((string) $leather->getSpecies()?->getCode()));
            $speciesCode = mb_substr($speciesCode, 0, 3);

            $nationCode = strtoupper(trim((string) $leather->getProvenance()?->getNation()?->getName()));
            $nationCode = mb_substr(str_replace(' ', '', $nationCode), 0, 3);

            $weightCode = strtoupper(trim((string) $leather->getWeight()?->getName()));

            $thicknessValue = (string) (method_exists($leather, 'getThicknessMm') ? $leather->getThicknessMm() : '');
            $thicknessCode = preg_replace('/[^\d]/', '', $thicknessValue) ?? '';
            if ($thicknessCode === '' || (int) $thicknessCode === 0) {
                $thicknessCode = strtoupper(trim((string) $leather->getThickness()?->getName()));
            }

            $flayCode = strtoupper(trim((string) $leather->getFlay()?->getCode()));

            $code = $supplierCode . $typeCode . $speciesCode . $nationCode . $weightCode . $thicknessCode . $flayCode;

            $code = preg_replace('/[^A-Za-z0-9]/', '', $code);
            $leather->setCode($code);

            $errors = $validator->validate($leather);
            if (count($errors) > 0) {
                $errors = $this->validatorOutputFormatter->formatOutput($errors);
                return $this->doResponse->doErrorJsonResponse($errors);
            }

            $em = $this->doctrine;
            $em->persist($leather);
            $em->flush();

            $result = $this->groupSerializer->serializeGroup($leather, 'leather_detail');
            return new JsonResponse($this->doResponse->doResponse($result));

        } catch (\Exception $e) {
            return $this->doResponse->doErrorJsonResponse($e->getMessage());
        }
    }

    #[Route('/leather/{id}',
        name: 'delete_leather',
        methods: ['DELETE'])]
    public function deleteLeather(int $id): JsonResponse
    {
        $leather = $this->doctrine->getRepository(Leather::class)->find($id);
        if (!$leather) {
            return $this->doResponse->doErrorJsonResponse('Leather not found', 404);
        }

        $this->doctrine->remove($leather);
        $this->doctrine->flush();

        return new JsonResponse($this->doResponse->doResponse('delete_successfully'));
    }

    private function handleRelations(Leather $leather, array &$data): Leather
    {
        if (isset($data['weight_id'])) {
            $weight = $this->doctrine->getRepository(LeatherWeight::class)->find($data['weight_id']);
            if ($weight) {
                $leather->setWeight($weight);
            }
            unset($data['weight_id']);
        }

        if (isset($data['species_id'])) {
            $species = $this->doctrine->getRepository(LeatherSpecies::class)->find($data['species_id']);
            if ($species) {
                $leather->setSpecies($species);
            }
            unset($data['species_id']);
        }

        if (isset($data['contact_id'])) {
            $contact = $this->doctrine->getRepository(Contact::class)->find($data['contact_id']);
            if ($contact) {
                $leather->setContact($contact);
            }
            unset($data['contact_id']);
        }

        if (isset($data['thickness_id'])) {
            $thickness = $this->doctrine->getRepository(LeatherThickness::class)->find($data['thickness_id']);
            if ($thickness) {
                $leather->setThickness($thickness);
            }
            unset($data['thickness_id']);
        }

        if (isset($data['supplier_id'])) {
            $supplier = $this->doctrine->getRepository(Contact::class)->find($data['supplier_id']);
            if ($supplier) {
                $leather->setSupplier($supplier);
            }
            unset($data['supplier_id']);
        }

        if (isset($data['flay_id'])) {
            $flay = $this->doctrine->getRepository(LeatherFlay::class)->find($data['flay_id']);
            if ($flay) {
                $leather->setFlay($flay);
            }
            unset($data['flay_id']);
        }

        if (isset($data['provenance_id'])) {
            $provenance = $this->doctrine->getRepository(LeatherProvenance::class)->find($data['provenance_id']);
            if ($provenance) {
                $leather->setProvenance($provenance);
            }
            unset($data['provenance_id']);
        }

        if (isset($data['type_id'])) {
            $type = $this->doctrine->getRepository(LeatherType::class)->find($data['type_id']);
            if ($type) {
                $leather->setType($type);
            }
            unset($data['type_id']);
        }

        if (isset($data['status_id'])) {
            $status = $this->doctrine->getRepository(LeatherStatus::class)->find($data['status_id']);
            if ($status) {
                $leather->setStatus($status);
            }
            unset($data['status_id']);
        }

        return $leather;
    }
}


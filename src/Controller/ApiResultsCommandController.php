<?php

namespace App\Controller;

use App\Entity\Result;
use App\Utility\Utils;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class ApiResultsCommandController
 *
 * @package App\Controller
 *
 * @Route(
 *     path=ApiResultsQueryInterface::RUTA_API,
 *     name="api_results_"
 * )
 */
class ApiResultsCommandController extends AbstractController implements ApiResultsCommandInterface
{
    private const ROLE_ADMIN = 'ROLE_ADMIN';

    public function __construct(
        private readonly EntityManagerInterface $entityManager
    ) {
    }

    /**
     * @see ApiResultsCommandInterface::deleteAction()
     *
     * @Route(
     *     path="/{resultId}.{_format}",
     *     defaults={ "_format": null },
     *     requirements={
     *          "resultId": "\d+",
     *         "_format": "json|xml"
     *     },
     *     methods={ Request::METHOD_DELETE },
     *     name="delete"
     * )
     */
    public function deleteAction(Request $request, int $resultId): Response
    {
        $format = Utils::getFormat($request);
        if (!$this->isGranted('IS_AUTHENTICATED_FULLY')) {
            return Utils::errorMessage(
                Response::HTTP_UNAUTHORIZED,
                '`Unauthorized`: Invalid credentials.',
                $format
            );
        }

        if (!$this->isGranted(self::ROLE_ADMIN)) {
            return Utils::errorMessage(
                Response::HTTP_FORBIDDEN,
                '`Forbidden`: You don\'t have permission to access',
                $format
            );
        }

        /** @var Result $result */
        $result = $this->entityManager
            ->getRepository(Result::class)
            ->find($resultId);

        if (!$result instanceof Result) {
            return Utils::errorMessage(Response::HTTP_NOT_FOUND, null, $format);
        }

        $this->entityManager->remove($result);
        $this->entityManager->flush();

        return Utils::apiResponse(Response::HTTP_NO_CONTENT);
    }

    /**
     * @see ApiResultsCommandInterface::postAction()
     *
     * @Route(
     *     path=".{_format}",
     *     defaults={ "_format": null },
     *     requirements={
     *         "_format": "json|xml"
     *     },
     *     methods={ Request::METHOD_POST },
     *     name="post"
     * )
     */
    public function postAction(Request $request): Response
    {
        $format = Utils::getFormat($request);
        if (!$this->isGranted('IS_AUTHENTICATED_FULLY')) {
            return Utils::errorMessage(
                Response::HTTP_UNAUTHORIZED,
                '`Unauthorized`: Invalid credentials.',
                $format
            );
        }

        if (!$this->isGranted(self::ROLE_ADMIN)) {
            return Utils::errorMessage(
                Response::HTTP_FORBIDDEN,
                '`Forbidden`: You don\'t have permission to access',
                $format
            );
        }

        $postData = json_decode($request->getContent(), true);

        if (!isset($postData[Result::RESULT_ATTR], $postData[Result::TIME_ATTR], $postData[Result::USER_ATTR])) {
            return Utils::errorMessage(Response::HTTP_UNPROCESSABLE_ENTITY, null, $format);
        }

        $result = new Result(
            intval($postData[Result::RESULT_ATTR]),
            $this->entityManager->getRepository(User::class)->find($postData[Result::USER_ATTR]),
            new \DateTime($postData[Result::TIME_ATTR])
        );

        $this->entityManager->persist($result);
        $this->entityManager->flush();

        return Utils::apiResponse(
            Response::HTTP_CREATED,
            [Result::RESULT_ATTR => $result],
            $format,
            [
                'Location' => $request->getScheme() . '://' . $request->getHttpHost() .
                    ApiResultsQueryInterface::RUTA_API . '/' . $result->getId(),
            ]
        );
    }

    /**
     * @see ApiResultsCommandInterface::putAction()
     *
     * @Route(
     *     path="/{resultId}.{_format}",
     *     defaults={ "_format": null },
     *     requirements={
     *         "resultId": "\d+",
     *         "_format": "json|xml"
     *     },
     *     methods={ Request::METHOD_PUT },
     *     name="put"
     * )
     */
    public function putAction(Request $request, int $resultId): Response
    {
        $format = Utils::getFormat($request);
        if (!$this->isGranted('IS_AUTHENTICATED_FULLY')) {
            return Utils::errorMessage(
                Response::HTTP_UNAUTHORIZED,
                '`Unauthorized`: Invalid credentials.',
                $format
            );
        }

        if (!$this->isGranted(self::ROLE_ADMIN)) {
            return Utils::errorMessage(
                Response::HTTP_FORBIDDEN,
                '`Forbidden`: You don\'t have permission to access',
                $format
            );
        }

        /** @var Result $result */
        $result = $this->entityManager->getRepository(Result::class)->find($resultId);

        if (!$result) {
            return Utils::errorMessage(Response::HTTP_NOT_FOUND, null, $format);
        }

        $postData = json_decode($request->getContent(), true);

        if (isset($postData[Result::RESULT_ATTR])) {
            $result->setResult(intval($postData[Result::RESULT_ATTR]));
        }

        if (isset($postData[Result::TIME_ATTR])) {
            $result->setTimeFromString($postData[Result::TIME_ATTR]);
        }

        if (isset($postData[Result::USER_ATTR])) {
            $result->setUser($this->entityManager->getRepository(User::class)->find($postData[Result::USER_ATTR]));
        }

        $this->entityManager->flush();

        return Utils::apiResponse(
            209, // 209 - Content Returned
            [Result::RESULT_ATTR => $result],
            $format
        );
    }
}

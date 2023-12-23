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
 * Class ApiResultsQueryController
 *
 * @package App\Controller
 *
 * @Route(
 *     path=ApiResultsQueryInterface::RUTA_API,
 *     name="api_results_"
 * )
 */
class ApiResultsQueryController extends AbstractController implements ApiResultsQueryInterface
{
    private const HEADER_CACHE_CONTROL = 'Cache-Control';
    private const HEADER_ETAG = 'ETag';
    private const HEADER_ALLOW = 'Allow';

    public function __construct(
        private readonly EntityManagerInterface $entityManager
    ) {
    }

    /**
     * @see ApiResultsQueryInterface::cgetAction()
     *
     * @Route(
     *     path=".{_format}/{sort?time}",
     *     defaults={ "_format": "json", "sort": "time" },
     *     requirements={
     *         "sort": "time|result|user",
     *         "_format": "json|xml"
     *     },
     *     methods={ Request::METHOD_GET },
     *     name="cget"
     * )
     */
    public function cgetAction(Request $request): Response
    {
        $format = Utils::getFormat($request);
        $order = strval($request->get('sort'));
        $results = $this->entityManager
            ->getRepository(Result::class)
            ->findBy([], [$order => 'ASC']);

        if (empty($results)) {
            return Utils::errorMessage(Response::HTTP_NOT_FOUND, null, $format);
        }

        $etag = md5((string) json_encode($results, JSON_THROW_ON_ERROR));
        if (($etags = $request->getETags()) && (in_array($etag, $etags) || in_array('*', $etags))) {
            return new Response(null, Response::HTTP_NOT_MODIFIED);
        }

        return Utils::apiResponse(
            Response::HTTP_OK,
            ['results' => $results],
            $format,
            [
                self::HEADER_CACHE_CONTROL => 'private',
                self::HEADER_ETAG => $etag,
            ]
        );
    }

    /**
     * @see ApiResultsQueryInterface::getAction()
     *
     * @Route(
     *     path="/{resultId}.{_format}",
     *     defaults={ "_format": null },
     *     requirements={
     *          "resultId": "\d+",
     *          "_format": "json|xml"
     *     },
     *     methods={ Request::METHOD_GET },
     *     name="get"
     * )
     */
    public function getAction(Request $request, int $resultId): Response
    {
        $format = Utils::getFormat($request);
        if (!$this->isGranted('IS_AUTHENTICATED_FULLY')) {
            return Utils::errorMessage( // 401
                Response::HTTP_UNAUTHORIZED,
                '`Unauthorized`: Invalid credentials.',
                $format
            );
        }
        $result = $this->entityManager
            ->getRepository(Result::class)
            ->find($resultId);

        if (!$result instanceof Result) {
            return Utils::errorMessage(Response::HTTP_NOT_FOUND, null, $format);
        }

        $etag = md5(json_encode($result, JSON_THROW_ON_ERROR));
        if (($etags = $request->getETags()) && (in_array($etag, $etags) || in_array('*', $etags))) {
            return new Response(null, Response::HTTP_NOT_MODIFIED);
        }

        return Utils::apiResponse(
            Response::HTTP_OK,
            [Result::RESULT_ATTR => $result],
            $format,
            [
                self::HEADER_CACHE_CONTROL => 'private',
                self::HEADER_ETAG => $etag,
            ]
        );
    }

    /**
     * @see ApiResultsQueryInterface::optionsAction()
     *
     * @Route(
     *     path="/{resultId}.{_format}",
     *     defaults={ "resultId" = 0, "_format": "json" },
     *     requirements={
     *          "resultId": "\d+",
     *         "_format": "json|xml"
     *     },
     *     methods={ Request::METHOD_OPTIONS },
     *     name="options"
     * )
     */
    public function optionsAction(int|null $resultId): Response
    {
        $methods = $resultId !== 0
            ? [Request::METHOD_GET, Request::METHOD_PUT, Request::METHOD_DELETE]
            : [Request::METHOD_GET, Request::METHOD_POST];
        $methods[] = Request::METHOD_OPTIONS;

        return new Response(
            null,
            Response::HTTP_NO_CONTENT,
            [
                self::HEADER_ALLOW => implode(',', $methods),
                self::HEADER_CACHE_CONTROL => 'public, immutable'
            ]
        );
    }
}

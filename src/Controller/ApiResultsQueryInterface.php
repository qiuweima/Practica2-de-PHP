<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Interface ApiResultsQueryInterface
 *
 * @package App\Controller
 */
interface ApiResultsQueryInterface
{
    public final const RUTA_API = '/api/v1/results';
    /**
     * Retrieves a collection of Result entities.
     *
     * @param Request $request
     * @return Response
     */
    public function cgetAction(Request $request): Response;

    /**
     * Retrieves a single Result entity.
     *
     * @param Request $request
     * @param int $resultId
     * @return Response
     */
    public function getAction(Request $request, int $resultId): Response;

    /**
     * Provides HTTP methods that are allowed for a specific Result entity or for the collection.
     *
     * @param int|null $resultId
     * @return Response
     */
    public function optionsAction(int|null $resultId): Response;
}

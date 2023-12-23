<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Interface ApiResultsCommandInterface
 *
 * @package App\Controller
 */
interface ApiResultsCommandInterface
{
    /**
     * Deletes a Result entity.
     *
     * @param Request $request
     * @param int $resultId
     * @return Response
     */
    public function deleteAction(Request $request, int $resultId): Response;

    /**
     * Creates a new Result entity.
     *
     * @param Request $request
     * @return Response
     */
    public function postAction(Request $request): Response;

    /**
     * Updates an existing Result entity.
     *
     * @param Request $request
     * @param int $resultId
     * @return Response
     */
    public function putAction(Request $request, int $resultId): Response;
}

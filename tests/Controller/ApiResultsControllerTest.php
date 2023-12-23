<?php

namespace App\Tests\Controller;

use App\Entity\Result;
use App\Entity\User;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class ApiResultsControllerTest
 *
 * @package App\Tests\Controller
 * @group controllers
 *
 * @coversDefaultClass \App\Controller\ApiResultsCommandController
 * @coversDefaultClass \App\Controller\ApiResultsQueryController
 */
class ApiResultsControllerTest extends BaseTestCase
{
    private const RUTA_API = '/api/v1/results';

    /** @var array<string,string> $adminHeaders */
    private static array $adminHeaders;
    private array $roleAdminHeaders;
    private array $roleUserHeaders;

    /**
     * Test OPTIONS /results[/resultId] 204 No Content
     *
     * @covers ::optionsAction
     */
    protected function setUp(): void
    {
        $this->roleUserHeaders = self::getTokenHeaders(self::$role_user[User::EMAIL_ATTR],self::$role_user[User::PASSWD_ATTR]);
        $this->roleAdminHeaders = self::getTokenHeaders(self::$role_admin[User::EMAIL_ATTR],self::$role_admin[User::PASSWD_ATTR]);
    }
    public function testOptionsResultAction204NoContent(): void
    {
        self::$client->request(
            Request::METHOD_OPTIONS,
            self::RUTA_API
        );
        $response = self::$client->getResponse();

        self::assertSame(
            Response::HTTP_NO_CONTENT,
            $response->getStatusCode()
        );
        self::assertNotEmpty($response->headers->get('Allow'));

        self::$client->request(
            Request::METHOD_OPTIONS,
            self::RUTA_API . '/' . self::$faker->numberBetween(1, 100)
        );
        self::assertSame(
            Response::HTTP_NO_CONTENT,
            $response->getStatusCode()
        );
        self::assertNotEmpty($response->headers->get('Allow'));
    }

    // Add more test methods for GET, POST, PUT, DELETE etc.

    // Example: Test POST /results 201 Created
    // Example: Test GET /results 200 OK
    // Example: Test PUT /results/{resultId} 209 Content Returned
    // Example: Test DELETE /results/{resultId} 204 No Content

    public function testPostResultAction201Created(): void
    {
        $p_data = [
            Result::RESULT_ATTR => self::$faker->numberBetween(1, 100),
            Result::TIME_ATTR => self::$faker->dateTime()->format('Y-m-d H:i:s'),
            // Assume that Result::USER_ATTR needs a user email
            Result::USER_ATTR => self::$faker->email(),
        ];

        self::$adminHeaders = $this->getTokenHeaders(
            self::$role_admin[User::EMAIL_ATTR],
            self::$role_admin[User::PASSWD_ATTR]
        );

        self::$client->request(
            Request::METHOD_POST,
            self::RUTA_API,
            [],
            [],
            self::$adminHeaders,
            json_encode($p_data)
        );
        $response = self::$client->getResponse();

        self::assertSame(Response::HTTP_CREATED, $response->getStatusCode());
        self::assertTrue($response->isSuccessful());
        self::assertNotNull($response->headers->get('Location'));
        self::assertJson((string) $response->getContent());
        $resultData = json_decode((string) $response->getContent(), true);
        self::assertNotEmpty($resultData['id']);
        self::assertSame($p_data[Result::RESULT_ATTR], $resultData[Result::RESULT_ATTR]);
    }

    /**
     * Test GET /results 200 OK
     */
    public function testCGetResultsAction200Ok(): void
    {
        self::$client->request(Request::METHOD_GET, self::RUTA_API, [], [], self::$adminHeaders);
        $response = self::$client->getResponse();

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertTrue($response->isSuccessful());
        self::assertJson((string) $response->getContent());
        $results = json_decode((string) $response->getContent(), true);
        self::assertArrayHasKey('results', $results);
    }

    /**
     * Test PUT /results/{resultId} 209 Content Returned
     */
    public function testPutResultAction209ContentReturned(): void
    {
        $existingResultId = 1;
        $p_data = [
            Result::RESULT_ATTR => self::$faker->numberBetween(101, 200),
            // Other attributes can be added here
        ];

        self::$client->request(
            Request::METHOD_PUT,
            self::RUTA_API . '/' . $existingResultId,
            [],
            [],
            self::$adminHeaders,
            json_encode($p_data)
        );
        $response = self::$client->getResponse();

        self::assertSame(209, $response->getStatusCode());
        self::assertTrue($response->isSuccessful());
        self::assertJson((string) $response->getContent());
        $resultData = json_decode((string) $response->getContent(), true);
        self::assertSame($existingResultId, $resultData['id']);
        self::assertSame($p_data[Result::RESULT_ATTR], $resultData[Result::RESULT_ATTR]);
    }

    /**
     * Test DELETE /results/{resultId} 204 No Content
     */
    public function testDeleteResultAction204NoContent(): void
    {
        $existingResultId = 1;

        self::$client->request(
            Request::METHOD_DELETE,
            self::RUTA_API . '/' . $existingResultId,
            [],
            [],
            self::$adminHeaders
        );
        $response = self::$client->getResponse();

        self::assertSame(Response::HTTP_NO_CONTENT, $response->getStatusCode());
        self::assertEmpty((string) $response->getContent());
    }

    public function testPostResultAction400BadRequest(): void
    {
        $p_data = [
            Result::RESULT_ATTR => null,
        ];

        self::$client->request(
            Request::METHOD_POST,
            self::RUTA_API,
            [],
            [],
            self::$adminHeaders,
            json_encode($p_data)
        );
        $response = self::$client->getResponse();

        self::assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
    }

    public function testPutResultAction400BadRequest(): void
    {
        $resultId = 1;
        $invalidData = [
            Result::RESULT_ATTR => 'invalid-data',
        ];

        self::$client->request(
            Request::METHOD_PUT,
            self::RUTA_API . '/' . $resultId,
            [],
            [],
            $this->roleAdminHeaders,
            json_encode($invalidData)
        );
        $response = self::$client->getResponse();

        self::assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
    }

    /**
     * Test GET /results/{resultId} 404 Not Found
     */
    public function testGetResultAction404NotFound(): void
    {
        $nonExistentResultId = 999;

        self::$client->request(
            Request::METHOD_GET,
            self::RUTA_API . '/' . $nonExistentResultId,
            [],
            [],
            self::$adminHeaders
        );
        $response = self::$client->getResponse();

        self::assertSame(Response::HTTP_NOT_FOUND, $response->getStatusCode());
    }
    public function testPutResultAction404NotFound(): void
    {
        $nonExistentResultId = 9999;

        self::$client->request(
            Request::METHOD_PUT,
            self::RUTA_API . '/' . $nonExistentResultId,
            [],
            [],
            $this->roleAdminHeaders
        );
        $response = self::$client->getResponse();

        self::assertSame(Response::HTTP_NOT_FOUND, $response->getStatusCode());
    }

    /**
     * Test DELETE /results/{resultId} for 404 Not Found
     */
    public function testDeleteResultAction404NotFound(): void
    {
        $nonExistentResultId = 9999;

        self::$client->request(
            Request::METHOD_DELETE,
            self::RUTA_API . '/' . $nonExistentResultId,
            [],
            [],
            $this->roleAdminHeaders
        );
        $response = self::$client->getResponse();

        self::assertSame(Response::HTTP_NOT_FOUND, $response->getStatusCode());
    }
    /**
     * Test DELETE /results/{resultId} 403 Forbidden
     *
     * Assuming non-admin user cannot delete results
     */
    public function testDeleteResultAction403Forbidden(): void
    {
        $existingResultId = 1; 
        $nonAdminHeaders = $this->getTokenHeaders(
            self::$role_user[User::EMAIL_ATTR],
            self::$role_user[User::PASSWD_ATTR]
        );

        self::$client->request(
            Request::METHOD_DELETE,
            self::RUTA_API . '/' . $existingResultId,
            [],
            [],
            $nonAdminHeaders
        );
        $response = self::$client->getResponse();

        self::assertSame(Response::HTTP_FORBIDDEN, $response->getStatusCode());
    }
    public function testGetResultAction403Forbidden(): void
    {
        $resultId = 1;

        self::$client->request(
            Request::METHOD_GET,
            self::RUTA_API . '/' . $resultId,
            [],
            [],
            $this->roleUserHeaders
        );
        $response = self::$client->getResponse();

        self::assertSame(Response::HTTP_FORBIDDEN, $response->getStatusCode());
    }

    public function testPutResultAction403Forbidden(): void
    {
        $resultId = 1;
        $validData = [
            Result::RESULT_ATTR => 100,
            Result::TIME_ATTR => '2023-12-12 10:12:12'
        ];

        self::$client->request(
            Request::METHOD_PUT,
            self::RUTA_API . '/' . $resultId,
            [],
            [],
            $this->roleUserHeaders,
            json_encode($validData)
        );
        $response = self::$client->getResponse();

        self::assertSame(Response::HTTP_FORBIDDEN, $response->getStatusCode());
    }
    public function testPostResultAction403Forbidden(): void
    {
        $validData = [
            Result::RESULT_ATTR => 100,
            Result::TIME_ATTR => '2023-12-12 10:12:12',
            Result::USER_ATTR => self::$role_admin[User::EMAIL_ATTR],
        ];


        self::$client->request(
            Request::METHOD_POST,
            self::RUTA_API,
            [],
            [],
            $this->roleUserHeaders,
            json_encode($validData)
        );
        $response = self::$client->getResponse();

        self::assertSame(Response::HTTP_FORBIDDEN, $response->getStatusCode());
    }
    public function testPutResultAction401Unauthorized(): void
    {
        $existingResultId = 1;
        $p_data = [
            Result::RESULT_ATTR => self::$faker->numberBetween(101, 200),
        ];

        self::$client->request(
            Request::METHOD_PUT,
            self::RUTA_API . '/' . $existingResultId,
            [],
            [],
            [],
            json_encode($p_data)
        );
        $response = self::$client->getResponse();

        self::assertSame(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
    }
    public function testPostResultAction401Unauthorized(): void
    {
        $validData = [
            Result::RESULT_ATTR => 100,
            Result::TIME_ATTR => '2023-12-22 12:00:00',
            Result::USER_ATTR => self::$role_admin[User::EMAIL_ATTR],
        ];

        self::$client->request(
            Request::METHOD_POST,
            self::RUTA_API,
            [],
            [],
            [],
            json_encode($validData)
        );
        $response = self::$client->getResponse();

        self::assertSame(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
    }
    public function testDeleteResultAction401Unauthorized(): void
    {
        $resultId = 1;

        self::$client->request(
            Request::METHOD_DELETE,
            self::RUTA_API . '/' . $resultId,
            [],
            [],
            []
        );
        $response = self::$client->getResponse();

        self::assertSame(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
    }

    public function testCGetResultsAction412PreconditionFailed(): void
    {

        self::$client->request(
            Request::METHOD_GET,
            self::RUTA_API,
            [],
            [],
            array_merge(
                $this->roleAdminHeaders,
                ['Some-Required-Condition' => 'Missing or Invalid']
            )
        );
        $response = self::$client->getResponse();

        self::assertSame(Response::HTTP_PRECONDITION_FAILED, $response->getStatusCode());
    }
    public function testPutResultAction412PreconditionFailed(): void
    {
        $existingResultId = 1;
        $p_data = [
            Result::RESULT_ATTR => self::$faker->numberBetween(101, 200),
        ];

        // Missing ETag header
        self::$client->request(
            Request::METHOD_PUT,
            self::RUTA_API . '/' . $existingResultId,
            [],
            [],
            self::$adminHeaders,
            json_encode($p_data)
        );
        $response = self::$client->getResponse();

        self::assertSame(Response::HTTP_PRECONDITION_FAILED, $response->getStatusCode());
    }
    public function testPostResultAction412PreconditionFailed(): void
    {
        $validData = [
            Result::RESULT_ATTR => 100,
            Result::TIME_ATTR => '2023-12-12 10:12:12',
            Result::USER_ATTR => self::$role_admin[User::EMAIL_ATTR],
        ];


        self::$client->request(
            Request::METHOD_POST,
            self::RUTA_API,
            [],
            [],
            array_merge(
                $this->roleAdminHeaders,
                ['Some-Required-Header' => 'Missing or Invalid Value']
            ),
            json_encode($validData)
        );
        $response = self::$client->getResponse();

        self::assertSame(Response::HTTP_PRECONDITION_FAILED, $response->getStatusCode());
    }
    public function testDeleteResultAction412PreconditionFailed(): void
    {
        $resultId = 1;

        self::$client->request(
            Request::METHOD_DELETE,
            self::RUTA_API . '/' . $resultId,
            [],
            [],
            array_merge(
                $this->roleAdminHeaders,
                ['HTTP_If-Match' => 'outdated-etag']
            )
        );
        $response = self::$client->getResponse();

        self::assertSame(Response::HTTP_PRECONDITION_FAILED, $response->getStatusCode());
    }

    public function testPutResult422(): void
    {
        $resultId = 1;
        $invalidData = [
            Result::RESULT_ATTR => 'invalid-data',

        ];

        self::$client->request(
            Request::METHOD_PUT,
            self::RUTA_API . '/' . $resultId,
            [],
            [],
            $this->roleAdminHeaders,
            json_encode($invalidData)
        );
        $response = self::$client->getResponse();

        self::assertSame(Response::HTTP_UNPROCESSABLE_ENTITY, $response->getStatusCode());
    }
    public function testPostResult422(): void
    {
        $invalidData = [
            // Invalid or incomplete data that causes the entity to be unprocessable
            Result::RESULT_ATTR => 'invalid-data-type',
            // Missing Result::TIME_ATTR
        ];

        self::$client->request(
            Request::METHOD_POST,
            self::RUTA_API,
            [],
            [],
            $this->roleAdminHeaders,
            json_encode($invalidData)
        );
        $response = self::$client->getResponse();

        self::assertSame(Response::HTTP_UNPROCESSABLE_ENTITY, $response->getStatusCode());
    }



}


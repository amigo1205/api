<?php

namespace Directus\Api\Routes;

use Directus\Application\Application;
use Directus\Application\Http\Request;
use Directus\Application\Http\Response;
use Directus\Application\Route;
use Directus\Database\Schema\SchemaManager;
use Directus\Exception\Exception;
use Directus\Filesystem\Exception\FailedUploadException;
use Directus\Services\FilesServices;
use Directus\Services\RevisionsService;
use Directus\Util\ArrayUtils;
use Slim\Http\UploadedFile;

class Files extends Route
{
    /**
     * @param Application $app
     */
    public function __invoke(Application $app)
    {
        $app->post('', [$this, 'create']);
        $app->get('/{id:[0-9]+}', [$this, 'read']);
        $app->patch('/{id:[0-9]+}', [$this, 'update']);
        $app->delete('/{id:[0-9]+}', [$this, 'delete']);
        $app->get('', [$this, 'all']);

        // Folders
        $controller = $this;
        $app->group('/folders', function () use ($controller) {
            $this->post('', [$controller, 'createFolder']);
            $this->get('/{id:[0-9]+}', [$controller, 'readFolder']);
            $this->patch('/{id:[0-9]+}', [$controller, 'updateFolder']);
            $this->delete('/{id:[0-9]+}', [$controller, 'deleteFolder']);
            $this->get('', [$controller, 'allFolder']);
        });

        // Revisions
        $app->get('/{id}/revisions', [$this, 'fileRevisions']);
        $app->get('/{id}/revisions/{offset}', [$this, 'oneFileRevision']);
    }

    /**
     * @param Request $request
     * @param Response $response
     *
     * @return Response
     *
     * @throws Exception
     */
    public function create(Request $request, Response $response)
    {
        $this->validateRequestPayload($request);
        $service = new FilesServices($this->container);
        $uploadedFiles = $request->getUploadedFiles();
        $payload = $request->getParsedBody();

        if (!empty($uploadedFiles)) {
            /** @var UploadedFile $uploadedFile */
            $uploadedFile = array_shift($uploadedFiles);
            if (!\Directus\is_uploaded_file_okay($uploadedFile->getError())) {
                throw new FailedUploadException($uploadedFile->getError());
            }

            if (empty($payload)) {
                $payload = [];
            }

            // TODO: the file already exists move it to the upload path location
            $data = file_get_contents($uploadedFile->file);
            $payload = array_merge([
                'filename' => $uploadedFile->getClientFilename(),
                'type' => $uploadedFile->getClientMediaType(),
                'data' => base64_encode($data)
            ], $payload);
        }

        $responseData = $service->create(
            $payload,
            $request->getQueryParams()
        );

        return $this->responseWithData($request, $response, $responseData);
    }

    /**
     * @param Request $request
     * @param Response $response
     *
     * @return Response
     */
    public function read(Request $request, Response $response)
    {
        $service = new FilesServices($this->container);
        $responseData = $service->findByIds(
            $request->getAttribute('id'),
            ArrayUtils::pick($request->getParams(), ['fields', 'meta'])
        );

        return $this->responseWithData($request, $response, $responseData);
    }

    /**
     * @param Request $request
     * @param Response $response
     *
     * @return Response
     */
    public function update(Request $request, Response $response)
    {
        $this->validateRequestPayload($request);
        $service = new FilesServices($this->container);
        $responseData = $service->update(
            $request->getAttribute('id'),
            $request->getParsedBody(),
            $request->getQueryParams()
        );

        return $this->responseWithData($request, $response, $responseData);
    }

    public function delete(Request $request, Response $response)
    {
        $service = new FilesServices($this->container);
        $service->delete(
            $request->getAttribute('id'),
            $request->getQueryParams()
        );

        $response = $response->withStatus(204);

        return $this->responseWithData($request, $response, []);
    }

    /**
     * @param Request $request
     * @param Response $response
     *
     * @return Response
     */
    public function all(Request $request, Response $response)
    {
        $service = new FilesServices($this->container);
        $responseData = $service->findAll($request->getQueryParams());

        return $this->responseWithData($request, $response, $responseData);
    }

    /**
     * @param Request $request
     * @param Response $response
     *
     * @return Response
     */
    public function createFolder(Request $request, Response $response)
    {
        $this->validateRequestPayload($request);
        $service = new FilesServices($this->container);
        $responseData = $service->createFolder(
            $request->getParsedBody(),
            $request->getQueryParams()
        );

        return $this->responseWithData($request, $response, $responseData);
    }

    /**
     * @param Request $request
     * @param Response $response
     *
     * @return Response
     */
    public function readFolder(Request $request, Response $response)
    {
        $service = new FilesServices($this->container);
        $responseData = $service->findFolderByIds(
            $request->getAttribute('id'),
            ArrayUtils::pick($request->getQueryParams(), ['fields', 'meta'])
        );

        return $this->responseWithData($request, $response, $responseData);
    }

    /**
     * @param Request $request
     * @param Response $response
     *
     * @return Response
     */
    public function updateFolder(Request $request, Response $response)
    {
        $this->validateRequestPayload($request);
        $service = new FilesServices($this->container);
        $responseData = $service->updateFolder(
            $request->getAttribute('id'),
            $request->getParsedBody(),
            $request->getQueryParams()
        );

        return $this->responseWithData($request, $response, $responseData);
    }

    /**
     * @param Request $request
     * @param Response $response
     *
     * @return Response
     */
    public function allFolder(Request $request, Response $response)
    {
        $service = new FilesServices($this->container);
        $responseData = $service->findAllFolders(
            $request->getQueryParams()
        );

        return $this->responseWithData($request, $response, $responseData);
    }

    /**
     * @param Request $request
     * @param Response $response
     *
     * @return Response
     */
    public function deleteFolder(Request $request, Response $response)
    {
        $service = new FilesServices($this->container);
        $service->deleteFolder(
            $request->getAttribute('id'),
            $request->getQueryParams()
        );

        $response = $response->withStatus(204);

        return $this->responseWithData($request, $response, []);
    }

    /**
     * @param Request $request
     * @param Response $response
     *
     * @return Response
     */
    public function fileRevisions(Request $request, Response $response)
    {
        $service = new RevisionsService($this->container);
        $responseData = $service->findAllByItem(
            SchemaManager::COLLECTION_FILES,
            $request->getAttribute('id'),
            $request->getQueryParams()
        );

        return $this->responseWithData($request, $response, $responseData);
    }

    /**
     * @param Request $request
     * @param Response $response
     *
     * @return Response
     */
    public function oneFileRevision(Request $request, Response $response)
    {
        $service = new RevisionsService($this->container);
        $responseData = $service->findOneByItemOffset(
            SchemaManager::COLLECTION_FILES,
            $request->getAttribute('id'),
            $request->getAttribute('offset'),
            $request->getQueryParams()
        );

        return $this->responseWithData($request, $response, $responseData);
    }

    /**
     * @param Request $request
     * @param Response $response
     *
     * @return Response
     */
    public function fileRevert(Request $request, Response $response)
    {
        $service = new RevisionsService($this->container);
        $responseData = $service->revert(
            SchemaManager::COLLECTION_FILES,
            $request->getAttribute('id'),
            $request->getAttribute('revision'),
            $request->getQueryParams()
        );

        return $this->responseWithData($request, $response, $responseData);
    }
}

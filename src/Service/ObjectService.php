<?php
declare(strict_types=1);

namespace InvoiceNinjaModule\Service;

use InvoiceNinjaModule\Exception\ApiAuthException;
use InvoiceNinjaModule\Exception\EmptyResponseException;
use InvoiceNinjaModule\Exception\HttpClientAuthException;
use InvoiceNinjaModule\Exception\InvalidParameterException;
use InvoiceNinjaModule\Exception\InvalidResultException;
use InvoiceNinjaModule\Exception\NotFoundException;
use InvoiceNinjaModule\Model\Interfaces\BaseInterface;
use InvoiceNinjaModule\Options\RequestOptions;
use InvoiceNinjaModule\Service\Interfaces\ObjectServiceInterface;
use InvoiceNinjaModule\Service\Interfaces\RequestServiceInterface;
use Laminas\Http\Request;
use Laminas\Hydrator\HydratorInterface;

/**
 * Class ObjectService
 */
final class ObjectService implements ObjectServiceInterface
{
    protected RequestServiceInterface $requestService;
    protected HydratorInterface $hydrator;
    protected BaseInterface $objectType;

    /**
     * BaseManager constructor.
     *
     * @param RequestServiceInterface $requestService
     * @param HydratorInterface   $hydrator
     */
    public function __construct(RequestServiceInterface $requestService, HydratorInterface $hydrator)
    {
        $this->requestService = $requestService;
        $this->hydrator       = $hydrator;
    }

    /**
     * @param BaseInterface $object
     * @param string        $reqRoute
     *
     * @return BaseInterface
     * @throws ApiAuthException
     * @throws EmptyResponseException
     * @throws HttpClientAuthException
     * @throws InvalidResultException
     */
    public function createObject(BaseInterface $object, string $reqRoute) :BaseInterface
    {
        $reqOptions = new RequestOptions();
        $reqOptions->addPostParameters($this->hydrator->extract($object));
        $responseArr = $this->requestService->dispatchRequest(Request::METHOD_POST, $reqRoute, $reqOptions);
        return $this->hydrateObject($responseArr, $object);
    }

    /**
     * @param BaseInterface $object
     * @param string        $id
     * @param string        $reqRoute
     *
     * @return BaseInterface
     * @throws InvalidResultException
     * @throws NotFoundException
     * @throws HttpClientAuthException
     * @throws ApiAuthException
     */
    public function getObjectById(BaseInterface $object, string $id, string $reqRoute) :BaseInterface
    {
        $requestOptions = new RequestOptions();

        try {
            $responseArr = $this->requestService->dispatchRequest(
                Request::METHOD_GET,
                $reqRoute.'/'.$id,
                $requestOptions
            );
        } catch (EmptyResponseException $e) {
            throw new NotFoundException($id);
        }
        return $this->hydrateObject($responseArr, $object);
    }

    /**
     * @param BaseInterface $object
     * @param array         $searchTerm
     * @param string        $reqRoute
     *
     * @return BaseInterface[]
     * @throws InvalidParameterException
     * @throws InvalidResultException
     * @throws HttpClientAuthException
     * @throws ApiAuthException
     */
    public function findObjectBy(BaseInterface $object, array $searchTerm, string $reqRoute) :array
    {
        $resultArr = [];
        $reqOptions = new RequestOptions();

        if (empty($searchTerm)) {
            throw new InvalidParameterException('searchTerm must not be empty');
        }
        $reqOptions->addQueryParameters($searchTerm);

        try {
            $responseArr = $this->requestService->dispatchRequest(Request::METHOD_GET, $reqRoute, $reqOptions);

            foreach ($responseArr as $objectArr) {
                $resultArr[] = $this->hydrateObject($objectArr, clone $object);
            }
        } catch (EmptyResponseException $e) {
            return $resultArr;
        }
        return $resultArr;
    }

    /**
     * @param BaseInterface $object
     * @param string        $reqRoute
     *
     * @return BaseInterface
     * @throws EmptyResponseException
     * @throws InvalidResultException
     * @throws HttpClientAuthException
     * @throws ApiAuthException
     */
    public function restoreObject(BaseInterface $object, string $reqRoute) :BaseInterface
    {
        return $this->update($object, $reqRoute, ObjectServiceInterface::ACTION_RESTORE);
    }

    /**
     * @param BaseInterface $object
     * @param string        $reqRoute
     *
     * @return BaseInterface
     * @throws EmptyResponseException
     * @throws InvalidResultException
     * @throws HttpClientAuthException
     * @throws ApiAuthException
     */
    public function archiveObject(BaseInterface $object, string $reqRoute) :BaseInterface
    {
        return $this->update($object, $reqRoute, ObjectServiceInterface::ACTION_ARCHIVE);
    }

    /**
     * @param BaseInterface $object
     * @param string        $reqRoute
     *
     * @return BaseInterface
     * @throws EmptyResponseException
     * @throws InvalidResultException
     * @throws HttpClientAuthException
     * @throws ApiAuthException
     */
    public function updateObject(BaseInterface $object, string $reqRoute) :BaseInterface
    {
        return $this->update($object, $reqRoute);
    }

    /**
     * @param BaseInterface $object
     * @param string        $reqRoute
     * @param string|null   $action
     *
     * @return BaseInterface
     * @throws EmptyResponseException
     * @throws InvalidResultException
     * @throws HttpClientAuthException
     * @throws ApiAuthException
     */
    private function update(BaseInterface $object, string $reqRoute, ?string $action = null) :BaseInterface
    {
        $reqOptions = new RequestOptions();

        if ($action !== null) {
            $reqOptions->addQueryParameters(['action' => $action]);
        } else {
            $reqOptions->addPostParameters($this->hydrator->extract($object));
        }

        $responseArr = $this->requestService->dispatchRequest(
            Request::METHOD_PUT,
            $reqRoute.'/'.$object->getId(),
            $reqOptions
        );
        return $this->hydrateObject($responseArr, $object);
    }

    /**
     * @param BaseInterface $object
     * @param string        $reqRoute
     *
     * @return BaseInterface
     * @throws EmptyResponseException
     * @throws InvalidResultException
     * @throws HttpClientAuthException
     * @throws ApiAuthException
     */
    public function deleteObject(BaseInterface $object, string $reqRoute) :BaseInterface
    {
        $responseArr = $this->requestService->dispatchRequest(
            Request::METHOD_DELETE,
            $reqRoute.'/'.$object->getId(),
            new RequestOptions()
        );
        return $this->hydrateObject($responseArr, $object);
    }

    /**
     * Retrieves all objects.
     * Default page size on server side is 15!
     *
     * @param BaseInterface $object
     * @param string        $reqRoute
     * @param int           $page
     * @param int           $pageSize
     *
     * @return BaseInterface[]
     * @throws EmptyResponseException
     * @throws InvalidResultException
     * @throws HttpClientAuthException
     * @throws ApiAuthException
     */
    public function getAllObjects(BaseInterface $object, string $reqRoute, int $page = 1, int $pageSize = 0) :array
    {
        $reqOptions = new RequestOptions();
        $reqOptions->setPage($page);
        $reqOptions->setPageSize($pageSize);

        $responseArr = $this->requestService->dispatchRequest(Request::METHOD_GET, $reqRoute, $reqOptions);

        $result = [];
        foreach ($responseArr as $clientData) {
            $result[] = $this->hydrateObject($clientData, clone $object);
        }
        return $result;
    }

    /**
     * @param string $invitationKey
     * @param string $topic
     *
     * @return array
     * @throws ApiAuthException
     * @throws EmptyResponseException
     * @throws HttpClientAuthException
     */
    public function downloadFile(string $invitationKey, string $topic) :array
    {
        return $this->requestService->dispatchRequest(
            Request::METHOD_GET,
            '/'.$topic.'/'.$invitationKey.'/download',
            new RequestOptions()
        );
    }

    /**
     * @param string $command
     * @param array  $body
     *
     * @throws EmptyResponseException
     * @throws ApiAuthException
     * @throws HttpClientAuthException
     */
    public function sendCommand(string $command, array $body) :void
    {
        $reqOptions = new RequestOptions();
        $reqOptions->addPostParameters($body);
        $this->requestService->dispatchRequest(
            Request::METHOD_POST,
            '/'.$command,
            $reqOptions
        );
    }

    /**
     * @param array         $data
     * @param BaseInterface $object
     *
     * @return BaseInterface
     * @throws InvalidResultException
     */
    private function hydrateObject(array $data, BaseInterface $object) :BaseInterface
    {
        $result = $this->hydrator->hydrate($data, $object);
        if ($result instanceof BaseInterface) {
            return $result;
        }
        throw new InvalidResultException();
    }
}

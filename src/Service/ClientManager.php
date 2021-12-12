<?php
declare(strict_types=1);

namespace InvoiceNinjaModule\Service;

use InvoiceNinjaModule\Exception\ApiAuthException;
use InvoiceNinjaModule\Exception\EmptyResponseException;
use InvoiceNinjaModule\Exception\HttpClientAuthException;
use InvoiceNinjaModule\Exception\InvalidParameterException;
use InvoiceNinjaModule\Exception\InvalidResultException;
use InvoiceNinjaModule\Exception\NotFoundException;
use InvoiceNinjaModule\Model\Client;
use InvoiceNinjaModule\Model\Interfaces\BaseInterface;
use InvoiceNinjaModule\Model\Interfaces\ClientInterface;
use InvoiceNinjaModule\Service\Interfaces\ClientManagerInterface;
use InvoiceNinjaModule\Service\Interfaces\ObjectServiceInterface;

/**
 * Class ClientManager
 */
final class ClientManager implements ClientManagerInterface
{
    private ObjectServiceInterface $objectManager;
    private string $reqRoute;
    private Client $objectType;

    /**
     * ClientManager constructor.
     *
     * @param ObjectServiceInterface $objectManager
     */
    public function __construct(ObjectServiceInterface $objectManager)
    {
        $this->reqRoute = '/clients';
        $this->objectManager = $objectManager;
        $this->objectType  = new Client();
    }

    /**
     * @param ClientInterface $client
     *
     * @return ClientInterface
     * @throws InvalidResultException
     * @throws EmptyResponseException
     * @throws HttpClientAuthException
     * @throws ApiAuthException
     */
    public function createClient(ClientInterface $client) :ClientInterface
    {
        //@TODO: Client needs at least one contact!!
        /*
                    $contact = new Contact();
                    $contact->setEmail('test@test.de');
                    $contact->setFirstName('dddd');
                    $contact->setLastName('XXXXXX');
                    $contact->setPrimary(true);
                    $contact->setSendInvoice(true); */
        return $this->checkResult($this->objectManager->createObject($client, $this->reqRoute));
    }

    /**
     * @param ClientInterface $client
     *
     * @return ClientInterface
     * @throws InvalidResultException
     * @throws EmptyResponseException
     * @throws HttpClientAuthException
     * @throws ApiAuthException
     */
    public function delete(ClientInterface $client) :ClientInterface
    {
        return $this->checkResult($this->objectManager->deleteObject($client, $this->reqRoute));
    }

    /**
     * @param ClientInterface $client
     *
     * @return ClientInterface
     * @throws InvalidResultException
     * @throws EmptyResponseException
     * @throws HttpClientAuthException
     * @throws ApiAuthException
     */
    public function update(ClientInterface $client) :ClientInterface
    {
        return $this->checkResult($this->objectManager->updateObject($client, $this->reqRoute));
    }

    /**
     * @param ClientInterface $client
     *
     * @return ClientInterface
     * @throws InvalidResultException
     * @throws EmptyResponseException
     * @throws HttpClientAuthException
     * @throws ApiAuthException
     */
    public function restore(ClientInterface $client) :ClientInterface
    {
        return $this->checkResult($this->objectManager->restoreObject($client, $this->reqRoute));
    }

    /**
     * @param ClientInterface $client
     *
     * @return ClientInterface
     * @throws InvalidResultException
     * @throws EmptyResponseException
     * @throws HttpClientAuthException
     * @throws ApiAuthException
     */
    public function archive(ClientInterface $client) :ClientInterface
    {
        return $this->checkResult($this->objectManager->archiveObject($client, $this->reqRoute));
    }

    /**
     * @param string $id
     *
     * @return ClientInterface
     * @throws InvalidResultException
     * @throws EmptyResponseException
     * @throws NotFoundException
     * @throws HttpClientAuthException
     * @throws ApiAuthException
     */
    public function getClientById(string $id) :ClientInterface
    {
        return $this->checkResult($this->objectManager->getObjectById($this->objectType, $id, $this->reqRoute));
    }

    /**
     * @param string $email
     *
     * @return ClientInterface[]
     * @throws InvalidResultException
     * @throws InvalidParameterException
     * @throws HttpClientAuthException
     * @throws ApiAuthException
     */
    public function findClientsByEmail(string $email) :array
    {
        return $this->objectManager->findObjectBy($this->objectType, ['email' => $email], $this->reqRoute);
    }

    /**
     * @param string $idNumber
     *
     * @return ClientInterface[]
     * @throws InvalidResultException
     * @throws InvalidParameterException
     * @throws HttpClientAuthException
     * @throws ApiAuthException
     */
    public function findClientsByIdNumber(string $idNumber) :array
    {
        return $this->objectManager->findObjectBy($this->objectType, ['id_number' => $idNumber], $this->reqRoute);
    }

    /**
     * @param int $page
     * @param int $pageSize
     *
     * @return ClientInterface[]
     * @throws InvalidResultException
     * @throws EmptyResponseException
     * @throws HttpClientAuthException
     * @throws ApiAuthException
     */
    public function getAllClients(int $page = 1, int $pageSize = 0) :array
    {
        $result = $this->objectManager->getAllObjects($this->objectType, $this->reqRoute, $page, $pageSize);
        foreach ($result as $client) {
            $this->checkResult($client);
        }
        return $result;
    }

    /**
     *
     * @return ClientInterface[]
     * @throws InvalidResultException
     * @throws EmptyResponseException
     * @throws HttpClientAuthException
     * @throws ApiAuthException
     */
    public function getActiveClients() :array
    {
        return $this->objectManager->findObjectBy($this->objectType, ['is_deleted' => false], $this->reqRoute);
    }

    /**
     * @param BaseInterface $client
     *
     * @return ClientInterface
     * @throws InvalidResultException
     */
    private function checkResult(BaseInterface $client) :ClientInterface
    {
        if (!$client instanceof ClientInterface) {
            throw new InvalidResultException();
        }
        return $client;
    }
}

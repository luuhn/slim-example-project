<?php


namespace App\Domain\Client\Service;


use App\Domain\Authentication\Exception\ForbiddenException;
use App\Domain\Authorization\Privilege;
use App\Domain\Client\Authorization\ClientAuthorizationChecker;
use App\Domain\Client\Authorization\ClientAuthorizationGetter;
use App\Domain\Client\Data\ClientData;
use App\Domain\Client\Data\ClientResultAggregateData;
use App\Domain\Client\Data\ClientResultDataCollection;
use App\Domain\Note\Authorization\NoteAuthorizationChecker;
use App\Domain\Note\Authorization\NoteAuthorizationGetter;
use App\Domain\Note\Service\NoteFinder;
use App\Domain\User\Service\UserNameAbbreviator;
use App\Infrastructure\Client\ClientFinderRepository;
use App\Infrastructure\Client\ClientStatus\ClientStatusFinderRepository;
use App\Infrastructure\User\UserFinderRepository;

class ClientFinder
{
    public function __construct(
        private readonly ClientFinderRepository $clientFinderRepository,
        private readonly UserFinderRepository $userFinderRepository,
        private readonly UserNameAbbreviator $userNameAbbreviator,
        private readonly ClientStatusFinderRepository $clientStatusFinderRepository,
        private readonly NoteFinder $noteFinder,
        private readonly ClientAuthorizationChecker $clientAuthorizationChecker,
        private readonly ClientAuthorizationGetter $clientAuthorizationGetter,
        private readonly NoteAuthorizationGetter $noteAuthorizationGetter,
        private readonly NoteAuthorizationChecker $noteAuthorizationChecker,
    ) {
    }

    /**
     * Build query builder where array with filter params
     *
     * @param array $filterParams default deleted_at null
     * @return array
     */
    public function buildWhereArrayWithFilterParams(array $filterParams = ['deleted_at' => null]): array
    {
        // Build where array for cakephp query builder
        $queryBuilderWhereArray = [];
        $adaptColumnValueToQueryBuilder = static function (string &$column, null|string|int|array &$value) {
            // If empty string it means that value should be null
            if ($value === '') {
                $value = null;
            }
            // If expected value is "null" the word "IS" is needed in the array key right after the column
            $is = '';
            // If " IS" is already in column, it doesn't have to be added
            if ($value === null && !str_contains($column, ' IS')) {
                $is = ' IS'; // To be added right after column
            }
            $column = "client.$column$is";
        };
        foreach ($filterParams as $column => $value) {
            // If multiple values are given for a filter setting, separate by OR
            if (is_array($value)) {
                $orConditions = [];
                foreach ($value as $rowId) {
                    $value = $rowId;
                    // Create column clone otherwise column (which is the same for each iteration of this loop) would
                    // have "client." prepended in each iteration
                    $columnClone = $column;
                    $adaptColumnValueToQueryBuilder($columnClone, $value);
                    $orConditions[][$columnClone] = $value;
                }
                // Add OR with conditions to where array
                $queryBuilderWhereArray[]['OR'] = $orConditions;
            }else{
                $adaptColumnValueToQueryBuilder($column, $value);
                $queryBuilderWhereArray[$column] = $value;
            }
        }
        return $queryBuilderWhereArray;
    }

    /**
     * Gives clients from db with aggregate data
     * matching given filter params
     *
     * @param $queryBuilderWhereArray
     * @return ClientResultDataCollection
     */
    public function findClientsWithAggregates($queryBuilderWhereArray): ClientResultDataCollection
    {
        $clientResultCollection = new ClientResultDataCollection();
        // Retrieve clients
        $clientResultCollection->clients = $this->findClientsWhereWithResultAggregate($queryBuilderWhereArray);

        $clientResultCollection->statuses = $this->clientStatusFinderRepository->findAllClientStatusesMappedByIdName();
        $clientResultCollection->users = $this->userNameAbbreviator->abbreviateUserNames(
            $this->userFinderRepository->findAllUsers()
        );

        // Add permissions on what logged-in user is allowed to do with object
        return $clientResultCollection;
    }

    /**
     * Finds and adds user_id change and client_status_id change privilege
     * to found clientResultAggregate filtered by the given $whereArray
     *
     * @param array $whereArray cake query builder where array -> ['table.field' => 'value']
     * @return ClientResultAggregateData[]
     */
    private function findClientsWhereWithResultAggregate(array $whereArray = ['client.deleted_at IS' => null]): array
    {
        $clientResultsWithAggregates = $this->clientFinderRepository->findClientsWithResultAggregate($whereArray);
        // Add assigned user and client status privilege to each clientResultAggregate
        foreach ($clientResultsWithAggregates as $client) {
            $client->assignedUserPrivilege = $this->clientAuthorizationGetter->getMutationPrivilegeForClientColumn(
                $client->userId,
                'user_id'
            );
            //  Set client status privilege
            $client->clientStatusPrivilege = $this->clientAuthorizationGetter->getMutationPrivilegeForClientColumn(
                $client->userId,
                'client_status_id',
            );
        }
        return $clientResultsWithAggregates;
    }

    /**
     * Find one client in the database
     *
     * @param $id
     * @return ClientData
     */
    public function findClient($id): ClientData
    {
        return $this->clientFinderRepository->findClientById($id);
    }

    /**
     * Find one client in the database with aggregate
     *
     * @param int $clientId
     * @param bool $includingNotes
     * @return ClientResultAggregateData
     */
    public function findClientReadAggregate(int $clientId, bool $includingNotes = true): ClientResultAggregateData
    {
        $clientResultAggregate = $this->clientFinderRepository->findClientAggregateById($clientId);
        if ($clientResultAggregate->id &&
            $this->clientAuthorizationChecker->isGrantedToRead($clientResultAggregate->userId)
        ) {
            // Set client mutation privilege
            $clientResultAggregate->mainDataPrivilege = $this->clientAuthorizationGetter->getMutationPrivilegeForClientColumn(
                $clientResultAggregate->userId,
                'main_data'
            );
            // Set main note privilege
            $clientResultAggregate->mainNoteData->privilege = $this->noteAuthorizationGetter->getMainNotePrivilege(
                $clientResultAggregate->mainNoteData->userId,
                $clientResultAggregate->userId
            );

            // Set assigned user privilege
            $clientResultAggregate->assignedUserPrivilege = $this->clientAuthorizationGetter->getMutationPrivilegeForClientColumn(
                $clientResultAggregate->userId,
                'user_id',
            );
            //  Set client status privilege
            $clientResultAggregate->clientStatusPrivilege = $this->clientAuthorizationGetter->getMutationPrivilegeForClientColumn(
                $clientResultAggregate->userId,
                'client_status_id',
            );
            //  Set create note privilege
            $clientResultAggregate->noteCreatePrivilege = $this->noteAuthorizationChecker->isGrantedToCreate(
                0,
                $clientResultAggregate->userId,
                false
            ) ? Privilege::CREATE : Privilege::NONE;

            if ($includingNotes === true) {
                $clientResultAggregate->notes = $this->noteFinder->findAllNotesFromClientExceptMain(
                    $clientId
                );
            } else {
                $clientResultAggregate->notesAmount = $this->noteFinder->findClientNotesAmount($clientId);
            }
            return $clientResultAggregate;
        }
        // The reasons this exception is thrown when tried to access soft deleted clients:
        // they are supposed to be deleted so only maybe a very high privileged role should have access, and it should
        // clearly be marked as deleted in the GUI as well. Also, a non-authorized user that is trying to access a client
        // should not be able to distinguish which clients exist and which not so for both cases the not allowed exception
        throw new ForbiddenException('Not allowed to read client.');
    }


    /**
     * Return all posts which are linked to the given user
     *
     * @param int $userId
     * @return ClientResultDataCollection
     */
    public function findAllClientsFromUser(int $userId): ClientResultDataCollection
    {
        $clientResultCollection = new ClientResultDataCollection();
        $clientResultCollection->clients = $this->clientFinderRepository->findAllClientsByUserId($userId);
//        $this->clientUserRightSetter->defineUserRightsOnClients($allClients);
        return $clientResultCollection;
    }

}
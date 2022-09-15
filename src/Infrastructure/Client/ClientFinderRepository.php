<?php


namespace App\Infrastructure\Client;


use App\Common\Hydrator;
use App\Domain\Client\Data\ClientData;
use App\Domain\Client\Data\ClientResultAggregateData;
use App\Infrastructure\Exceptions\PersistenceRecordNotFoundException;
use App\Infrastructure\Factory\QueryFactory;
use http\Client;

class ClientFinderRepository
{
    // ClientList select fields are the base (requires the least amount) which can be extended in constructor
    // To prevent duplicate code the client list aggregate select fields are in this array below
    private array $clientListAggregateSelectFields = [
        // Client data retrieved with original name to populate parent class ClientData
        'id' => 'client.id',
        'first_name' => 'client.first_name',
        'last_name' => 'client.last_name',
        'birthdate' => 'client.birthdate',
        'location' => 'client.location',
        'phone' => 'client.phone',
        'email' => 'client.email',
        'sex' => 'client.sex',
        'client_message' => 'client.client_message',
        'user_id' => 'client.user_id',
        'client_status_id' => 'client.client_status_id',
        'main_note_id' => 'client.main_note_id',
        'updated_at' => 'client.updated_at',
        'created_at' => 'client.created_at',
        // User data prefixed with user_
        'user_first_name' => 'user.first_name',
        'user_surname' => 'user.surname',
        // Client status data prefixed with client_status_
        'client_status_name' => 'client_status.name',
    ];
    // In constructor extended client list select fields
    private array $clientReadAggregateSelectFields = [];

    public function __construct(
        private QueryFactory $queryFactory,
        private Hydrator $hydrator
    ) {
        $this->clientReadAggregateSelectFields = array_merge($this->clientListAggregateSelectFields, [
            // Main note data prefixed with `note_`
            // Only necessary note fields
            'note_message' => 'note.message',
            'note_user_id' => 'note.user_id',
            'note_updated_at' => 'note.updated_at'
        ]);
    }

    /**
     * Return all clients with some aggregate data (user and status) attribute loaded that makes
     * sense for client result. Typically, things that will be used by the frontend when
     * displaying clients.
     *
     * Side note: difference between Association, aggregation / composition and inheritance
     * https://www.visual-paradigm.com/guide/uml-unified-modeling-language/uml-aggregation-vs-composition/
     *
     * @return ClientData[]
     */
    public function findAllClientsWithResultAggregate(): array
    {
        $query = $this->queryFactory->newQuery()->from('client');
        $query->select(
            $this->clientListAggregateSelectFields
        )// Multiple joins doc: https://book.cakephp.org/4/en/orm/query-builder.html#adding-joins
        ->join([
            // `user` is alias and has to be same as $clientAggregateSelectFields (could be `u` as well as long as its consistent)
            'user' => ['table' => 'user', 'type' => 'LEFT', 'conditions' => 'client.user_id = user.id'],
            'client_status' => [
                'table' => 'client_status',
                'type' => 'LEFT',
                'conditions' => 'client.client_status_id = client_status.id'
            ],
        ])
            ->andWhere(
                ['client.deleted_at IS' => null]
            );
        $resultRows = $query->execute()->fetchAll('assoc') ?: [];
        // Convert to list of Post objects with associated User info
        return $this->hydrator->hydrate($resultRows, ClientResultAggregateData::class);
    }

    /**
     * Return post with given id if it exists
     * otherwise null
     *
     * @param string|int $id
     * @return ClientData
     */
    public function findClientById(string|int $id): ClientData
    {
        $query = $this->queryFactory->newQuery()->select(['*'])->from('client')->where(
            ['deleted_at IS' => null, 'id' => $id]
        );
        $postRow = $query->execute()->fetch('assoc') ?: [];
        return new ClientData($postRow);
    }

    /**
     * Return client with relevant aggregate data for client read
     *
     * @param int $id
     * @return ClientResultAggregateData
     */
    public function findClientAggregateById(int $id): ClientResultAggregateData
    {
        $query = $this->queryFactory->newQuery()->from('client');

        $query->select($this->clientReadAggregateSelectFields)
            ->join([
                // `user` is alias and has to be same as $clientAggregateSelectFields (could be `u` as well as long as its consistent)
                'user' => ['table' => 'user', 'type' => 'LEFT', 'conditions' => 'client.user_id = user.id'],
                'client_status' => [
                    'table' => 'client_status',
                    'type' => 'LEFT',
                    'conditions' => 'client.client_status_id = client_status.id'
                ],
                'note' => [
                    'table' => 'note',
                    'type' => 'LEFT',
                    'conditions' => 'client.main_note_id = note.id and note.deleted_at IS NULL'
                ],
            ])
            ->andWhere(
                ['client.id' => $id, 'client.deleted_at IS' => null]
            );

        $resultRows = $query->execute()->fetch('assoc') ?: [];
        // Instantiate UserPost DTO
        return new ClientResultAggregateData($resultRows);
    }


    /**
     * Retrieve post from database
     * If not found error is thrown
     *
     * @param int $id
     * @return array
     * @throws PersistenceRecordNotFoundException
     */
    public function getPostById(int $id): array
    {
        $query = $this->queryFactory->newQuery()->select(['*'])->from('post')->where(
            ['deleted_at IS' => null, 'id' => $id]
        );
        $entry = $query->execute()->fetch('assoc');
        if (!$entry) {
            throw new PersistenceRecordNotFoundException('post');
        }
        return $entry;
    }

    /**
     * Return all posts which are linked to the given user
     *
     * @param int $userId
     * @return ClientResultAggregateData[]
     */
    public function findAllClientsByUserId(int $userId): array
    {
        $query = $this->queryFactory->newQuery()->from('client');

        $query->select(
            $this->clientListAggregateSelectFields
        )->join(['table' => 'user', 'conditions' => 'client.user_id = user.id'])
            ->join(['table' => 'client_status', 'conditions' => 'client.client_status_id = client_status.id'])
            ->andWhere(
                ['client.user_id' => $userId, 'client.deleted_at IS' => null]
            );
        $resultRows = $query->execute()->fetchAll('assoc') ?: [];
        // Convert to list of Post objects with aggregate
        return $this->hydrator->hydrate($resultRows, ClientResultAggregateData::class);
    }
}
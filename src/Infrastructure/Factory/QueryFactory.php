<?php

namespace App\Infrastructure\Factory;

use Cake\Database\Connection;
use Cake\Database\Query;

/**
 * Factory.
 */
final class QueryFactory
{
    private Connection $connection;

    /**
     * The constructor.
     *
     * @param Connection $connection The database connection
     */
    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * Get a query instance
     * ! Dont forget deleted_at when selecting or mass updating
     *
     * SELECT Example:
     *     $query = $this->queryFactory->newQuery()->select(['*'])->from('user')->where(
     *         ['deleted_at IS' => null, 'name LIKE' => '%John%']);
     *     return $query->execute()->fetchAll('assoc');
     * UPDATE Example:
     *     $query = $this->queryFactory->newQuery()->update('user')->set($data)->where(['id' => 1]);
     *     return $query->execute()->rowCount() > 0;
     *
     * @return Query
     */
    public function newQuery(): Query
    {
        return $this->connection->newQuery();
    }

    /**
     * Data is an assoc array of rows to insert where the key is the column name
     * Example:
     *     return (int)$this->queryFactory->newInsert($data)->into('user')->execute()->lastInsertId();
     *
     * @param array $data ['col_name' => 'Value', 'other_col' => 'Other value']
     * @return Query
     */
    public function newInsert(array $data): Query
    {
        return $this->connection->newQuery()->insert(array_keys($data))->values($data);
    }

    /**
     * Soft delete entry with given id from database
     * Table name needed here as its a required argument for update() function
     * Example:
     *     $query = $this->queryFactory->newDelete('post')->where(['id' => $id]);
     *     return $query->execute()->rowCount() > 0;
     *
     * @param string $fromTable
     * @return Query
     */
    public function newDelete(string $fromTable): Query
    {
        return $this->connection->newQuery()->update($fromTable)->set(['deleted_at' => date('Y-m-d H:i:s')]);
    }

}

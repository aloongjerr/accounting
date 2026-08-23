<?php

namespace AloongJerr\Accounting\Traits;

/**
 * Provides configurable database connection for accounting models.
 *
 * Reads the connection name from config('accounting.connection').
 * When null, uses the default application database connection.
 * This allows accounting data to be stored in a separate database.
 */
trait HasAccountingConnection
{
    /**
     * Get the current connection name for the model.
     */
    public function getConnectionName(): ?string
    {
        $connection = config('accounting.connection');

        return $connection !== null ? $connection : parent::getConnectionName();
    }
}

<?php

declare(strict_types=1);

namespace Gate\Core;

/** A schema migration. Files in database/migrations return an instance of this interface. */
interface Migration
{
    public function up(Database $db): void;

    public function down(Database $db): void;
}

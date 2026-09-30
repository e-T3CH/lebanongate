<?php

declare(strict_types=1);

namespace BMMatic\Core;

/** A schema migration. Files in database/migrations return an instance of this interface. */
interface Migration
{
    public function up(Database $db): void;

    public function down(Database $db): void;
}

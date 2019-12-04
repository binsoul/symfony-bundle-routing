<?php

declare(strict_types=1);

namespace BinSoul\Symfony\Bundle\Routing\EventListener;

use BinSoul\Symfony\Bundle\Doctrine\EventListener\AbstractPrefixListener;

class TablePrefixListener extends AbstractPrefixListener
{
    public function __construct(string $prefix)
    {
        parent::__construct($prefix, 'BinSoul\\Symfony\\Bundle\\Routing\\');
    }
}

<?php

namespace Oro\Bundle\SearchBundle\Query\Factory;

use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\SearchBundle\Query\SearchQueryInterface;

interface QueryFactoryInterface
{
    /**
     * Creating the Query wrapper object in the given
     * Datasource context.
     *
     * @param DatagridInterface $grid
     * @param array             $config
     * @return SearchQueryInterface
     */
    public function create(DatagridInterface $grid, array $config);
}

<?php

namespace Enhavo\Bundle\ResourceBundle\Collection;

use Doctrine\ORM\EntityRepository;
use Enhavo\Bundle\ResourceBundle\Column\Column;
use Enhavo\Bundle\ResourceBundle\Filter\Filter;

abstract class AbstractCollection implements CollectionInterface
{
    protected EntityRepository $repository;
    protected array $options;
    /** @var Filter[] */
    protected array $filters;
    /** @var Column[] */
    protected array $columns;

    public function setRepository(EntityRepository $repository): void
    {
        $this->repository = $repository;
    }

    public function setFilters(array $filters): void
    {
        $this->filters = $filters;
    }

    public function setColumns(array $columns): void
    {
        $this->columns = $columns;
    }

    public function setOptions(array $options): void
    {
        $this->options = $options;
    }
}

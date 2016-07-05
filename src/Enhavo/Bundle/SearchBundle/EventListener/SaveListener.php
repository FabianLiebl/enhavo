<?php

namespace Enhavo\Bundle\SearchBundle\EventListener;

use Symfony\Component\DependencyInjection\Container;
use Enhavo\Bundle\SearchBundle\Metadata\MetadataFactory;

class SaveListener
{
    protected $container;
    protected $util;
    protected $metadataFactory;

    public function __construct(Container $container, MetadataFactory $metadataFactory)
    {
        $this->container = $container;
        $this->metadataFactory = $metadataFactory;
    }

    public function onSave($event)
    {
        $metadata = $this->metadataFactory->create($event->getSubject());
        if($metadata) {
            //if searchYaml exists try indexing
            $engine = $this->container->getParameter('enhavo_search.search.index_engine');
            $indexEngine = $this->container->get($engine);
            $indexEngine->index($event->getSubject());
        }
    }
}
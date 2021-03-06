<?php
/**
 * VideoFactory.php
 *
 * @since 02/11/16
 * @author gseidel
 */

namespace Enhavo\Bundle\BlockBundle\Factory;

use Enhavo\Bundle\BlockBundle\Model\Block\VideoBlock;
use Enhavo\Bundle\BlockBundle\Model\BlockInterface;

class VideoBlockFactory extends AbstractBlockFactory
{
    public function duplicate(BlockInterface $original)
    {
        /** @var VideoBlock $data */
        /** @var VideoBlock $original */
        $data = new $this->dataClass;

        $data->setTitle($original->getTitle());
        $data->setUrl($original->getUrl());

        return $data;
    }
}

<?php

declare(strict_types=1);

namespace araise\CrudBundle\Collection;

use araise\CrudBundle\Block\Block;
use araise\CrudBundle\Enums\PageInterface;

class BlockCollection extends ArrayCollection
{
    public function filterVisibility(PageInterface $page): self
    {
        /** @var self $filtered */
        $filtered = $this->filter(static fn (Block $block) => in_array($page, $block->getOption('visibility'), true));

        return $filtered;
    }
}

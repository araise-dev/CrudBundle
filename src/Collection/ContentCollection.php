<?php

declare(strict_types=1);

namespace araise\CrudBundle\Collection;

use araise\CrudBundle\Content\AbstractContent;
use araise\CrudBundle\Enums\PageInterface;

class ContentCollection extends ArrayCollection
{
    public function filterVisibility(PageInterface $page): self
    {
        /** @var self $filtered */
        $filtered = $this->filter(static fn (AbstractContent $content) => in_array($page, $content->getOption('visibility'), true));

        return $filtered;
    }
}

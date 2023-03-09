<?php

namespace Drupal\display_format_markdown;

use League\CommonMark\Node\Block\AbstractBlock;

class Media extends AbstractBlock
{
  private string $id;

  public function __construct(int $id)
  {
    parent::__construct();

    $this->id = $id;
  }

  public function getId(): int
  {
    return $this->id;
  }

  public function setId(int $id): void
  {
    $this->id = $id;
  }
}

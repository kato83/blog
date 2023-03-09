<?php

namespace Drupal\display_format_markdown;

use League\CommonMark\Node\Block\AbstractBlock;
use League\CommonMark\Parser\Block\AbstractBlockContinueParser;
use League\CommonMark\Parser\Block\BlockContinue;
use League\CommonMark\Parser\Block\BlockContinueParserInterface;
use League\CommonMark\Parser\Cursor;

final class MediaParser extends AbstractBlockContinueParser
{
  /** @psalm-readonly */
  private Media $block;
  private string $id;

  public function __construct(string $id)
  {
    $this->id = $id;
    $this->block = new Media($this->id);
  }

  public function getBlock(): Media
  {
    return $this->block;
  }

  public function tryContinue(Cursor $cursor, BlockContinueParserInterface $activeBlockParser): ?BlockContinue
  {
    return BlockContinue::none();
  }
}

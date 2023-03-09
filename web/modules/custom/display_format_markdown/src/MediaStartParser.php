<?php

namespace Drupal\display_format_markdown;

use League\CommonMark\Parser\Block\BlockStart;
use League\CommonMark\Parser\Block\BlockStartParserInterface;
use League\CommonMark\Parser\Cursor;
use League\CommonMark\Parser\MarkdownParserStateInterface;

final class MediaStartParser implements BlockStartParserInterface
{
  public function tryStart(Cursor $cursor, MarkdownParserStateInterface $parserState): ?BlockStart
  {
    if ($cursor->isIndented() || $cursor->getNextNonSpaceCharacter() !== '$') {
      return BlockStart::none();
    }

    $cursor->advanceToNextNonSpaceOrTab();
    $cursor->advanceBy(1);
    $id = $cursor->getRemainder();

    if (!ctype_digit($id)) {
      return BlockStart::none();
    }

    return BlockStart::of(new MediaParser($id))->at($cursor);
  }
}

<?php

namespace Drupal\display_format_markdown;

use League\CommonMark\Extension\CommonMark\Node\Inline\HtmlInline;
use League\CommonMark\Parser\Inline\InlineParserInterface;
use League\CommonMark\Parser\Inline\InlineParserMatch;
use League\CommonMark\Parser\InlineParserContext;

class MarkdownMediaIntegration implements InlineParserInterface
{
  public function getMatchDefinition(): InlineParserMatch
  {
    return InlineParserMatch::regex('@\(m:([0-9]+)\)');
  }

  public function parse(InlineParserContext $inlineContext): bool
  {
    $cursor = $inlineContext->getCursor();
    // The @ symbol must not have any other characters immediately prior
    $previousChar = $cursor->peek(-1);
    if ($previousChar !== null && $previousChar !== ' ') {
      // peek() doesn't modify the cursor, so no need to restore state first
      return false;
    }

    // This seems to be a valid match
    // Advance the cursor to the end of the match
    $cursor->advanceBy($inlineContext->getFullMatchLength());

    // Grab the Twitter handle
    [$handle] = $inlineContext->getSubMatches();


    $media = \Drupal\media\Entity\Media::load($handle);
    if (empty($media)) {
      return false;
    }

    /** @var Drupal\Core\Entity\EntityTypeManager */
    $manager = \Drupal::service('entity_type.manager');
    $builder = $manager->getViewBuilder('media');
    $build = $builder->view($media, 'full');
    $html = \Drupal::service('renderer')->renderPlain($build);

    $inlineContext->getContainer()->replaceWith(new HtmlInline($html));
    return true;
  }
}

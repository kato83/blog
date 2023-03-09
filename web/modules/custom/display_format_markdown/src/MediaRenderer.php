<?php

namespace Drupal\display_format_markdown;

use League\CommonMark\Node\Node;
use League\CommonMark\Renderer\ChildNodeRendererInterface;
use League\CommonMark\Renderer\NodeRendererInterface;

final class MediaRenderer implements NodeRendererInterface
{
  /**
   * @param Media $node
   *
   * {@inheritDoc}
   *
   * @psalm-suppress MoreSpecificImplementedParamType
   */
  public function render(Node $node, ChildNodeRendererInterface $childRenderer): \Stringable
  {
    Media::assertInstanceOf($node);
    return new MediaString($node->getId());
  }
}

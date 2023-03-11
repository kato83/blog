<?php

namespace Drupal\display_format_markdown\Plugin\Field\FieldFormatter;

use Drupal\display_format_markdown\MarkdownMediaIntegration;
use Drupal\Core\Field\FormatterBase;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\display_format_markdown\Media;
use Drupal\display_format_markdown\MediaRenderer;
use Drupal\display_format_markdown\MediaStartParser;
use League\CommonMark\Extension\Table\TableExtension;
use League\CommonMark\MarkdownConverter;
use League\CommonMark\Environment\Environment;

/**
 * Makdown formatter.
 *
 * @FieldFormatter(
 *   id = "markdown_default",
 *   label = @Translation("Markdown text"),
 *   field_types = {
 *     "string_long",
 *     "string"
 *   }
 * )
 */
class MarkdownDefaultFormatter extends FormatterBase
{

  /**
   * {@inheritdoc}
   */
  public function settingsSummary()
  {
    $summary = [];
    $summary[] = $this->t('Displays the rendered markdown html.');
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode)
  {
    $element = [];
    $environment = new Environment([
      // 'html_input' => 'escape',
      'html_input' => 'allow',
      'allow_unsafe_links' => true,
    ]);
    $environment->addExtension(new CommonMarkCoreExtension());
    $environment->addExtension(new TableExtension());
    $environment->addBlockStartParser(new MediaStartParser(), -110);
    $environment->addRenderer(Media::class, new MediaRenderer(), 0);
    $converter =  new MarkdownConverter($environment);

    foreach ($items as $delta => $item) {
      // @todo markdown 処理
      $element[$delta] = [
        '#type' => 'inline_template',
        '#template' => '{% verbatim %}' . $converter->convert($item->value) . '{% endverbatim %}'
      ];
    }

    return $element;
  }
}

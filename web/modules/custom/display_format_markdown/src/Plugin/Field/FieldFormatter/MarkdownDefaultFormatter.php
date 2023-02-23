<?php

namespace Drupal\display_format_markdown\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;
use League\CommonMark\CommonMarkConverter;

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
    $converter = new CommonMarkConverter([
      'html_input' => 'strip',
      'allow_unsafe_links' => true,
    ]);

    foreach ($items as $delta => $item) {
      // @todo markdown 処理
      $element[$delta] = [
        '#type' => 'inline_template',
        '#template' => $converter->convert($item->value)
      ];
    }

    return $element;
  }
}

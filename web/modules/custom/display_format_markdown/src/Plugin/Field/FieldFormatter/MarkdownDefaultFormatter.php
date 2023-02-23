<?php

namespace Drupal\display_format_markdown\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;

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

    foreach ($items as $delta => $item) {
      // @todo markdown 処理
      $element[$delta] = ['#markup' => $item->value];
    }

    return $element;
  }
}

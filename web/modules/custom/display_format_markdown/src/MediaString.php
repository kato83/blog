<?php

namespace Drupal\display_format_markdown;

class MediaString implements \Stringable
{

  private string $id;

  function __construct(string $id)
  {
    $this->id = $id;
  }

  public function __toString(): string
  {
    $media = \Drupal\media\Entity\Media::load($this->id);
    if (empty($media)) {
      return '';
    }

    /** @var Drupal\Core\Entity\EntityTypeManager */
    $manager = \Drupal::service('entity_type.manager');
    $builder = $manager->getViewBuilder('media');
    $build = $builder->view($media, 'full');
    $html = \Drupal::service('renderer')->renderPlain($build);
    return $html;
  }
}

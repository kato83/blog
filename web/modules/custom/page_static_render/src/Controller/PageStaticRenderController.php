<?php

namespace Drupal\page_static_render\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Provides route responses for the Example module.
 */
class PageStaticRenderController extends ControllerBase
{

  /**
   * Returns a simple page.
   *
   * @return array
   *   A simple renderable array.
   */
  public function page()
  {
    return [];
  }
}

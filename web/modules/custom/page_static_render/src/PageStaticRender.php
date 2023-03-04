<?php

namespace Drupal\page_static_render;

use Drupal\node\Entity\Node;

class PageStaticRender
{

  protected $logger;

  function __construct()
  {
    $this->logger = \Drupal::logger('PageStaticRender');
  }

  /**
   * generate page by node entity
   */
  public function renderNode(Node $node)
  {
    exec("drush node-render {$node->id()} > /dev/null 2>&1 &");
    $this->renderFrontPage();
  }

  public function renderFrontPage()
  {
    // exec("php -f /opt/drupal/scripts/test.php > /dev/null 2>&1 &");
  }

  public function deleteByPath(string $path)
  {
    exec("rm -f \"/opt/drupal/html$path/index.html\" > /dev/null 2>&1 &");
  }
}

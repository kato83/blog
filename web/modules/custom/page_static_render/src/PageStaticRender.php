<?php

namespace Drupal\page_static_render;

use Drupal\node\Entity\Node;
use Drupal\path_alias\Entity\PathAlias;

class PageStaticRender
{

  protected $logger;

  function __construct()
  {
    $this->logger = \Drupal::logger('PageStaticRender');
    // @todo
    putenv('HOME=/var/www');
  }

  /**
   * generate page by node entity
   */
  public function renderNode(Node $node)
  {
    exec("/opt/drupal/vendor/bin/drush node-render {$node->id()} > /dev/null 2>&1 &", $std, $code);
    $this->renderFrontPage();
  }

  public function renderFrontPage()
  {
    // exec("php -f /opt/drupal/scripts/test.php > /dev/null 2>&1 &");
  }

  /**
   * ファイル削除
   * @todo PHP 標準のファイル削除処理に置き換える
   */
  public function deleteByPath(string $path)
  {
    exec("rm -f \"/opt/drupal/html$path/index.html\" > /dev/null 2>&1 &");
  }

  public function nodeUpsertCheck(Node $node)
  {
    $moderation_state = $node->get('moderation_state')->getString();
    /** @var \Drupal\page_static_render\PageStaticRender $render */
    $render = \Drupal::service('page_static_render.render');
    $logger = \Drupal::logger('page_static_render_node_insert');

    if ($moderation_state === 'unpublish') {
      $logger->info('delete');
      page_static_render_node_delete($node);
    } else if ($moderation_state === 'published') {
      /** @var \Drupal\page_static_render\PageStaticRender $render */
      $render = \Drupal::service('page_static_render.render');
      $render->renderNode($node);
    }
  }

  /**
   * パス移動が実施されたかのチェック
   */
  public function pathAliasMoveCheck(PathAlias $pathAlias)
  {
    $result = \Drupal::entityQuery('path_alias')
      ->accessCheck(true)
      ->condition('path', $pathAlias->getPath())
      ->execute();
    $path_alias = PathAlias::loadMultiple($result);

    if (!empty($path_alias))
      $this->deleteByPath(array_values($path_alias)[0]->getAlias());
  }
}

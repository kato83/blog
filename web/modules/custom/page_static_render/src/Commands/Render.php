<?php

namespace Drupal\page_static_render\Commands;

use Drush\Commands\DrushCommands;

/**
 * A drush command file.
 *
 * @package Drupal\page_static_render\Commands
 */
class Render extends DrushCommands
{

  /**
   * ページ生成
   *
   * @param string $id
   *   node id
   * @command page_static_render:node-render
   * @aliases node-render nr
   * @usage page_static_render:node-render
   */
  public function nodeRender($id = '')
  {
    $node = \Drupal\node\Entity\Node::load($id);
    $url = $node->toUrl()->toString();
    \Drupal::logger('info')->info($url);
    if (!file_exists("/opt/drupal/html$url")) mkdir("/opt/drupal/html$url", 0775, true);
    exec("curl -s -H 'Host: www.pu10g.com' \"http://127.0.0.1$url\" -o \"/opt/drupal/html$url/index.html\"");
    $this->output()->writeln("OK");
  }

  /**
   * ページ生成
   *
   * @command page_static_render:front-render
   * @aliases front-render fr
   * @usage page_static_render:front-render
   */
  public function frontRender()
  {
    exec("curl -s -H 'Host: www.pu10g.com' \"http://127.0.0.1/\" -o \"/opt/drupal/html/index.html\"");
    $this->output()->writeln("OK");
  }
}

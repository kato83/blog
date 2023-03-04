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
   * @param string $text
   *   Argument with message to be displayed.
   * @command page_static_render:node-render
   * @aliases node-render nr
   * @usage page_static_render:node-render
   */
  public function message($text = '')
  {
    $node = \Drupal\node\Entity\Node::load($text);
    $url = $node->toUrl()->toString();
    \Drupal::logger('info')->info($url);
    if (!file_exists("/opt/drupal/html$url")) mkdir("/opt/drupal/html$url", 0775, true);
    exec("curl -s -H 'Host: www.pu10g.com' \"http://127.0.0.1$url\" -o \"/opt/drupal/html$url/index.html\"");
    $this->output()->writeln("curl \"http://127.0.0.1$url\" -o \"/opt/drupal/html$url/index.html\" > /dev/null 2>&1 &");
  }
}

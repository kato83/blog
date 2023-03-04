<?php

namespace Drupal\page_static_render\Commands;

use Drupal\Component\Utility\Html;
use Drupal\node\Entity\Node;
use Drush\Commands\DrushCommands;
use IvoPetkov\HTML5DOMDocument;

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
    $html = file_get_contents("http://127.0.0.1$url");
    $images = $this->findImageByHtml($html);
    foreach ($images as $value) {
      $this->deployImage($value);
    }

    $this->output()->writeln("OK");
  }

  private function deployImage(string $url)
  {
    if (!file_exists("/opt/drupal/html$url")) mkdir(dirname("/opt/drupal/html$url"), 0775, true);
    $url = parse_url($url, PHP_URL_PATH);
    \Drupal::logger('image')->info("curl -s -H 'Host: www.pu10g.com' \"http://127.0.0.1$url\" -o \"/opt/drupal/html$url\"");
    exec("curl -s -H 'Host: www.pu10g.com' \"http://127.0.0.1$url\" -o \"/opt/drupal/html$url\"");
  }

  private function findImageByHtml(string $html): array
  {
    $document = new HTML5DOMDocument();
    $document->loadHTML($html);
    $imgs = $document->querySelectorAll('article img');
    $results = [];
    /** @var \IvoPetkov\HTML5DOMElement $img */
    foreach ($imgs as $img) {
      $results[] = $img->getAttribute('src');
    }
    return array_filter($results, 'strlen');
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

  /**
   * ページ生成
   *
   * @command page_static_render:all-render
   * @aliases all-render ar
   * @usage page_static_render:all-render
   */
  public function allRender()
  {
    $nodes = Node::loadMultiple(\Drupal::entityQuery('node')->accessCheck(true)->execute());
    foreach ($nodes as $value) {
      $this->nodeRender($value->id());
    }
    $this->output()->writeln("OK");
  }
}

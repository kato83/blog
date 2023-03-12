<?php

namespace Drupal\page_static_render\Commands;

use Drupal\Component\Utility\Html;
use Drupal\node\Entity\Node;
use Drush\Commands\DrushCommands;
use IvoPetkov\HTML5DOMDocument;
use PhpParser\Node\Expr\Cast\Array_;

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
  public function nodeRender($id = '', $isRenderFront = true)
  {
    $node = \Drupal\node\Entity\Node::load($id);
    $url = $node->toUrl()->toString();

    $opts = ['http' => ['method' => 'GET', 'header' => 'Host: www.pu10g.com']];
    $context = stream_context_create($opts);
    $body = file_get_contents("http://127.0.0.1$url", false, $context);
    if (!file_exists("/opt/drupal/html$url")) mkdir("/opt/drupal/html$url", 0775, true);
    file_put_contents("/opt/drupal/html$url/index.html", $body);

    $this->output()->writeln("OUTPUT ID: $id");
    $this->output()->writeln("SAVE AS  : /opt/drupal/html$url/index.html");

    $images = $this->findImageByHtml($body);
    foreach ($images as $image) {
      $path = $this->deployImage($image);
      $this->output()->writeln("IMAGE PUT: $path");
    }

    if ($isRenderFront) $this->frontRender();
  }

  private function deployImage(string $url)
  {
    if (!file_exists(dirname("/opt/drupal/html$url"))) mkdir(dirname("/opt/drupal/html$url"), 0775, true);

    $opts = ['http' => ['method' => 'GET', 'header' => 'Host: www.pu10g.com']];
    $context = stream_context_create($opts);
    file_put_contents(
      "/opt/drupal/html" . parse_url($url, PHP_URL_PATH),
      file_get_contents("http://127.0.0.1$url", false, $context));
    return "/opt/drupal/html$url";
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
      $src_sets = explode(',', $img->hasAttribute('srcset') ? $img->getAttribute('srcset') : '');
      $src_sets = array_filter(array_map(fn($str) => explode(' ', trim($str))[0], $src_sets), 'strlen');
      $results = array_merge($results, $src_sets);
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
    $json = json_decode(file_get_contents('http://127.0.0.1/api/v1/front'));
    $max_page = ceil(count($json) / 10);
    for ($i = 0; $i < $max_page; $i++) {
      $prefix = $i == 0 ? '' : ('/' . ($i + 1));
      if (!file_exists("/opt/drupal/html$prefix")) mkdir("/opt/drupal/html$prefix", 0775, true);

      $opts = ['http' => ['method' => 'GET', 'header' => 'Host: www.pu10g.com']];
      $context = stream_context_create($opts);
      $body = file_get_contents("http://127.0.0.1/?page=$i", false, $context);
      $body = $this->replaceLink($body);
      $body = $this->replacePager($body);
      file_put_contents("/opt/drupal/html$prefix/index.html", $body);
    }
    $this->output()->writeln("OK");
  }

  private function replaceLink(string $html): string
  {
    $document = new HTML5DOMDocument();
    $document->loadHTML($html);
    $items = $document->querySelectorAll('a[rel="bookmark"]');

    /** @var \IvoPetkov\HTML5DOMElement $item */
    foreach ($items as $item) {
      $href = $item->getAttribute('href');
      $href = str_ends_with($href, '/') ? $href : ($href . '/');

      $item->setAttribute('href', $href);
    }

    return $document->saveHTML();
  }

  public function replacePager(string $html): string
  {
    $document = new HTML5DOMDocument();
    $document->loadHTML($html);
    $items = $document->querySelectorAll('.pager a');

    /** @var \IvoPetkov\HTML5DOMElement $item */
    foreach ($items as $item) {
      $num = intval($item->getAttribute('data-page'));
      $item->setAttribute('href', $num == 1 ? '/' : '/' . $num . '/');
    }

    return $document->saveHTML();
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
      $this->nodeRender($value->id(), false);
    }
    $this->frontRender();
    $this->output()->writeln("OK");
  }
}

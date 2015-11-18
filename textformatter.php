<?php
/**
 * TextFormatter v1.0.2
 *
 * This plugin is a wrapper for TextFormatter, a library that supports
 * BBCode, HTML and other markup via plugin. Handles emoticons, censors
 * words, automatically embeds media and more.
 *
 * Dual licensed under the MIT or GPL Version 3 licenses, see LICENSE.
 * http://benjamin-regler.de/license/
 *
 * @package     TextFormatter
 * @version     1.0.2
 * @link        <https://github.com/sommerregen/grav-plugin-textformatter>
 * @author      Benjamin Regler <sommerregen@benjamin-regler.de>
 * @copyright   2015, Benjamin Regler
 * @license     <http://opensource.org/licenses/MIT>        MIT
 * @license     <http://opensource.org/licenses/GPL-3.0>    GPLv3
 */

namespace Grav\Plugin;

use Grav\Common\Plugin;
use Grav\Common\Page\Page;
use Grav\Common\Data\Blueprints;

use RocketTheme\Toolbox\Event\Event;

/**
 * TextFormatter Plugin
 *
 * This plugin is a wrapper for TextFormatter, a library that supports
 * BBCode, HTML and other markup via plugin. Handles emoticons, censors
 * words, automatically embeds media and more.
 */
class TextFormatterPlugin extends Plugin
{
  /**
   * @var TextFormatterPlugin
   */

  /**
   * Instance of TextFormatter class
   *
   * @var object
   */
  protected $textformatter;

  /**
   * Return a list of subscribed events.
   *
   * @return array    The list of events of the plugin of the form
   *                      'name' => ['method_name', priority].
   */
  public static function getSubscribedEvents()
  {
    return [
      'onPluginsInitialized' => ['onPluginsInitialized', 0]
    ];
  }

  /**
   * Initialize configuration
   */
  public function onPluginsInitialized()
  {
    if ($this->config->get('plugins.textformatter.enabled')) {
      $events = [
        'onPageContentRaw' => ['onPageContentRaw', 0],
        'onTwigInitialized' => ['onTwigInitialized', 0]
      ];

      if ($this->isAdmin()) {
        $this->active = false;
        $events = [
          'onBlueprintCreated' => ['onBlueprintCreated', 0]
        ];
      }

      $this->enable($events);
    }
  }

  /**
   * Extend page blueprints with textformatter configuration options.
   *
   * @param Event $event
   */
  public function onBlueprintCreated(Event $event)
  {
    /** @var Blueprints $blueprint */
    $blueprint = $event['blueprint'];

    if ($blueprint->get('form.fields.tabs')) {
      $blueprints = new Blueprints(__DIR__ . '/blueprints/');
      $extends = $blueprints->get($this->name);
      $blueprint->extend($extends, true);
    }
  }

  /**
   * Add content after page content was read into the system.
   *
   * @param  Event  $event An event object, when `onPageContentRaw` is
   *                       fired.
   */
  public function onPageContentRaw(Event $event)
  {
    /** @var Page $page */
    $page = $event['page'];

    /** @var Cache $cache */
    $cache = $this->grav['cache'];

    $header = $page->header();
    $config = $this->mergeConfig($page);

    // Process contents with TextFormatter(?)
    if (isset($header->process['textformatter'])) {
      $process = (bool) $header->process['textformatter'];
    } else {
      $process = $config->get('process', false);
    }

    // Process contents
    if ($config->get('enabled')) {
      $raw = $page->getRawContent();

      // Build an anonymous function to pass to `parseLinks()`
      $function = function ($matches) use (&$page, &$config) {
        $content = $matches[1];
        return $this->textFormatterFilter($content, $config->toArray(), $page);
      };

      if ($process) {
        $raw = $function(['',
          // Parse links (strip markup from content)
          $this->parseLinks($raw, function($matches) {
            return $matches[1];
          })
        ]);
      } else {
        $raw = $this->parseLinks($raw, $function);
      }

      // Set the parsed content back into as raw content
      $page->setRawContent($raw);
    }
  }

  /**
   * Initialize Twig configuration and filters.
   */
  public function onTwigInitialized()
  {
    // Expose function
    $this->grav['twig']->twig()->addFilter(
      new \Twig_SimpleFilter('textformatter', [$this, 'textFormatterFilter'], ['is_safe' => ['html']])
    );
  }

  /**
   * Filter to parse content using TextFormatter class.
   *
   * @param  string $content The content to be filtered.
   * @param  array  $options Array of options for the textformatter filter.
   *
   * @return string          The filtered content.
   */
  public function textFormatterFilter($content, $params = [])
  {
    // Get custom user configuration
    $page = func_num_args() > 2 ? func_get_arg(2) : $this->grav['page'];
    $config = $this->mergeConfig($page, true, $params);

    // Render
    return $this->init()->render($content, $config, $page);
  }

  /**
   * Initialize plugin and all dependencies.
   *
   * @return \Grav\Plugin\TextFormattter   Returns a TextFormattter instance.
   */
  protected function init()
  {
    if (!$this->textformatter) {
      // Autoload classes
      $autoload = __DIR__ . '/vendor/autoload.php';
      if (!is_file($autoload)) {
          throw new \Exception('Textformatter Plugin failed to load. Composer dependencies not met.');
      }
      require_once $autoload;

      // Initialize TextFormatter class
      require_once(__DIR__ . '/classes/TextFormatter.php');
      $this->textformatter = new TextFormatter();
    }

    return $this->textformatter;
  }
}

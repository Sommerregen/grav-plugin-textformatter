<?php
/**
 * TextFormatter
 *
 * This file is part of Grav TextFormatter plugin.
 *
 * Dual licensed under the MIT or GPL Version 3 licenses, see LICENSE.
 * http://benjamin-regler.de/license/
 */

namespace Grav\Plugin;

use Grav\Common\GravTrait;
use Grav\Common\Filesystem\Folder;
use s9e\TextFormatter\Configurator;

/**
 * TextFormatter
 *
 * Helper class to wrap TextFormatter, a library that supports BBCode,
 * HTML and other markup via plugin. Handles emoticons, censors words,
 * automatically embeds media and more.
 */
class TextFormatter
{
  /**
   * @var TextFormatter
   */
  use GravTrait;

  /**
   * Current instance of the TextFormatter
   *
   * @var s9e\TextFormatter\Configurator
   */
  protected $textformatter;

  /**
   * Current options of the page
   *
   * @var Grav\Common\Data\Data
   */
  protected $options;

  /**
   * Initialize and setup TextFormatter
   *
   * @param  array  $options Options to be passed to the renderer.
   */
  public function init($options = [])
  {
    $this->textformatter = new Configurator();
    $this->options = $options;

    // Use PHP engine (no need to cache something)
    $this->textformatter->rendering->engine = 'PHP';

    foreach ($options->toArray() as $key => $config) {
      $key = 'setup' . ucfirst($key);
      if (method_exists($this, $key)) {
        $this->$key($config);
      }
    }
  }

  /**
   * Process contents i.e. apply filer to the content.
   *
   * @param  string     $content The content to render.
   * @param  array      $options Options to be passed to the renderer.
   * @param  null|Page  $page    Null or an instance of \Grav\Common\Page.
   *
   * @return string              The rendered contents.
   */
  public function render($content, $options = [], $page = null)
  {
    // Initialize, if not already done
    if (!$this->textformatter) {
      $this->init($options);
    }

    // Get an instance of the parser and the renderer
    extract($this->textformatter->finalize());

    // Parse and transform plain text into XML
    $xml = $parser->parse($content);

    // Resolve emoticons path (return something even if path does not exists)
    if ($emoticons = (string) $options->get('emoticons.path', '')) {
      /** @var UniformResourceLocator $locator */
      $locator = self::getGrav()['locator'];

      /** @var Uri $uri */
      $uri = self::getGrav()['uri'];

      // Get relative path to the resource (or false if not found).
      $resource = strpos($emoticons, '://') ? $locator->findResource($emoticons, false) : $emoticons;
      $emoticons = $resource ? rtrim($uri->rootUrl(), '/') . '/' . $resource : '';
    }

    // Set parameters
    $renderer->setParameters([
      // Path to the emoticons
      'EMOTICONS_PATH' => $emoticons
    ]);

    // Transform XML into HTML
    $content = $renderer->render($xml);

    // Reset textformatter and return modified content
    $this->textformatter = null;
    return $content;
  }

  /**
   * Convert plain-text emails into clickable "mailto:" links.
   *
   * @param  array $options List of options (see `textformatter.yaml`)
   * @see    http://s9etextformatter.readthedocs.org/Plugins/Autoemail/Synopsis/
   */
  protected function setupAutomail($options)
  {
    if ($options) {
      $this->textformatter->plugins->load('Autoemail');
    }
  }

  /**
   * Converts plain-text image URLs into actual images.
   *
   * @param  array $options List of options (see `textformatter.yaml`)
   * @see    http://s9etextformatter.readthedocs.org/Plugins/Autoimage/Synopsis/
   */
  protected function setupAutoimage($options)
  {
    if ($options) {
      $this->textformatter->plugins->load('Autoemail');
    }
  }

  /**
   * Converts plain-text URLs into clickable links.
   *
   * @param  array $options List of options (see `textformatter.yaml`)
   * @see    http://s9etextformatter.readthedocs.org/Plugins/Autolink/Synopsis/
   */
  protected function setupAutolink($options)
  {
    if ($options['enabled']) {
      $this->textformatter->plugins->load('Autolink', ['matchWww' => $options['www']]);

      $this->textformatter->urlConfig->disallowScheme('http');
      $this->textformatter->urlConfig->disallowScheme('https');

      foreach ((array) $options['schemes'] as $key => $scheme) {
        $this->textformatter->urlConfig->allowScheme($scheme);
      }
    }
  }

  /**
   * Handle a very flexible flavour of the BBCode syntax.
   *
   * @param  array $options List of options (see `textformatter.yaml`)
   * @see    http://s9etextformatter.readthedocs.org/Plugins/BBCodes/Synopsis/
   */
  protected function setupBbcodes($options)
  {
    if ($options['enabled']) {
      // Add BBCodes using bundled repository
      foreach ((array) $options['bbcodes'] as $key => $bbcode) {
        $bbcode = strtoupper($bbcode);
        // Unset duplicate tags, see https://github.com/s9e/TextFormatter/issues/11
        if (in_array($bbcode, ['EMAIL', 'URL'])) {
          unset($this->textformatter->tags[$bbcode]);
        }

        $this->textformatter->BBCodes->addFromRepository($bbcode);
      }

      // Add BBCode using the custom syntax
      foreach ((array) $options['custom'] as $bbcode => $template) {
        $this->textformatter->BBCodes->addCustom($bbcode, $template);
      }
    }
  }

  /**
   * Censors text based on a configurable list of words.
   *
   * @param  array $options List of options (see `textformatter.yaml`)
   * @see    http://s9etextformatter.readthedocs.org/Plugins/Censor/Synopsis/
   */
  protected function setupCensor($options)
  {
    if ($options['enabled']) {
      foreach ((array) $options['words'] as $word => $replacement) {
        $replacement = !empty($replacement) ? $replacement : null;
        $this->textformatter->Censor->add($word, $replacement);
      }
    }
  }

  /**
   * Render standardized set of pictographs.
   *
   * @param  array $options List of options (see `textformatter.yaml`)
   * @see    http://s9etextformatter.readthedocs.org/Plugins/Emoji/Synopsis/
   */
  protected function setupEmoji($options)
  {
    switch (strtolower($options)) {
      case 'twemoji':
        $this->textformatter->Emoji;          // Using the Twemoji set
        # code...
        break;

      case 'emojione':
        $this->textformatter->Emoji->useEmojiOne();  // Using the EmojiOne set
        break;

      default:
        break;
    }
  }

  /**
   * Performs simple replacements, best suited for handling emoticons. Matching
   * is case-sensitive.
   *
   * @param  array $options List of options (see `textformatter.yaml`)
   * @see    http://s9etextformatter.readthedocs.org/Plugins/Emoticons/Synopsis/
   */
  protected function setupEmoticons($options)
  {
    if ($options['enabled']) {
      foreach ((array) $options['icons'] as $code => $filename)
      {
			  $this->textformatter->Emoticons->add($code,
				  '<img src="{$EMOTICONS_PATH}/' . $filename . '" alt="' . $code . '"/>'
  			);
  		}
    }
  }

  /**
   * Defines the backslash character \ as an escape character.
   *
   * @param  array $options List of options (see `textformatter.yaml`)
   * @see    http://s9etextformatter.readthedocs.org/Plugins/Escaper/Synopsis/
   */
  protected function setupEscaper($options)
  {
    if ($options['enabled']) {
      // Escape according to regular expression
      $escape = [];
      if ($options['regex']) {
        $escape['regexp'] = $options['regex'];
      }

      // Load Escaper
      $this->textformatter->plugins->load('Escaper', $escape);

      // Escape any character (only suitable in some specific situations)
      if ($options['escape_all']) {
        $this->textformatter->Escaper->escapeAll();
      }
    }
  }

  /**
   * Provide enhanced typography, aka "fancy Unicode symbols"
   *
   * @param  array $options List of options (see `textformatter.yaml`)
   * @see    http://s9etextformatter.readthedocs.org/Plugins/FancyPants/Synopsis/
   */
  protected function setupFancypants($options)
  {
    if ($options) {
      $this->textformatter->plugins->load('FancyPants');
    }
  }

  /**
   * Allows HTML comments to be used, enables a whitelist of HTML
   * elements and escapes HTML entities.
   *
   * @param  array $options List of options (see `textformatter.yaml`)
   *
   * @see    http://s9etextformatter.readthedocs.org/Plugins/HTMLComments/Synopsis/
   * @see    http://s9etextformatter.readthedocs.org/Plugins/HTMLElements/Synopsis/
   * @see    http://s9etextformatter.readthedocs.org/Plugins/HTMLEntities/Synopsis/
   */
  protected function setupHtml($options)
  {
    // HTMLComments
    if ($options['comments']) {
      $this->textformatter->plugins->load('HTMLComments');
    }

    // HTMLEntities
    if ($options['entities']) {
      $this->textformatter->plugins->load('HTMLEntities');
    }

    // HTMLElements
    if ($options['elements']['enabled']) {
      $elements = $options['elements']['allowed'];

      // Register safe HTML elements and attributes
      foreach ((array) $elements['safe'] as $element => $item) {
        $this->textformatter->HTMLElements->allowElement($element);

        $attributes = array_filter(explode(', ', $item));
        foreach ($attributes as $index => $attribute) {
          $attr = $this->textformatter->HTMLElements->allowAttribute($element, trim($attribute, '* '));
          if ($attribute[0] !== '*') {
            $attr->required = true;
          }
        }
      }

      // Register unsafe HTML elements and attributes
      foreach ((array) $elements['unsafe'] as $element => $item) {
        $this->textformatter->HTMLElements->allowUnsafeElement($element);

        $attributes = array_filter(explode(', ', $item));
        foreach ($attributes as $index => $attribute) {
          $attr = $this->textformatter->HTMLElements->allowUnsafeAttribute($element, trim($attribute, '* '));
          if ($attribute[0] !== '*') {
            $attr->required = true;
          }
        }
      }
    }
  }

  /**
   * Serves to capture keywords in plain text and render them as a rich
   * element of your choosing such as a link, a popup or a widget.
   *
   * @param  array $options List of options (see `textformatter.yaml`)
   * @see    http://s9etextformatter.readthedocs.org/Plugins/Keywords/Synopsis/
   */
  protected function setupKeywords($options)
  {
    if ($options['enabled']) {
      // Keywords are case-sensitive by default but you can make
      // case-insensitive. This is not recommended if the list of
      // keywords contain words that could appear in normal speech, e.g.
      // "Fire", "Air", "The"
      $this->textformatter->Keywords->caseSensitive = (bool) $options['case_sensitive'];

      if (strlen($options['template']) > 0) {
        // Set the template that renders them
        $this->textformatter->Keywords->getTag()->template = $options['template'];
      }

      // Add a couple of keywords
      foreach ((array) $options['keywords'] as $index => $word) {
        $this->textformatter->Keywords->add($word);
      }
    }
  }

  /**
   * Allow the user to embed content from allowed sites using a [media]
   * BBCode, site-specific BBCodes such as [youtube], or from simply
   * posting a supported URL in plain text.
   *
   * @param  array $options List of options (see `textformatter.yaml`)
   * @see    http://s9etextformatter.readthedocs.org/Plugins/MediaEmbed/Synopsis/
   */
  protected function setupMediaembed($options)
  {
    if ($options['enabled']) {
      if ($options['create_individiual_bbcodes']) {
        // We want to create individual BBCodes such as [youtube] in
        // addition to the default [media] BBCode
        $this->textformatter->MediaEmbed->createIndividualBBCodes = true;
      }

      // Add the sites we want to support
      foreach ((array) $options['sites'] as $key => $service) {
        $this->textformatter->MediaEmbed->add($service);
      }
    }
  }

  /**
   * Performs generic, regexp-based replacements.
   *
   * @param  array $options List of options (see `textformatter.yaml`)
   * @see    http://s9etextformatter.readthedocs.org/Plugins/Preg/Synopsis/
   */
  protected function setupPreg($options)
  {
    if ($options['enabled']) {
      foreach ((array) $options['replace'] as $pattern => $replacement) {
        $this->textformatter->Preg->replace($pattern, $replacement);
      }

      foreach ((array) $options['match'] as $pattern => $tag) {
        $this->textformatter->Preg->match($pattern, $tag);
      }
    }
  }
}

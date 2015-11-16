<?php

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2015 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Plugins\Litedown;
use s9e\TextFormatter\Plugins\ConfiguratorBase;
class Configurator extends ConfiguratorBase
{
	protected $tags = array(
		'C'      => '<code><xsl:apply-templates /></code>',
		'CODE'   => array(
			'attributes' => array(
				'lang' => array(
					'filterChain' => array('#simpletext'),
					'required'    => \false
				)
			),
			'template' =>
				'<pre>
					<code class="{@lang}">
						<xsl:apply-templates />
					</code>
				</pre>'
		),
		'DEL'    => '<del><xsl:apply-templates/></del>',
		'EM'     => '<em><xsl:apply-templates/></em>',
		'H1'     => '<h1><xsl:apply-templates/></h1>',
		'H2'     => '<h2><xsl:apply-templates/></h2>',
		'H3'     => '<h3><xsl:apply-templates/></h3>',
		'H4'     => '<h4><xsl:apply-templates/></h4>',
		'H5'     => '<h5><xsl:apply-templates/></h5>',
		'H6'     => '<h6><xsl:apply-templates/></h6>',
		'HR'     => '<hr/>',
		'IMG'    => array(
			'attributes' => array(
				'alt'   => array('required' => \false),
				'src'   => array('filterChain' => array('#url')),
				'title' => array('required' => \false)
			),
			'template' => '<img src="{@src}"><xsl:copy-of select="@alt"/><xsl:copy-of select="@title"/></img>'
		),
		'LI'     => '<li><xsl:apply-templates/></li>',
		'LIST'   => array(
			'attributes' => array(
				'type' => array(
					'filterChain' => array('#simpletext'),
					'required'    => \false
				)
			),
			'template' =>
				'<xsl:choose>
					<xsl:when test="not(@type)">
						<ul><xsl:apply-templates/></ul>
					</xsl:when>
					<xsl:otherwise>
						<ol><xsl:apply-templates/></ol>
					</xsl:otherwise>
				</xsl:choose>'
		),
		'QUOTE'  => '<blockquote><xsl:apply-templates/></blockquote>',
		'STRONG' => '<strong><xsl:apply-templates/></strong>',
		'SUP'    => '<sup><xsl:apply-templates/></sup>',
		'URL'    => array(
			'attributes' => array(
				'title' => array(
					'required' => \false
				),
				'url'   => array(
					'filterChain' => array('#url')
				)
			),
			'template' => '<a href="{@url}"><xsl:copy-of select="@title"/><xsl:apply-templates/></a>'
		)
	);
	protected function setUp()
	{
		$this->configurator->rulesGenerator->append('ManageParagraphs');
		foreach ($this->tags as $tagName => $tagConfig)
		{
			if (isset($this->configurator->tags[$tagName]))
				continue;
			if (\is_string($tagConfig))
				$tagConfig = array('template' => $tagConfig);
			if (isset($tagConfig['attributes']))
			{
				foreach ($tagConfig['attributes'] as &$attributeConfig)
					if (isset($attributeConfig['filterChain']))
					{
						foreach ($attributeConfig['filterChain'] as &$filter)
							if (\is_string($filter) && $filter[0] === '#')
								$filter = $this->configurator->attributeFilters[$filter];
						unset($filter);
					}
				unset($attributeConfig);
			}
			$this->configurator->tags->add($tagName, $tagConfig);
		}
	}
	public function asConfig()
	{
		return array();
	}
}
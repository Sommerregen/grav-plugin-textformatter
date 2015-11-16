---
title: 'TextFormatter Test'

process:
  textformatter: true

taxonomy:
  category:
    - blog
---

[youtube]http://www.youtube.com/watch?v=QH2-TGUlwu4[/youtube]

##### Hello world 😀

The following <b onmouseover="alert(1)">plugins</b> have been enabled:

[list]
  [*][b]Autolink[/b] --- loose URLs such as http://github.com are automatically turned into links
  [*][b]BBCodes[/b]
  [list=circle]
    [*][b]bold[/b], [i]italic[/i], [u]underline[/u], [s]strikethrough[/s],
    [*][color=#f05]co[/color][color=#2f2]lo[/color][color=#02f]r,[/color]
    [*][C][URL][/C], [C:123][C][/C:123], [C][YOUTUBE][/C], [C][FLOAT][/C], and [C][LIST][/C]
    [*][C][CODE][/C] with real-time syntax highlighting via [url=http://softwaremaniacs.org/soft/highlight/en/]Highlight.js[/url]
  [code]$who = "world";
printf("Hello %s\n", $who);[/code]
  [/list]
  [*][b]Censor[/b] --- the word "apple" is censored and automatically replaced with "banana"
  [*][b]Emoticons[/b] --- one emoticon :) has been added
  [*][b]FancyPants[/b] --- some typography is enhanced, e.g. (c) (tm) and "quotes"
  [*][b]Preg[/b] --- the Preg plugin provides a way to perform generic regexp-based replacements that are HTML-safe. Here, text that matches [C]/#(?<tag>[a-z0-9]+)/i[/C] is replaced with the template [C]<a href="https://twitter.com/#!/search/%23{@tag}"><xsl:apply-templates/></a>[/C] -- For example: #PHP, #fml
  [*][b]HTMLElements[/b] --- [C]<a>[/C] and [C]<b>[/C] tags are allowed, with two whitelisted attributes for [C]<a>[/C]: [C]href[/C] and [C]title[/C]. Example: <a href="https://github.com" title="GitHub - Social Coding"><b>GitHub</b></a>
  [*][b]HTMLEntities[/b] --- HTML entities such as &hearts; are decoded
[/list]

[hr]

This is a demo of the [url=https://github.com/sommerregen/grav-plugin-textformatter title="sommerregen\grav-plugin-textformatter at GitHub.com"]Grav Plugin TextFormatter plugin[/url] using the [url=https://github.com/s9e/TextFormatter/tree/master/src/ title="s9e\TextFormatter at GitHub.com"]s9e\TextFormatter library[/url] for parsing/renderering the page.

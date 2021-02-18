<?php

namespace Hologram\Web;

use ForceField\Core\Configure;
use ForceField\Utility\Color;
use Hologram\Obfuscate\Obfuscator;
//use MatthiasMullie\Minify\CSS;

define('CSS_SELECTOR', 1);
define('CSS_RULE', 2);
define('CSS_FONT', 3);
define('CSS_KEYFRAME', 4);
define('CSS_MEDIA', 4);

final class Css
{

	private static $css_props;

	private $parent;

	private $raw_css;

	private $tokens;

	private $ntokens;

	private $pos;

	private $current_token;

	private $data;

	private $media_target;

	private $css_charset;

	private $render_mode;

	private $minify;

	private $obfuscate_css;

	public function __construct($data = null)
	{
		if (is_string($data)) {
			// Parse CSS stylesheet
			Css::parseCss($data, $this);
		} else if (is_array($data)) {
			// Parse stylesheet rules as array
		} else if (is_null($data)) {
			// Default => Empty stylesheet or data
		} else {
			// Invalid
		}

		$this->data = [
			'name' => 'stylesheet',
			'data' => []
		];

		$this->media_target = 'mobile';
		$this->tokens = [];
		$this->minify = Configure::readBool('minify.css');
		// Mobile first
		// $this->render_mode = $mode;
		// $this->clear(); // Initialize
	}

	private static function formatToString(array $entity)
	{
		$css = '';
		$name = $entity['name'];

		switch ($name) {
			case 'ruleset':
				// Ruleset
				$css = Css::obfuscateSelector($entity['tokens']) . '{';

				foreach ($entity['data'] as $rule) {
					$css .= Css::formatToString($rule);
				}

				$css .= '}';
				break;
			case 'rule':
				// Rule
				$css .= $entity['property'] . ':' . Css::glueValueTokens($entity['value_array']) . ';';
				break;
			case '@media':
				// Media Query
				if ($entity['tokens'])
					$css = '@media ' . Css::glueMediaQueryTokens($entity['tokens']) . '{';

				foreach ($entity['data'] as $e) {
					$css .= Css::formatToString($e);
				}

				if ($entity['tokens'])
					$css .= '}';
				break;
			case '@font-face':
				// Font
				$css = '@font-face{';

				foreach ($entity['data'] as $e) {
					$css .= Css::formatToString($e);
				}

				$css .= '}';
				break;
			case '@charset':
				// Charset
				$css = '@charset' . $entity['value'] . ';';
				break;
			case '@keyframes':
				// CSS Animation
				$css = '@keyframes ' . $entity['anim_name'] . '{';

				foreach ($entity['data'] as $e) {
					$css .= Css::formatToString($e);
				}

				$css .= '}';
				break;
			case '@import':
				// Import
				$css = '@import ' . Css::glueMediaQueryTokens($entity['tokens']) . ';';
				break;
			case 'stylesheet':
				foreach ($entity['data'] as $entity) {
					$css .= Css::formatToString($entity);
				}
				break;
			default:
				// Do nothing
		}

		return $css;
	}

	private static function prettyPrint(array $entity, &$tab)
	{
		$css = '';
		$name = $entity['name'];

		switch ($name) {
			case 'ruleset':
				// Ruleset
				$css = "\n" . Css::indent($tab) . Css::obfuscateSelector($entity['tokens']) . ' {';
				$tab++;

				foreach ($entity['data'] as $rule) {
					$css .= "\n" . Css::indent($tab) . Css::prettyPrint($rule, $tab);
				}

				$tab--;
				$css .= "\n" . Css::indent($tab) . '}';
				break;
			case 'rule':
				// Rule
				$css .= Css::indent($tab) . $entity['property'] . ' : ' . Css::glueValueTokens($entity['value_array']) . ';';
				break;
			case '@media':
				// Media Query
				if ($entity['tokens']) {
					$css = "\n" . Css::indent($tab) . '@media ' . Css::glueMediaQueryTokens($entity['tokens']) . ' {';
					$tab++;
				}

				foreach ($entity['data'] as $e) {
					$css .= "\n" . Css::indent($tab) . Css::prettyPrint($e, $tab);
				}

				if ($entity['tokens']) {
					$tab--;
					$css .= "\n" . Css::indent($tab) . '}';
				}
				break;
			case '@font-face':
				// Font
				$css = "\n" . Css::indent($tab) . '@font-face {';
				$tab++;

				foreach ($entity['data'] as $e) {
					$css .= "\n" . Css::indent($tab) . Css::prettyPrint($e, $tab);
				}

				$tab--;
				$css .= "\n" . Css::indent($tab) . '}';
				break;
			case '@charset':
				// Charset
				$css = "\n" . Css::indent($tab) . '@charset ' . $entity['value'] . ';';
				break;
			case '@keyframes':
				// CSS Animation
				$css = "\n" . Css::indent($tab) . '@keyframes ' . $entity['anim_name'] . ' {';

				foreach ($entity['data'] as $e) {
					$css .= "\n" . Css::indent($tab) . Css::prettyPrint($e, $tab);
				}

				$css .= "\n" . Css::indent($tab) . '}';
				break;
			case '@import':
				// Import
				$css = "\n" . Css::indent($tab) . '@import ' . Css::glueMediaQueryTokens($entity['tokens']) . ';';
				break;
			case 'stylesheet':
				foreach ($entity['data'] as $entity) {
					$css .= "\n" . Css::indent($tab) . Css::prettyPrint($entity, $tab);
				}
				$css .= "\n\n\n";
				break;
			default:
				// Do nothing
		}

		return $css;
	}

	private static function indent(&$tab)
	{
		$result = '';

		for ($i = 0; $i < $tab; $i++) {
			$result .= "  ";
		}

		return $result;
	}

	private static function obfuscateSelector(array $tokens)
	{
		$a = [];

		foreach ($tokens as $tkn) {
			if (preg_match('/^(#|\.)[a-zA-Z0-9_\-]+$/', $tkn))
				$a[] = Obfuscator::obscure($tkn);
			else
				$a[] = $tkn;
		}

		return implode('', $a);
	}

	private static function glueMediaQueryTokens(array $tokens)
	{
		$s = '';
		$prev = null;
		foreach ($tokens as $tkn) {
			// Add whitespace around media query tokens
			if (($s || strlen($s)) && $tkn != ':' && preg_match('/^[a-zA-Z0-9_\-%)]$/', $s[strlen($s) - 1]) && preg_match('/^[a-zA-Z0-9_\-]+|[(]$/', $tkn))
				$s .= ' ';
			
			$s .= $tkn;
			$prev = $tkn;
		}

		return $s;
	}

	private static function glueValueTokens(array $tokens)
	{
		$s = '';

		foreach ($tokens as $tkn) {
			if (($s || strlen($s)) && $tkn != ':' && preg_match('/^[a-zA-Z0-9_\-%]$/', $s[strlen($s) - 1]) && preg_match('/^[a-zA-Z0-9_\-]+$/', $tkn[0]))
				$s .= ' ';

			$s .= $tkn;
		}

		return $s;
	}

	private static function parseCss($css_string, Css $css)
	{
		$tokens = Css::tokenize($css_string);
		$len = count($tokens);

		for ($pos = 0; $pos < $len; $pos++) {
			$tkn = $tokens[$pos];

			switch ($tkn) {
				case '@media':
					if (!Css::parseMediaQuery($css->data, $tokens, $len, $pos))
						return $css; // Parse error
					break;
				case '@font-face':
					if (!Css::parseFontFace($css->data, $tokens, $len, $pos))
						return $css; // Parse error
					break;
				case '@keyframes':
					if (!Css::parseKeyframes($css->data, $tokens, $len, $pos))
						return $css; // Parse error
					break;
				case '@charset':
					if (!Css::parseCharset($css->data, $tokens, $len, $pos))
						return $css; // Parse error
					break;
				case '@import':
					if (!Css::parseImport($css->data, $tokens, $len, $pos))
						return $css; // Parse error
					break;
				default:
					// Ruleset
					if (!Css::parseRuleSet($css->data, $tokens, $len, $pos))
						return $css; // Parse error
			}
		}

		$css->raw_css = $css_string;
		return $css;
	}

	private static function parseRuleSet(array &$parent, array $tokens, $len, &$pos)
	{
		$selector = '';
		$selector_tokens = [];

		for (; $pos < $len; $pos++) {
			$tkn = $tokens[$pos];

			if ($tkn == '{') {

				$pos++; // Next token

				$ruleset = [
					'name' => 'ruleset',
					'selector' => $selector,
					'tokens' => $selector_tokens,
					'data' => []
				];

				while ($pos < $len) {

					$tkn = $tokens[$pos];

					if ($tkn == '}') {

						$parent['data'][] = $ruleset;
						return true;
					}

					if (!Css::parseRule($ruleset, $tokens, $len, $pos))
						return false; // Parse error

					$pos++; // To next token
				}
			} else {

				$selector .= $tkn;
				$selector_tokens[] = $tkn;
			}
		}

		return false;
	}

	private static function parseRule(array &$ruleset, array $tokens, $len, &$pos)
	{
		if ($pos >= $len)
			return false; // Fail

		$property = $tokens[$pos];

		$pos++;

		if ($pos < $len && $tokens[$pos] == ':') {

			$pos++; // To value

			if ($pos < $len && !in_array($tokens[$pos], [
				';',
				'{',
				'}'
			])) {
				$value = [];

				for (; $pos < $len; $pos++) {

					$tkn = $tokens[$pos];

					if ($tkn == ';') {
						// Success !
						$ruleset['data'][] = [
							'name' => 'rule',
							'property' => $property,
							'value_array' => $value,
							'value_string' => Css::glueValueTokens($value)
						];
						return true;
					} else
						$value[] = $tkn;
				}
			}
		}

		// Invalid syntax
		return false;
	}

	private static function parseMediaQuery(array &$parent, array $tokens, $len, &$pos)
	{
		$media = [
			'name' => '@media',
			'tokens' => [],
			'data' => []
		];
		$pos++; // Skip @media

		for (; $pos < $len; $pos++) {

			$tkn = $tokens[$pos];

			if ($tkn == ';')
				return false; // Parse error
			else if ($tkn == '{') {
				$pos++; // To

				while ($pos < $len) {

					$tkn = $tokens[$pos];

					if ($tkn == '}') {
						// Success!
						$parent['data'][] = $media;
						return true;
					} else if (!Css::parseRuleSet($media, $tokens, $len, $pos)) {
						// Parse error
						break;
					}

					$pos++;
				}

				// Parse error
				break;
			} else {
				$media['tokens'][] = $tkn;
				// echo $tkn . "\n";
			}
		}

		// Invalid syntax
		return false;
	}

	private static function parseFontFace(array &$parent, array $tokens, $len, &$pos)
	{
		$fontface = [
			'name' => '@font-face',
			'data' => []
		];

		$pos++; // Skip @font-face

		if ($pos < $len && $tokens[$pos] == '{') {
			$pos++; // To expected property

			for (; $pos < $len; $pos++) {
				$tkn = $tokens[$pos];

				if ($tkn == '}') {
					// Success!
					$parent['data'][] = $fontface;
					return true;
				} else if (!Css::parseRule($fontface, $tokens, $len, $pos)) {
					// Parse error
					break;
				}
			}
		}

		// Fail
		return false;
	}

	private static function parseCharset(array &$parent, array $tokens, $len, &$pos)
	{
		$pos++; // Skip @charset
		if ($pos < $len) {
			$tkn = $tokens[$pos];

			if ($tkn[0] == '"' || $tkn[0] == "'") {
				$value = $tkn;
				$pos++;

				$tkn = $pos < $len ? $tokens[$pos] : null;

				if ($tkn == ';') {
					// Success!
					$parent['data'][] = [
						'name' => '@charset',
						'value' => $value
					];
					return true;
				}
			}
		}

		// Fail
		return false;
	}

	private static function parseKeyframes(array &$parent, array $tokens, $len, &$pos)
	{
		$pos++; // Skip @keyframes

		if ($pos < $len) {

			$anim_name = $tokens[$pos];

			$keyframes = [
				'name' => '@keyframes',
				'anim_name' => $anim_name,
				'data' => []
			];

			$pos++; // To expected {

			if ($pos < $len && $tokens[$pos] == '{') {

				$pos++; // To next

				for (; $pos < $len; $pos++) {
					$tkn = $tokens[$pos];

					if ($tkn == '}') {
						// Success!
						$parent['data'][] = $keyframes;
						return true;
					} else if (!Css::parseRuleSet($keyframes, $tokens, $len, $pos)) {
						// Parse error
						break;
					}
				}
			}
		}

		// Fail
		return false;
	}

	private static function parseImport(array &$parent, array $tokens, $len, &$pos)
	{
		$import = [
			'name' => '@import',
			'tokens' => []
		];

		$pos++; // To next token

		for (; $pos < $len; $pos++) {
			$tkn = $tokens[$pos];

			if ($tkn == ';') {
				// Success!
				$parent['data'][] = $import;
				return true;
			} else
				$import['tokens'][] = $tkn;
		}

		// Fail
		return false;
	}

	private static function toCssProperty($name)
	{
		if (!is_array(Css::$css_props))
			Css::$css_props = Configure::readArray('css.properties', []);

		if (array_key_exists($name, Css::$css_props))
			return Css::$css_props[$name] ? Css::$css_props[$name] : $name;
		else
			return NULL; // Not a CSS property
	}

	private static function readToken($pattern, $css, $len, &$pos)
	{
		$tkn = '';

		for (; $pos < $len; $pos++) {
			$tkn .= $css[$pos];
			$next = $pos < $len - 1 ? $css[$pos + 1] : null;

			if (is_null($next) || !preg_match($pattern, $tkn . $next))
				break; // End of token stream reached or encountered end of token
		}

		return $tkn;
	}

	private static function readCssEntity($css, $len, &$pos, array &$tokens, Css $parent = null)
	{

		// Read media query and CSS animation style blocks
		$a = [];
		$a[] = $entity = Css::readToken('/^@[a-zA-Z0-9_\-]+$/', $css, $len, $pos);
		$pos++; // Next

		for (; $pos < $len; $pos++) {

			$a[] = Css::readToken('/^[^\s\n():]+$/', $css, $len, $pos);
		}

		foreach ($a as $tkn) {

			$tkn = trim($tkn);

			if ($tkn && !preg_match('/^[\s\n]+$/', $tkn)) {
				$tokens[] = $tkn;

				if ($parent) {
					$parent->add($tkn);
				}
			}
		}

		if ($parent)
			$parent->pop();

		return $entity;
	}

	private static function readCssSelector($css, $len, &$pos, array &$tokens)
	{
		$selector_tokens = [];

		// Tokenize selector
		for ($pos = 0; $pos < $len; $pos++) {

			$char = $css[$pos];
			$next = $pos < $len - 1 ? $css[$pos + 1] : null;

			switch (true) {
				case preg_match('/^[\s\n]$/', $char):
					// Descendent selector (also ignore whitespace)
					Css::readToken('/^[\s\n]+$/', $css, $len, $pos);
					$selector_tokens[] = ' ';
					break;
				case $char == '.':
					// Class selector
					$selector_tokens[] = Css::readToken('/^\.[a-zA-Z0-9_\-]+$/', $css, $len, $pos);
					break;
				case $char == '#':
					// ID selector
					$selector_tokens[] = Css::readToken('/^\#[a-zA-Z0-9_\-]+$/', $css, $len, $pos);
					break;
				case $char == '@':
					// CSS entity
					$selector_tokens[] = Css::readToken('/^@[a-zA-Z0-9_\-]+$/', $css, $len, $pos);
					break;
				case preg_match('/^[a-zA-Z0-9]$/', $char):
					// Element selector
					$selector_tokens[] = Css::readToken('/^[a-zA-Z0-9_\-]+$/', $css, $len, $pos);
					break;
				case $char == ':':
					// Pseudo selector
					$selector_tokens[] = Css::readToken('/^::?[a-zA-Z0-9_\-()]+$/', $css, $len, $pos);
					break;
				case $char == '[':
					// Attribute selector
					$tkn = '';
					while ($pos < $len) {
						$tkn .= $css[$pos];
						if ($css[$pos] == ']')
							break;
						$pos++;
					}
					$selector_tokens[] = $tkn;
					break;
				case ':':
					// Pseudo selector
					$selector_tokens[] = Css::readToken('/^\:[a-zA-Z0-9_\-]$/', $css, $len, $pos);
					break;
				case $char == '>': // Child selector
				case $char == '+': // Sibling selector
				case $char == '~': // Sibling
				case $char == ',': // Comma
				case $char == '*': // Select all
				default:
					if (preg_match('/^[a-zA-Z0-9%]$/', $char))
						$selector_tokens[] = Css::readToken('/^[a-zA-Z0-9%]+$/', $css, $len, $pos);
					else
						$selector_tokens[] = $char; // And all other unexpected tokens
			}

			if (is_null($next) || $next == '{' || $next == ';')
				break;
		}

		$a = []; // Normalized tokens (remove unintended whitespace)
		$n = count($selector_tokens);
		$tkn = null;

		for ($i = 0; $i < $n; $i++) {
			$prev = $tkn;
			$tkn = $selector_tokens[$i];
			$next = $i < $n - 1 ? $selector_tokens[$i + 1] : null;

			if ($tkn == ' ') {

				if (($prev && in_array($prev, [
					' ',
					',',
					'>',
					'+',
					'~'
				])) || ($next && in_array($next, [
					' ',
					',',
					'>',
					'+',
					'~'
				])))
					continue;
			}

			$a[] = $tkn;
		}

		$tokens = array_merge($tokens, $a);
		return implode('', $a);
	}

	private static function readCssValue($css, $len, &$pos, array &$tokens)
	{
		$a = [];
		$a[] = $name = Css::readToken('/^([\{\}\(\)\:\;]|[\s\n]+|[^\s\n\{\}\(\)\:\;]+)$/', $css, $len, $pos);
		$pos++; // To next

		// Tokenize selector
		for (; $pos < $len; $pos++) {

			$char = $css[$pos];

			if ($char == '"' || $char == "'")
				$a[] = Css::readStringToken($css, $len, $pos);
			else
				$a[] = Css::readToken('/^([\{\}\(\)\:\;]|[\s\n]+|[^\s\n\{\}\(\)\:\;]+)$/', $css, $len, $pos);
		}

		foreach ($a as $tkn) {
			$tkn = trim($tkn);

			if ($tkn == '0' || ($tkn && !preg_match('/^[\s\n]+$/', $tkn)))
				$tokens[] = $tkn;
		}

		return $name;
	}

	private static function readStringToken($css, $len, &$pos)
	{
		$delimiter = $css[$pos];
		$string = $delimiter;
		$prev = null;
		$pos++;

		for (; $pos < $len; $pos++) {
			$char = $css[$pos];
			$string .= $char;

			if ($char == $delimiter && $prev != "\\") {
				break;
			} else if ($char == "\n")
				break;

			$prev = $char;
		}

		return $string;
	}

	private function get($name, $get_all = FALSE)
	{
		if (Css::$css_props && array_key_exists($name, Css::$css_props)) {
			$a = [];
			for ($i = count($this->data); $i >= 0; $i--) {
				if (array_key_exists($name, $this->data[$i]))
					return $this->data[$i][$name];
			}
		} else
			throw new \Exception('Attempting to access unsupported CSS property "' . $name . '"');
	}

	private function add($tkn)
	{
		return $this->tokens[] = $tkn;
	}

	public static function tokenize($css_string)
	{
		$css_string = trim($css_string); // Trim whitespace padding
		$matches = [];
		$queries = null;

		
		//while (preg_match('/(\@media)[\s]*[a-zA-Z][a-zA-Z0-9_\-]*/', $css_string, $matches, PREG_OFFSET_CAPTURE | PREG_UNMATCHED_AS_NULL)) {
			/*
			if (is_null($queries)) {
				// Load queries
				$queries = Configure::readArray('css.mediaQueries', []);
			}

			$tpl = $matches[0][0];
			$name = str_replace('@media ', '', $tpl);
			$len = strlen($tpl);
			if (!array_key_exists($name, $queries))
				continue; //die('Invalid query reference "' . $name . '".');
			$query = $queries[$name];
			$css_string = substr_replace($css_string, '@media ' . $query, $matches[0][1], $len);
		}*/
		
		preg_match_all('/(\@media)[\s]*[a-zA-Z][a-zA-Z0-9_\-]*/', $css_string, $matches, PREG_OFFSET_CAPTURE);
		//print_r($matches);die();
		foreach($matches as $arr)
		{
			foreach($arr as $m)
			{
				if (is_null($queries))
				{
					// Load queries
					$queries = Configure::readArray('css.mediaQueries', []);
				}

				$tpl = $m[0];
				$name = trim(str_replace('@media', '', $tpl));
				$len = strlen($tpl);
				if(!array_key_exists($name, $queries))
					continue;
				$query = $queries[$name];
				$css_string = substr_replace($css_string, '@media ' . $query, $m[1], $len);
			}

			// end operation
			break;
		}
		
		$segments = [];
		$buffer = '';
		$arr = [
			'{',
			'}',
			';'
		];
		$fail = false;
		$len = strlen($css_string);

		// Dissecting the CSS string into segments
		for ($pos = 0; $pos < $len && !$fail; $pos++) {

			$ch = $css_string[$pos];
			$next = $pos < $len - 1 ? $css_string[$pos + 1] : null;

			if ($ch == '/' && $next == '*') {
				// Ignore comments
				$pos++;

				while ($pos < $len) {
					$ch = $css_string[$pos];
					$next = $pos < $len - 1 ? $css_string[$pos + 1] : null;
					$pos++;

					if ($ch == '*' && $next == '/') {
						// End of comment
						break;
					}
				}

				continue;
			} else if ($ch == "\n") {
				// Ignore linefeeds
				continue;
			}

			if (in_array($ch, $arr)) {
				if ($buffer) {
					// Flush buffer without whitespace padding
					$segments[] = trim($buffer);
					$buffer = '';
				}

				$segments[] = $ch;
			} else
				$buffer .= $ch;
		}

		// Flush buffer if exists
		if ($buffer)
			$segments[] = $buffer;

		// Visit each segment
		$tokens = [];
		$nsegs = count($segments);

		for ($i = 0; $i < $nsegs && !$fail; $i++) {

			$seg = $segments[$i];
			$next_seg = $i < $nsegs - 1 ? $segments[$i + 1] : null;
			$len = strlen($seg);

			for ($pos = 0; $pos < $len; $pos++) {

				$ch = $seg[$pos];

				switch ($ch) {
					case '@':
						// Read media query, CSS animation, etc.
						$data = Css::readCssValue($seg, $len, $pos, $tokens);
						break;
					case '{':
					case ';':
					case '}':
						$tokens[] = $ch;
						break;
					default:
						if ($next_seg == ';') {
							// CSS rule
							$data = Css::readCssValue($seg, $len, $pos, $tokens);
							// $i ++; // Skip ;
							// $tokens[] = $next_seg;
						} else if ($next_seg == '{' || !$next_seg) {
							// Css selector
							$data = Css::readCssSelector($seg, $len, $pos, $tokens);
							// $i ++; // Skip {
							// if ($next_seg)
							// $tokens[] = $next_seg;
						} else if ($next_seg) {
							// Parse error (unexpected token)
							$fail = true;
						}
						break;
				}
			}
		}

		// Returns resulting stylesheet or the token array (for Css::tokenize())
		return $tokens;
	}

	public static function parse($css)
	{
		return Css::parseCss($css, new Css());
	}

	public static function minify($css)
	{
		return (string) Css::parse($css)->minified(true);
	}

	public function obfuscate()
	{
	}

	public function append($arg1, $arg2 = NULL)
	{
		if ($arg2) {
			// TODO Parse
			$arg1 = trim($arg1);
			if (!StringUtil::endsWith(';', $arg1, TRUE))
				$arg1 .= ';';
			$this->data[] = [
				'property' => $arg1,
				'value' => NULL
			];
		} else {
			$this->data[] = [
				'property' => $arg1,
				'value' => $arg2
			];
		}
		return $this;
	}

	public function find($selector, $media = NULL)
	{
		// TODO Simplify selector
		foreach ($this->data as $q) {
			if (is_null($media) && $q->selector_string() == $selector)
				return $q;
			else if (!is_null($media) && $q->selector_string() == $selector && $q->media() == $media)
				return $q;
		}
		// TODO More advanced comparison
		// Probably by parsing the css selector and using a compare() method for deeper comparison
		return NULL;
	}

	public function save()
	{
		return (string) $this;
	}

	public function minified($minified = null)
	{
		if (func_num_args() > 0) {
			$this->minify = $minified;
			return $this;
		}

		return $this->minify;
	}

	public function __call($name, $args)
	{
		if (Css::$css_props && array_key_exists($name, Css::$css_props)) {
			$name = Css::toCssProperty($name);
			if (count($args) > 0) {
				$value = count($args) > 0 ? $args[0] : NULL;
				$media = count($args) > 1 ? $args[1] : NULL;
				$prefix = count($args) > 2 ? $args[1] : NULL;
				if ($media) {
					$q = $this->css()->find($this->selector, $media);
					$this->rule($this->selector, $media)->$name($value, null, $prefix);
					return $this; // Keep this method chained
				} else {
					// Store normal css
					if ($this->css()->rendersVendorPrefixes()) {
						if (is_array($prefix))
							$prefix = Css::format_prefix($prefix);
						else if (is_string($prefix))
							$prefix = preg_split('/[\,\s]+/', $prefix); // Store
						else if ($prefix == null)
							$prefix = CSS::prefix($name); // Get prefix
					}
					if ($value != null) {
						if (is_array($prefix)) {
							foreach ($prefix as $p) {
								$this->data[] = [
									'property' => $p . $name,
									'value' => $value
								];
							}
						} else {
							if ($value instanceof Color)
								$value = $value->css(); // Convert to CSS string
							$this->data[] = [
								'property' => $name,
								'value' => $value
							];
						}
					}
				}
				return $this;
			} else
				return $this->get(Css::toCssProperty($name));
		} else
			throw new \Exception('Attempting to write unsupported CSS property "' . $name . '"');
	}

	public function __toString()
	{
		if ($this->minify)
			return Css::formatToString($this->data);
		else {
			$tab = 0;
			return Css::prettyPrint($this->data, $tab);
		}
	}
}

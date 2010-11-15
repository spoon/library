<?php

/**
 * Spoon Library
 *
 * This source file is part of the Spoon Library. More information,
 * documentation and tutorials can be found @ http://www.spoon-library.com
 *
 * @package		spoon
 * @subpackage	template
 *
 *
 * @author		Davy Hellemans <davy@spoon-library.com>
 * @author 		Tijs Verkoyen <tijs@spoon-library.com>
 * @author		Dave Lens <dave@spoon-library.com>
 * @since		0.1.1
 */


/**
 * Spoon Library
 *
 * This source file is part of the Spoon Library. More information,
 * documentation and tutorials can be found @ http://www.spoon-library.com
 *
 * @package		spoon
 * @subpackage	template
 *
 *
 * @author		Davy Hellemans <davy@spoon-library.com>
 * @author		Matthias Mullie <matthias@spoon-library.com>
 * @since		1.0.0
 */
class SpoonTemplateCompiler
{
	/**
	 * Cache directory location
	 *
	 * @var	string
	 */
	private $cacheDirectory = '.';


	/**
	 * Compile directory location
	 *
	 * @var	string
	 */
	private $compileDirectory = '.';


	/**
	 * Working content
	 *
	 * @var	string
	 */
	private $content;


	/**
	 * Always recompile
	 *
	 * @var	bool
	 */
	private $forceCompile = false;


	/**
	 * List of form objects
	 *
	 * @var	array
	 */
	private $forms = array();


	/**
	 * List of used iterations
	 *
	 * @var	array
	 */
	private $iterations = array();


	/**
	 * Counter of used iterations (each iteration will get a unique number)
	 *
	 * @var	int
	 */
	private $iterationsCounter;


	/**
	 * Cached list of the modifiers
	 *
	 * @var	array
	 */
	private $modifiers = array();


	/**
	 * Is the content already parsed
	 *
	 * @var	bool
	 */
	private $parsed = false;


	/**
	 * Template file
	 *
	 * @var	string
	 */
	private $template;


	/**
	 * List of compiler-interpreted variables
	 *
	 * @var	array
	 */
	private $templateVariables = array();


	/**
	 * List of variables
	 *
	 * @var	array
	 */
	private $variables = array();


	/**
	 * Class constructor.
	 *
	 * @return	void
	 * @param	string $template	The name of the template to compile.
	 * @param	array $variables	The list of possible variables.
	 */
	public function __construct($template, array $variables)
	{
		$this->template = (string) $template;
		$this->variables = $variables;
	}


	/**
	 * Retrieve the compiled name for this template.
	 *
	 * @return	string				The unique filename used to store the compiled template in the compile directory.
	 * @param	string $template	The name of the template.
	 */
	private function getCompileName($template)
	{
		return md5(realpath($template)) .'_'. basename($template) .'.php';
	}


	/**
	 * Retrieve the content.
	 *
	 * @return	string	The php compiled template.
	 */
	public function getContent()
	{
		if(!$this->parsed) $this->parse();
		return $this->content;
	}


	/**
	 * Parse the template.
	 *
	 * @return	void
	 */
	private function parse()
	{
		// not yet parsed
		if(!$this->parsed)
		{
			// add to the list of parsed files
			$this->files[] = $this->getCompileName($this->template);

			// map modifiers
			$this->modifiers = SpoonTemplateModifiers::getModifiers();

			// set content
			$this->content = SpoonFile::getContent($this->template);

			// strip php code
			$this->content = $this->stripCode($this->content);

			// strip comments
			$this->content = $this->stripComments($this->content);

			// prepare iterations
			$this->content = $this->prepareIterations($this->content);

			// parse variables
			$this->content = $this->parseVariables($this->content);

			// parse iterations
			$this->content = $this->parseIterations($this->content);

			// includes
			$this->content = $this->parseIncludes($this->content);

			// parse options
			$this->content = $this->parseOptions($this->content);

			// parse cache tags
//			$this->content = $this->parseCache($this->content);

			// parse forms
//			$this->content = $this->parseForms($this->content);

			// @todo: aw common, make this nice :)
			/**
			 * Now loop these vars again, but this time parse them in the
			 * content we're actually working with.
			 */
			foreach($this->templateVariables as $key => $value)
			{
				$this->content = str_replace('[$'. $key .']', '<?php echo '. $value['content'] .'; ?>', $this->content);
			}

			// while developing, you might want to know about the undefined indexes
			$errorReporting = (SPOON_DEBUG) ? 'E_ALL | E_STRICT' : 'E_WARNING';
			$displayErrors = (SPOON_DEBUG) ? 'On' : 'Off';

			// add error_reporting setting
			$this->content = '<?php error_reporting('. $errorReporting .'); ini_set(\'display_errors\', \''. $displayErrors .'\'); ?>'. "\n". $this->content;

			// parsed
			$this->parsed = true;
		}
	}


	/**
	 * Parse the cache tags.
	 *
	 * @return	string				The updated content, containing the parsed cache tags.
	 * @param	string $content		The content that may contain the parse tags.
	 */
	private function parseCache($content)
	{
		// regex pattern
		$pattern = '/\{cache:([a-z0-9_\.\{\$\}]+)\}.*?\{\/cache:\\1\}/is';

		// find matches
		if(preg_match_all($pattern, $content, $matches))
		{
			// loop matches
			foreach($matches[1] as $match)
			{
				// variable
				$variable = $this->getVariableString($match);

				// init vars
				$search = array();
				$replace = array();

				// search for
				$search[0] = '{cache:'. $match .'}';
				$search[1] = '{/cache:'. $match .'}';

				// replace with
				$replace[0] = "<?php if(!\$this->isCached(". $variable .")): ?>\n<?php ob_start(); ?>";
				$replace[1] = "<?php SpoonFile::setContent(\$this->cacheDirectory .'/'. $variable .'_cache.tpl', ob_get_clean()); ?>\n<?php endif; ?>\n";
				$replace[1] .= "<?php require \$this->cacheDirectory .'/'. $variable .'_cache.tpl'; ?>";

				// execute
				$content = str_replace($search, $replace, $content);
			}
		}

		return $content;
	}


	/**
	 * Parses the cycle tags in the given content.
	 *
	 * @return	string				The updated content, containing the parsed cycle tags.
	 * @param	string $content		The content that may contain the cycle tags.
	 */
	private function parseCycle($content, $iteration)
	{
		// regex pattern
		$pattern = '/\{cycle((:(("[^"]*?"|\'[^\']*?\')|\[\$[a-z0-9]+\]|[0-9]+))+)\}/is';

		// find matches
		if(preg_match_all($pattern, $content, $matches, PREG_SET_ORDER))
		{
			// loop matches
			foreach($matches as $i => $match)
			{
				// init vars
				$cycle = '';

				// cycles pattern
				$pattern = '/:(("[^"]*?"|\'[^\']*?\')|\[\$[a-z0-9]+\]|[0-9]+)/';

				// has cycles
				if(preg_match_all($pattern, $match[1], $arguments))
				{
					$cycle .= implode(', ', $arguments[1]);
				}

				// parse variables into cycle
				foreach($this->templateVariables as $key => $value)
				{
					$cycle = str_replace('[$'. $key .']', $value['content'], $cycle);
				}

				// search & replace
				$search = $match[0];
				$replace = '<?php echo $this->cycle('. $iteration .'[\'i\'], array('. $cycle .')); ?>';

				$content = str_replace($search, $replace, $content);
			}
		}

		return $content;
	}


	/**
	 * Parse the forms.
	 *
	 * @return	string				The updated content, containing the parsed form tags.
	 * @param	string $content		The content that may contain the form tags.
	 */
	private function parseForms($content)
	{
		// regex pattern
		$pattern = '/\{form:([a-z0-9_]+?)\}?/siU';

		// find matches
		if(preg_match_all($pattern, $content, $matches))
		{
			// loop matches
			foreach($matches[1] as $name)
			{
				// form object with that name exists
				if(isset($this->forms[$name]))
				{
					// init vars
					$search = array();
					$replace = array();

					// start & close tag
					$search = array('{form:'. $name .'}', '{/form:'. $name .'}');
					$replace[0] = '<form action="<?php echo $this->forms[\''. $name .'\']->getAction(); ?>" method="<?php echo $this->forms[\''. $name .'\']->getMethod(); ?>"<?php echo $this->forms[\''. $name .'\']->getParametersHTML(); ?>>' ."\n<div>\n";
					$replace[0] .= $this->forms[$name]->getField('form')->parse();

					// form tokens were used
					if($this->forms[$name]->getUseToken()) $replace[0] .= "\n". '<input type="hidden" name="form_token" id="<?php echo $this->forms[\''. $name .'\']->getField(\'form_token\')->getAttribute(\'id\'); ?>" value="<?php echo $this->forms[\''. $name .'\']->getField(\'form_token\')->getValue(); ?>" />';

					// close form & replace it
					$replace[1] = "\n</div>\n</form>";
					$content = str_replace($search, $replace, $content);
				}
			}
		}

		return $content;
	}


	/**
	 * Parse the include tags.
	 *
	 * @return	string				The updated content, containing the parsed include tags.
	 * @param	string $content		The content that may contain the include tags.
	 */
	private function parseIncludes($content)
	{
		// regex pattern
		$pattern = '/\{include:file=(([\'"])[a-z0-9\-_\.:\/\[\$\]]+\\2)\}/is'; // @todo: more characters are actually allowed as filenames; get a list of it!

		// find matches
		if(preg_match_all($pattern, $content, $matches, PREG_SET_ORDER))
		{
			// loop matches
			foreach($matches as $match)
			{
				// search string
				$search = $match[0];

				// parse variables into include
				foreach($this->templateVariables as $key => $value)
				{
					$match[1] = str_replace('[$'. $key .']', '\'. '. $value['content'] .' .\'', $match[1]);
					$match[0] = str_replace('[$'. $key .']', '<?php echo '. $value['content'] .'; ?>', $match[0]);
				}

				// replace string
				$replace = '<?php if($this->getForceCompile()) $this->compile(\''. dirname(realpath($this->template)) .'\', '. $match[1] .');
				$return = @include $this->getCompileDirectory() .\'/\'. $this->getCompileName('. $match[1] .', \''. dirname(realpath($this->template)) .'\');
				if($return === false && $this->compile(\''. dirname(realpath($this->template)) .'\', '. $match[1] .'))
				{
					$return = @include $this->getCompileDirectory() .\'/\'. $this->getCompileName('. $match[1] .', \''. dirname(realpath($this->template)) .'\');
				}'."\n";
				if(SPOON_DEBUG) $replace .= 'if($return === false)
				{
					?>'. $match[0] .'<?php
				}';

				// replace it
				$content = str_replace($search, $replace, $content);
			}
		}

		return $content;
	}


	/**
	 * Parse the iterations (recursively).
	 *
	 * @return	string				The updated content, containing the parsed iteration tags.
	 * @param	string $content		The content that may contain the iteration tags.
	 */
	private function parseIterations($content)
	{
		// fetch iterations
		$pattern = '/(\{iteration_([0-9]+):([a-z0-9_]*)((\.[a-z0-9_]*)*)((-\>[a-z0-9_]*((\.[a-z0-9_]*)*))?)\})(.*?)(\{\/iteration_\\2:\\3\\4\\6\})/is';

		// find matches
		if(preg_match_all($pattern, $content, $matches, PREG_SET_ORDER))
		{
			// loop iterations
			foreach($matches as $match)
			{
				// base variable names
				$iteration = '$this->iterations['. $match[2] .']';

				// @todo: code repitition is too big in the following 50 lines: rewrite

				// variable within iteration
				if($match[6] != '')
				{
					// base
					$variable = '${\''. $match[3] .'\'}';

					// add separate chunks
					foreach(explode('.', ltrim($match[4], '.')) as $chunk)
					{
						// make sure it's a valid chunk
						if(!$chunk) continue;

						// append pieces
						$variable .= "['". $chunk ."']";
						$iteration .= "['". $chunk ."']";
					}

					// iteration value
					if(isset($match[6]) && $match[6])
					{
						// set variable
						$chunks = explode('.', str_replace('->', '', $match[6]));

						// append pieces
						$internalVariable = (string) array_shift($chunks);
						$variable .= "['". $internalVariable ."']";
						$iteration .= "['". $internalVariable ."']";
						$internalVariable = '${\''. $internalVariable .'\'}';

						// add seperate chunks
						foreach((array) $chunks as $chunk)
						{
							// append pieces
							$variable .= "['". $chunk ."']";
							$iteration .= "['". $chunk ."']";
							$internalVariable .= "['". $chunk ."']";
						}
					}
				}

				// regular variable
				else
				{
					// base
					$variable = '$this->variables[\''. $match[3] .'\']';
					$internalVariable = '${\''. $match[3] .'\'}';

					// add separate chunks
					foreach(explode('.', ltrim($match[4], '.')) as $chunk)
					{
						// make sure it's a valid chunk
						if(!$chunk) continue;

						// append pieces
						$variable .= "['". $chunk ."']";
						$iteration .= "['". $chunk ."']";
						$internalVariable .= "['". $chunk ."']";
					}
				}

				// iteration content: parse inner variables & iterations, parse recursively, parse cycle tags
				$innerContent = $match[10];
				$innerContent = $this->parseIterations($innerContent);
				$innerContent = $this->parseCycle($innerContent, $iteration);

// @todo: save current $internalVariable info and reset after iteration (in case we're looping the same iteration nested in one another
				// start iteration
				$templateContent = '<?php';
				if(SPOON_DEBUG) $templateContent .= '
				if(!isset('. $variable .'))
				{
					?>{iteration:'. $match[3] . $match[4] . $match[6] .'}<?php
					'. $variable .' = array(\'\');
					'. $iteration .'[\'fail\'] = true;
				}';
				$templateContent .= '
				'. $iteration .'[\'iteration\'] = '. $variable .';
				'. $iteration .'[\'i\'] = 1;
				'. $iteration .'[\'count\'] = count('. $iteration .'[\'iteration\']);
				foreach((array) '. $iteration .'[\'iteration\'] as '. $internalVariable .')
				{
					if(!isset('. $internalVariable .'[\'first\']) && '. $iteration .'[\'i\'] == 1) '. $internalVariable .'[\'iteration\'][\'first\'] = true;
					if(!isset('. $internalVariable .'[\'last\']) && '. $iteration .'[\'i\'] == '. $iteration .'[\'count\']) '. $internalVariable .'[\'iteration\'][\'last\'] = true;
					if(isset('. $internalVariable .'[\'formElements\']) && is_array('. $internalVariable .'[\'formElements\']))
					{
						foreach('. $internalVariable .'[\'formElements\'] as $name => $object)
						{
							'. $internalVariable .'[$name] = $object->parse();
							'. $internalVariable .'[$name .\'Error\'] = (method_exists($object, \'getErrors\') && $object->getErrors() != \'\') ? \'<span class="formError">\'. $object->getErrors() .\'</span>\' : \'\';
						}
					}
				?>';

				// append inner content
				$templateContent .= $innerContent;

				// close iteration
				$templateContent .= '<?php
					'. $iteration .'[\'i\']++;
				}';
				if(SPOON_DEBUG) $templateContent .= '
				if(isset('. $iteration .'[\'fail\']) && '. $iteration .'[\'fail\'] == true)
				{
					?>{/iteration:'. $match[3] . $match[4] . $match[6] .'}<?php
				}';
				$templateContent .= '?>';

				$content = str_replace($match[0], $templateContent, $content);
			}
		}

		return $content;
	}


	/**
	 * Parse the options in the given content & scope.
	 *
	 * @return	string				The updated content, containing the parsed option tags.
	 * @param	string $content		The content that may contain the option tags.
	 */
	private function parseOptions($content)
	{
		// regex pattern
		$pattern = '/\{option:((\!)?([a-z0-9_]*)((\.[a-z0-9_]*)*)((-\>[a-z0-9_]*((\.[a-z0-9_]*)*))?))}.*?\{\/option:\\1\}/is';

		// init vars
		$options = array();

		// we want to keep parsing options until none can be found
		while(1)
		{
			// find matches
			if(preg_match_all($pattern, $content, $matches, PREG_SET_ORDER))
			{
				// init var
				$correctOptions = false;

				// loop matches
				foreach($matches as $match)
				{
					// base variable
					$variable = '';

					// variable within iteration
					if(isset($match[6]) && $match[6] != '')
					{
						// base
						$variable = '${\''. $match[3] .'\'}';

						// add separate chunks
						foreach(explode('.', ltrim($match[4] . str_replace('->', '.', $match[6]), '.')) as $chunk)
						{
							$variable .= "['". $chunk ."']";
						}
					}

					// regular variable
					else
					{
						// base
						$variable = '$this->variables';

						// add separate chunks
						foreach(explode('.', $match[3] . $match[4]) as $chunk)
						{
							$variable .= "['". $chunk ."']";
						}
					}

					// already matched
					if(in_array($match[1], $options)) continue;

					// init vars
					$search = array();
					$replace = array();

					// not yet used
					$options[] = $match[1];

					// search for
					$search[] = '{option:'. $match[1] .'}';
					$search[] = '{/option:'. $match[1] .'}';

					// positive option
					if($match[2] != '!')
					{
						// replace with
						$replace[] = '<?php if(isset('. $variable .') && count('. $variable .') != 0 && '. $variable .' != \'\' && '. $variable .' !== false): ?>';
					}

					// negative option
					else
					{
						// inverse option
						$replace[] = '<?php if(!isset('. $variable .') || count('. $variable .') == 0 || '. $variable .' == \'\' || '. $variable .' === false): ?>';
					}
					$replace[] = '<?php endif; ?>';

					// go replace
					$content = str_replace($search, $replace, $content);

					// at least one correct option
					$correctOptions = true;
				}

				// no correct options were found
				if(!$correctOptions) break;
			}

			// no matches
			else break;
		}

		return $content;
	}


	/**
	 * Parse the template to a file.
	 *
	 * @return	void
	 */
	public function parseToFile()
	{
		SpoonFile::setContent($this->compileDirectory .'/'. $this->getCompileName($this->template), $this->getContent());
	}


	/**
	 * Parse all the variables in this string.
	 *
	 * @return	string				The updated content, containing the parsed variables.
	 * @param	string $content		The content that may contain variables.
	 */
	protected function parseVariables($content)
	{
		// regex pattern
		$pattern = '/\{\$([a-z0-9_]*)((\.[a-z0-9_]*)*)(-\>[a-z0-9_]*((\.[a-z0-9_]*)*))?((\|[a-z_][a-z0-9_]*(:(("[^"]*?"|\'[^\']*?\')|\[\$[a-z0-9]+\]|[0-9]+))*)*)\}/i';

		// we want to keep parsing vars until none can be found
		while(1)
		{
			// find matches
			if(preg_match_all($pattern, $content, $matches, PREG_SET_ORDER))
			{
				// loop matches
				foreach($matches as $match)
				{
					// variable doesn't already exist
					if(array_search($match[0], $this->templateVariables, true) === false)
					{
						// unique key
						$varKey = md5($match[0]);

						// base variable
						$variable = '';

						// variable within iteration
						if(isset($match[4]) && $match[4] != '')
						{
							// base
							$variable = '${\''. $match[1] .'\'}';

							// add separate chunks
							foreach(explode('.', ltrim($match[2] . str_replace('->', '.', $match[4]), '.')) as $chunk)
							{
								$variable .= "['". $chunk ."']";
							}
						}

						// regular variable
						else
						{
							// base
							$variable = '$this->variables';

							// add separate chunks
							foreach(explode('.', $match[1] . $match[2]) as $chunk)
							{
								$variable .= "['". $chunk ."']";
							}
						}

						// save PHP code
						$PHP = $variable;

						// has modifiers
						if(isset($match[7]) && $match[7] != '')
						{
							// modifier pattern
							$pattern = '/\|([a-z_][a-z0-9_]*)((:(("[^"]*?"|\'[^\']*?\')|\[\$[a-z0-9]+\]|[0-9]+))*)/';

							// has match
							if(preg_match_all($pattern, $match[7], $modifiers))
							{
								// loop modifiers
								foreach($modifiers[1] as $key => $modifier)
								{
									// modifier doesn't exist
									if(!isset($this->modifiers[$modifier])) throw new SpoonTemplateException('The modifier "'. $modifier .'" does not exist.');

									// add call
									else
									{
										// method call
										if(is_array($this->modifiers[$modifier])) $PHP = implode('::', $this->modifiers[$modifier]) .'('. $PHP;

										// function call
										else $PHP = $this->modifiers[$modifier] .'('. $PHP;
									}

									// has arguments
									if($modifiers[2][$key] != '')
									{
										// arguments pattern
										$pattern = '/:(("[^"]*?"|\'[^\']*?\')|\[\$[a-z0-9]+\]|[0-9]+)/';

										// has arguments
										if(preg_match_all($pattern, $modifiers[2][$key], $arguments))
										{
											$PHP .= ', '. implode(', ', $arguments[1]);
										}
									}

									// add close tag
									$PHP .= ')';
								}
							}
						}

						/**
						 * Variables may have other variables used as parameters in modifiers
						 * so loop all currently known variables to replace them.
						 * It does not matter that we do not yet know all variables, we only
						 * need those inside this particular variable, and those will
						 * certainly already be parsed because we parse out variables outwards.
						 */
						// temporary variable which is a list of 'variables to check before parsing'
						$variables = array($variable);

						// loop all known template variables
						foreach($this->templateVariables as $key => $value)
						{
							// replace variables
							$PHP = str_replace('[$'. $key .']', $value['content'], $PHP);

							// debug enabled
							if(SPOON_DEBUG)
							{
								// check if this variable is found
								$match[0] = str_replace('[$'. $key .']', $value['template'], $match[0], $count);

								// add variable name to list of 'variables to check before parsing'
								if($count > 0) $variables = array_merge($variables, $value['variables']);
							}
						}

						// debug enabled: variable not assigned = revert to template code
						if(SPOON_DEBUG)
						{
							// holds checks to see if this variable can be parsed (along with the variables that may be used inside it)
							$exists = array();

							// loop variables
							foreach((array) $variables as $variable)
							{
								// get array containing variable
								$array = preg_replace('/(\[\'[a-z_][a-z0-9_]*\'\])$/', '', $variable);

								// get variable name
								preg_match('/\[\'([a-z_][a-z0-9_]*)\'\]$/', $variable, $variable);
								$variable = $variable[1];

								// container array is index of higher array
								if(preg_match('/\[\'[a-z_][a-z0-9_]*\'\]/', $array)) $exists[] = 'isset('. $array .')';
								$exists[] = 'array_key_exists(\''. $variable .'\', (array) '. $array .')';
							}

							// save info for error fallback
							$this->templateVariables[$varKey]['content'] = '('. implode(' && ', $exists) .' ? '. $PHP .' : \''. str_replace(array('\\','\'', '->'), array('\\\\','\\\'', '.'), $match[0]) .'\')';
							$this->templateVariables[$varKey]['variables'] = $variables;
							$this->templateVariables[$varKey]['template'] = $match[0];
						}

						// fast mode, without error recovery
						else $this->templateVariables[$varKey]['content'] = $PHP;
					}

					// replace in content
					$content = str_replace($match[0], '[$'. $varKey .']', $content);
				}
			}

			// break the loop, no matches were found
			else break;
		}

		return $content;
	}


	/**
	 * Prepare iterations (recursively).
	 *
	 * @return	string				The updated content, containing reworked (unique) iteration tags.
	 * @param	string $content		The content that may contain the iteration tags.
	 * @param	string $prefix		Prefix to be used for the unique iteration tag.
	 */
	private function prepareIterations($content, $prefix = '')
	{
		// we want to keep parsing iterations until none can be found
		while(1)
		{
			// fetch iterations - only the last iteration is matched if same iteration exists more than once
			$pattern = '/(\{iteration:([a-z0-9_]*((\.[a-z0-9_]*)*(-\>[a-z0-9_]*(\.[a-z0-9_]*)*)?))\})(?!.*?\{iteration:\\2\})(.*?)(\{\/iteration:\\2\})/is';

			// replace iteration names to ensure that they're unique
			$content = preg_replace_callback($pattern, array($this, 'prepareIterationsCallback'), $content, -1, $count);

			// break the loop, no matches were found
			if(!$count) break;
		}

		return $content;
	}


	/**
	 * Prepare iterations: callback
	 *
	 * @return	String				The updated iteration, containing a reworked (unique) iteration tag.
	 * @param	String $match		The regex-match for an iteration
	 */
	private function prepareIterationsCallback($match)
	{
		// increment iterations counter
		$this->iterationsCounter++;

		// return the modified iteration name
		return '{iteration_'. $this->iterationsCounter .':'. $match[2] .'}'. $match[7] .'{/iteration_'. $this->iterationsCounter .':'. $match[2] .'}';
	}


	/**
	 * Set the cache directory.
	 *
	 * @return	void
	 * @param	string $path	The location of the cache directory to store cached template blocks.
	 */
	public function setCacheDirectory($path)
	{
		$this->cacheDirectory = (string) $path;
	}


	/**
	 * Set the compile directory.
	 *
	 * @return	void
	 * @param	string $path	The location of the compile directory to store compiled templates in.
	 */
	public function setCompileDirectory($path)
	{
		$this->compileDirectory = (string) $path;
	}


	/**
	 * If enabled, recompiles a template even if it has already been compiled.
	 *
	 * @return	void
	 * @param	bool[optional] $on	Should this template be recompiled every time it's loaded.
	 */
	public function setForceCompile($on = true)
	{
		$this->forceCompile = (bool) $on;
	}


	/**
	 * Sets the forms.
	 *
	 * @return	void
	 * @param	array $forms	An array of forms that need to be included in this template.
	 */
	public function setForms(array $forms)
	{
		$this->forms = $forms;
	}


	/**
	 * Strips php code from the content.
	 *
	 * @return	string				The updated content, no longer containing php code.
	 * @param	string $content		The content that may contain php code.
	 */
	private function stripCode($content)
	{
		return $content = preg_replace('/\<\?(php)?(.*)\?\>/siU', '', $content);
	}


	/**
	 * Strip comments from the output.
	 *
	 * @return	string				The updated content, no longer containing template comments.
	 * @param	string $content		The content that may contain template comments.
	 */
	private function stripComments($content)
	{
		// we want to keep stripping comments until none can be found
		do
		{
			// strip comments from output
			$content = preg_replace('/\{\*(?!.*?\{\*).*?\*\}/s', '', $content, -1, $count);
		}
		while($count > 0);

		return $content;
	}
}

?>
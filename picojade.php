<?php
class PicoJade {
	protected $selfclosing = array(
		'area', 'base', 'br', 
		'col', 'command', 'embed', 
		'hr', 'img', 'input',
		'keygen', 'link', 'meta',
		'param', 'source', 'track', 
		'wbr'
	);

	protected $patterns = array(
		'html' => '~^([\w\d_\.\#\-]+)(.*)$~',
		'comment' => '~^(//\-?)\s*(.*)$~',
		'text' => '~^(\|)?(.*)$~',
	);

	protected function createToken($line = null){
		return (object) array(
			'open' => null,
			'close' => null,
			'line' => $line,
			'else' => false,
			'textBlock' => false,
			'isBlock' => false
		);
	}

	protected $doctypes = array(
		'5'             => '<!DOCTYPE html>',
		'xml'           => '<?xml version="1.0" encoding="utf-8" ?>',
		'default'       => '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">',
		'transitional'  => '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">',
		'strict'        => '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">',
		'frameset'      => '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Frameset//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-frameset.dtd">',
		'1.1'           => '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">',
		'basic'         => '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML Basic 1.1//EN" "http://www.w3.org/TR/xhtml-basic/xhtml-basic11.dtd">',
		'mobile'        => '<!DOCTYPE html PUBLIC "-//WAPFORUM//DTD XHTML Mobile 1.2//EN" "http://www.openmobilealliance.org/tech/DTD/xhtml-mobile12.dtd">'
	);

  public function compile($input, $showIndent = false)
  {
    $lines = explode("\n", str_replace("\r", '', rtrim($input, " \t\n") . "\n"));

    $output = $textBlock = $phpCode = null;
    $closing = array();
    foreach ($lines as $n => $line){
    	if($n === 0){
    		if(0 === strpos($line,'!!!')){
    			$doctype = trim(str_replace('!!!','',$line));
    			$output = isset($this->doctypes[$doctype]) ? $this->doctypes[$doctype] : $this->doctypes['5'];
    			continue;
    		}
    	}

      $token = $this->createToken();
      $nextLine = isset($lines[$n + 1]) ? $lines[$n + 1] : '';
      $indent = mb_strlen($line) - mb_strlen(ltrim($line));
      $nextIndent = mb_strlen($nextLine) - mb_strlen(ltrim($nextLine));
      $token->isBlock = ($nextIndent > $indent);
      $token->line = trim($line, "\t\n ");
      $indentStr = ($showIndent && !$textBlock) ? str_repeat(' ', $indent) : '';
      if (trim($line) == '' && !($n === count($lines) - 1 || mb_strpos($nextLine, '<?php') === 0))
        $indentStr = !$indent = PHP_INT_MAX;
      elseif ($textBlock !== null && $textBlock < $indent)
        $token->open = htmlspecialchars(ltrim($line));
      else{
        $token = $this->parseLine($token);
        $textBlock = null;
      }
      foreach (array_reverse($closing, true) as $i => $code){
        if ($i >= $indent){
          if (!$token->else || $i != $indent)
            $output .= $code;
          unset($closing[$i]);
        }
      }
      if ($n !== 0) $output .= "\n";
      if (mb_strpos($line, '<?php') === 0) $phpCode = true;
      if ($phpCode){
        $output .= "$line";
        if (mb_strpos($line, '?>') === 0) $phpCode = false;
        continue;
      }
      $output .= $indentStr . $token->open;
      $closing[$indent] = $token->close;
      if ($token->textBlock) $textBlock = $indent;
    }
    return rtrim($output, " \t\n") . "\n";
  }
  
	protected function parseLine($token){
		if (is_string($token))
			$token = $this->createToken($token);

		foreach ($this->patterns as $name => $pattern){
			if (preg_match($pattern, $token->line, $match)){
				$token->match = $match;
				
				if ($name == 'text')
					$token->open = $match[2];
				elseif ($name == 'comment'){
					$token->open = '<?php /* ' . $match[2];
					$token->close = ' */ ?>';
					$token->textBlock = true;
				}
				else
					$token = call_user_func(array($this, "parse" . ucfirst($name)), $token);
				break;
			}
		}

		return $token;
	}

	protected function parseHtml($token)
	{
		$m = array_fill(0, 5, null);
		preg_match('~^([\w\d\-_]*[\w\d])? ([\.\#][\w\d\-_\.\#]*[\w\d])?
		  (\( (?:(?>[^()]+) | (?3))* \))? (/)? (\.)? ((\-|=|\!=?)|:)? \s* (.*) ~x', $token->line, $m);

		$token->open = empty($m[1]) ? '<div' : "<$m[1]";
		//(in_array($m[1],$this->selfclosing) ? '/>' :
		$token->close = empty($m[1]) ? '</div>' : (in_array($m[1],$this->selfclosing) ? '/>' : "</$m[1]>");

		if (!empty($m[2])){
			$id = preg_filter('~.*(\#([^\.]*)).*~', '\2', $m[2]);
			$token->open .= $id ? " id=\"$id\"" : '';
			$classes = preg_replace('~\#[^\.]*~', '', $m[2]);
			$classes = str_replace('.', ' ', $classes);
			$token->open .= $classes ? ' class="' . trim($classes) . '"' : '';
		}

		if (!empty($m[3]))
			$token->open .= ' ' . implode(' ',explode(',',trim($m[3], '() ')));

		$token->close = empty($m[4]) ? $token->close : '';

		if(in_array($m[1],$this->selfclosing))
			$token->open .= "";
		else		
			$token->open .= empty($m[4]) ? '>' : " />";
		
		$token->textBlock = !empty($m[5]);

		if (!empty($m[6])){
			$nextToken = $this->createToken($m[7] . $m[8]);
			$nextToken->isBlock = $token->isBlock;
			$nextToken = $this->parseLine($nextToken);
			$token->open .= $nextToken->open;
			$token->close = $nextToken->close . $token->close;
		}else{
			$token->open .= $m[8];
		}

		return $token;
	}
}
?>

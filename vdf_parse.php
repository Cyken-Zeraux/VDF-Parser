<?php

function objectToArray ($object) {
    if(!is_object($object) && !is_array($object))
        return $object;

    return array_map('objectToArray', (array) $object);
}


function vdf_parse($string, $array = true, $linebreak = "\n")
{
	$lines = preg_split('/\r\n|\r|\n/', $string, null, PREG_SPLIT_DELIM_CAPTURE);

	$stack = array(new stdClass());
	$expect_bracket = False;

$regex = <<<'DOC'
/^("(?P<qkey>(?:\\.|[^\\"])+)"|(?P<key>[a-z0-9\-\_]+))([ \t]*("(?P<qval>(?:\\.|[^\\"])*)(?P<vq_end>")?|(?P<val>[a-z0-9\-\_]+)))?/i
DOC;
	$linenum = 0;
	$expect_line = 0;
	$rawline = '';

	for($iter = 0; $iter < count($lines); $iter++)
	{
		$rawline = (string)$lines[$iter];
		$line = ltrim($rawline);
		# skip empty and comment lines
		if ( $line == '' || $line[0] == '/' )
		{
			continue;
		}

		# one level deeper
		if ($line[0] == "{")
		{
			$expect_bracket = false;
			continue;
		}

		if ($expect_bracket)
		{
			throw new \Exception("vdf.parse: expected openning bracket on line:$expect_line");
		}

		# one level back
		if ($line[0] == "}")
		{
			if(count($stack) > 1)
			{
				array_pop($stack);
				continue;
			}
			throw new \Exception("vdf.parse: one too many closing parenthasis");
		}

		while(True)
		{
			$match = array();
			$matchbool = preg_match($regex, $line, $match);

			if($matchbool === false)
			{
				throw new \Exception('vdf.parse: invalid syntax');
			}

			if(@$match['qkey'] == null)
			{
				@$key = $match['key'];
			} else {
				@$key = $match['qkey'];
			}

			if(@$match['qval'] === null)
			{
				@$val = $match['val'];
			} else {
				@$val = $match['qval'];
			}

			# we have a key with value in parenthesis, so we make a new dict obj (level deeper)
			if ($val === NULL)
			{
				end($stack);
				$back = key($stack);
				reset($stack);

				//Combines values of same key, from top-down.
				if(!isset($stack[$back]->{$key}))
				{
					$stack[$back]->{$key} = new stdClass();

					end($stack);
					$back = key($stack);
					reset($stack);
				}

				$stack[] = $stack[$back]->{$key};
				$expect_line = $iter;
				$expect_bracket = true;
			}
			# we've matched a simple keyvalue pair, map it to the last dict obj in the stack
			else {

				# if the value is line consume one more line and try to match again,
				# until we get the KeyValue pair

				if(@$match['vq_end'] === null && @$match['qval'] !== null)
				{
					$iter++;
					$line .= $linebreak . $lines[$iter];
					//$file->fseek(-strlen($rawline), SEEK_CUR);
					continue;
				}

				end($stack);
				$back = key($stack);
				reset($stack);
				$stack[$back]->{$key} = $val;
			}

			# exit the loop
			break;
		}
	}

	if(count($stack) != 1)
	{
		throw new \Exception("vdf.parse: unclosed parenthasis or quotes");
	}

	if($array == true)
	{
		return objectToArray(array_pop($stack));
	}

	return array_pop($stack);

}

?>
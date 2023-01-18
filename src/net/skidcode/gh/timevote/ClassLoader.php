<?php
namespace net\skidcode\gh\timevote;

use IClassLoader;

class ClassLoader implements IClassLoader
{
	public function loadAll($pharPath)
	{
		$src = $pharPath."/src/";
		include($src."net/skidcode/gh/timevote/ConfigConstants.php");
		include($src."net/skidcode/gh/timevote/MessageBuilder.php");
		include($src."net/skidcode/gh/timevote/VoteHandler.php");
	}
}


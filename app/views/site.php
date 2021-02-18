<!doctype html>

<html lang="en">
<head>
<meta charset="utf-8">
<meta name='viewport' content='width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0'>

{block "style"}
	{"fonts"|style}
	{"main"|style}
{/block}

{block "css"}{/block}

{block "script"}
	{"main"|script}
{/block}
{block "code"}{/block}

<title>ForceField Site | This Text Should Be Replaced During Output Pipeline</title>
</head>
<body>

{block "header"}
<h1>Put Header Here</h1>
{/block}

{block "content"}
{/block}

{block "footer"}
<div id="footer">
	<footer>
	© <?php echo date('Y') . ' ' . ForceField\Network\Url::current()->domain(2); ?>. All Rights Reserved.
	</footer>
</div>
{/block}



</body>
</html>
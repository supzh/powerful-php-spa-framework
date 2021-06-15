# powerful-php-spa-frameowork

how to use:

route
```php

use Application\Application;
use Application\Route;
use Application\Request;

Route::request('/to', function(){ //this function use to get the frontend async page request
	$tplstr = Application::asyncPage($_GET['href'], $_GET);
	
	echo json_encode(['code' => 0, 'content' => $tplstr]);
});

Route::request('/', function(Request $request){
	include __DIR__ . '/../../tpl/index.html';
});

Route::request('/index', function(Request $request){
	include __DIR__ . '/../../tpl/index.html';
});

Route::request('/sec', function(Request $request){
	include __DIR__ . '/../../tpl/sec.html';
});
```

html page
```html
<html lang="zh-CN">
<head>
<title>PHP SPA</title>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0,user-scalable=no" >
<script src="/js/jquery.js"></script>

</head>
<body>

<h1>Home</h1>
<ul>
	<li><a href="/index.html" data-href="/index.html" class="to">home</a></li> <!-- notice all link should add a data-href attribute and a "to" class -->
	<li><a href="/sec.html" data-href="/sec.html"  class="to">second page</a></li>
</ul>

<script src="/js/common.js"></script>
</body>
</html>
```

implements
```

function setHref()
{
	var refresh = function(dataset, isBack = false){
		
		var d = {}
		for(var i in dataset){
			d[i] = dataset[i]
		}
		if(!isBack){
			history.pushState(d,null,d.href);
		}
		
		$.get('/to', d, function(msg){
			
			if(msg.code==0){
				var pageContent = document.querySelector('body');
				pageContent.innerHTML = msg.content;
				window.scrollTo(0,0)
				setHref()
			} else {
				alert('Page load failed, please refresh the page.');
			}
		}, 'JSON');
	}
	
	window.onpopstate = function(e){
		refresh(e.state, true)
	}
	
	var tos = document.getElementsByClassName('to');
	for(var i=0;i<tos.length;i++){
		tos.item(i).onclick = function(e){

			for(var u in e.path) {
				if(e.path[u].className){
				if(e.path[u].className.match('to') !=null) {
					target = e.path[u]
					break;
				}
				}
			}
			refresh(target.dataset)
			return false;
		}
	}
}

setHref()	

```

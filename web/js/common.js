
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

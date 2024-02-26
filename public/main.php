<?php

function setDocumentTitle()
{
	$script = <<<'HEREA'
	<script>
		function sendAnyTrackRequest(event) {
			var pageTitle = document.title;
			var xhr = new XMLHttpRequest();
			xhr.open('GET', 'track.php?event=' + event + '&title=' + encodeURIComponent(pageTitle), true);
			xhr.onload = function() { if (xhr.status == 200) { var result = JSON.parse(xhr.responseText); console.log(result); var p = document.createElement('a'); p.innerText = result.URL; p.target="_black"; p.href=result.URL; p.style.display = 'block'; document.getElementById('result').append(p); } else { console.error('Error: ' + xhr.status); } }
			xhr.send();
		}
		window.addEventListener('load', function() {
			sendAnyTrackRequest('PageView')
		})
		document.querySelectorAll('input[type=button]').forEach(function (el) {
			el.addEventListener('click', function() {
				sendAnyTrackRequest('ButtonClick')
			})
		})
		document.querySelectorAll('form').forEach(function (el) {
			el.addEventListener('submit', function() {
				sendAnyTrackRequest('FormSubmit')
			})
		})
	</script>
	HEREA;
	echo $script;
}

setDocumentTitle();
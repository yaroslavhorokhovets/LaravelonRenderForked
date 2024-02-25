<?php

function setDocumentTitle()
{
	$script = <<<'HEREA'
	<script>
		function sendAnyTrackRequest(event) {
			var pageTitle = document.title;
			var xhr = new XMLHttpRequest();
			xhr.open('GET', 'track.php?aid=iMaKRTVWjJ5T&event=' + event + '&title=' + encodeURIComponent(pageTitle), true);
			xhr.onload = function() { if (xhr.status == 200) { console.log(xhr.responseText); var p = document.createElement('p'); p.innerText = xhr.responseText; p.style.textWrap = 'wrap'; document.getElementById('result').append(p); } else { console.error('Error: ' + xhr.status); } }
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
(function(){
	class Panic
	{
      static init(ajax) {
      	var panic = document.getElementById('oops-panic');
      	var styles = [];

      	for (var i = 0; i < document.styleSheets.length; i++) {
            var style = document.styleSheets[i];
            if (!style.ownerNode.classList.contains('oops-debug')) {
            	style.oldDisabled = style.disabled;
            	style.disabled = true;
            	styles.push(style);
            }
      	}

      	document.getElementById('oops-panic-toggle').addEventListener('oops-toggle', function() {
            var collapsed = this.classList.contains('oops-collapsed');
            for (var i = 0; i < styles.length; i++) {
            	styles[i].disabled = collapsed ? styles[i].oldDisabled : true;
            }
      	});

      	if (!ajax) {
            document.body.appendChild(panic);
            var id = location.href + document.getElementById('oops-panic-error').textContent;
            Oops.Toggle.persist(panic, sessionStorage.getItem('oops-toggles-panickey') == id);
            sessionStorage.setItem('oops-toggles-panickey', id);
      	}

      	if (inited) {
            return;
      	}
      	inited = true;

      	// enables toggling via ESC
      	document.addEventListener('keyup', function (e) {
            if (e.keyCode == 27 && !e.shiftKey && !e.altKey && !e.ctrlKey && !e.metaKey) { // ESC
            	Oops.Toggle.toggle(document.getElementById('oops-panic-toggle'));
            }
      	});
      }


      static loadAjax(content, dumps) {
      	var ajaxPanic = document.getElementById('oops-panic');
      	if (ajaxPanic) {
            ajaxPanic.parentNode.removeChild(ajaxPanic);
      	}
      	document.body.insertAdjacentHTML('beforeend', content);
      	ajaxPanic = document.getElementById('oops-panic');
      	Oops.Dumper.init(dumps, ajaxPanic);
      	Panic.init(true);
      	window.scrollTo(0, 0);
      }
	}

	var inited;


	Oops = window.Oops || {};
	Oops.Panic = Panic;
})();

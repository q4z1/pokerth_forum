/**
 * Auf- und Zuklappen des Country-Dropdowns im UCP-Profil.
 *
 * Die Länderliste steht bereits im HTML — dieses Skript wählt nur aus und
 * schreibt den Wert in das hidden field. Abgeschickt wird ganz normal mit dem
 * phpBB-Formular; es gibt keine eigenen Requests mehr.
 */
(function () {
	'use strict';

	var dropdown = document.getElementById('pth_country_dropdown');

	if (!dropdown) {
		return;
	}

	var button = dropdown.querySelector('.pth-dropdown__selected');
	var list = dropdown.querySelector('.pth-dropdown__options');
	var field = dropdown.querySelector('#pth_country');

	function parseRgb(value) {
		var m = /^rgba?\(([^)]+)\)$/.exec(value);

		if (!m) {
			return null;
		}

		var parts = m[1].split(',').map(function (s) { return parseFloat(s); });

		return { r: parts[0], g: parts[1], b: parts[2], a: parts.length > 3 ? parts[3] : 1 };
	}

	/**
	 * Sorgt dafür, dass die Liste deckend ist.
	 *
	 * Die Klasse "inputbox" liefert in diesem Style rgba(255,255,255,0.1). Beim
	 * geschlossenen Button fällt das nicht auf, weil er flach auf dem Panel liegt —
	 * die aufgeklappte Liste schwebt aber über anderem Inhalt und wird dadurch
	 * durchsichtig. Statt Theme-Farben fest zu verdrahten (19 Farbpresets, jeweils
	 * hell und dunkel) wird die erste deckende Hintergrundfarbe oberhalb gesucht
	 * und die halbtransparente Farbe darauf gerechnet. Ergebnis ist genau der
	 * Farbton, den auch der geschlossene Button zeigt.
	 */
	function makeOpaque() {
		var own = parseRgb(getComputedStyle(list).backgroundColor);

		if (!own || own.a === 1) {
			return;
		}

		var base = null;

		for (var el = list.parentElement; el && !base; el = el.parentElement) {
			var c = parseRgb(getComputedStyle(el).backgroundColor);

			if (c && c.a === 1) {
				base = c;
			}
		}

		if (!base) {
			base = { r: 255, g: 255, b: 255 };
		}

		var mix = function (channel) {
			return Math.round(own[channel] * own.a + base[channel] * (1 - own.a));
		};

		list.style.backgroundColor = 'rgb(' + mix('r') + ', ' + mix('g') + ', ' + mix('b') + ')';
	}

	function close() {
		list.hidden = true;
		button.setAttribute('aria-expanded', 'false');
	}

	function open() {
		makeOpaque();
		list.hidden = false;
		button.setAttribute('aria-expanded', 'true');
	}

	button.addEventListener('click', function () {
		if (list.hidden) {
			open();
		} else {
			close();
		}
	});

	list.addEventListener('click', function (event) {
		var option = event.target.closest('.pth-dropdown__option');

		if (!option) {
			return;
		}

		var flag = option.getAttribute('data-flag');
		var title = option.getAttribute('data-title');

		field.value = option.getAttribute('data-value');
		button.innerHTML = '';

		if (flag) {
			var img = document.createElement('img');
			img.src = flag;
			img.alt = '';
			img.width = 20;
			img.height = 14;
			button.appendChild(img);
		}

		var label = document.createElement('span');
		label.textContent = title;
		button.appendChild(label);

		Array.prototype.forEach.call(list.children, function (item) {
			item.removeAttribute('aria-selected');
		});
		option.setAttribute('aria-selected', 'true');

		close();
	});

	document.addEventListener('click', function (event) {
		if (!dropdown.contains(event.target)) {
			close();
		}
	});

	document.addEventListener('keydown', function (event) {
		if (event.key === 'Escape' && !list.hidden) {
			close();
			button.focus();
		}
	});
})();

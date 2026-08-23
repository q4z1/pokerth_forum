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

	function close() {
		list.hidden = true;
		button.setAttribute('aria-expanded', 'false');
	}

	function open() {
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

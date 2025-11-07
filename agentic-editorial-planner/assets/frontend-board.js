(function () {
	document.addEventListener('DOMContentLoaded', function () {
		document.querySelectorAll('.aep-task-card__toggle').forEach(function (toggle) {
			toggle.addEventListener('click', function () {
				const target = toggle.closest('.aep-task-card').querySelector('.aep-task-card__content');
				if (!target) {
					return;
				}
				const expanded = toggle.getAttribute('aria-expanded') === 'true';
				toggle.setAttribute('aria-expanded', !expanded);
				target.hidden = expanded;
			});
		});
	});
})();


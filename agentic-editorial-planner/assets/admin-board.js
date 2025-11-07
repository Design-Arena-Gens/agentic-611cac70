/* global wp, aepPlanner */
(function () {
	const { apiFetch, domReady } = wp || {};

	function createElement(tag, className, text) {
		const el = document.createElement(tag);
		if (className) {
			el.className = className;
		}
		if (text) {
			el.textContent = text;
		}
		return el;
	}

	function formatDate(value) {
		if (!value) {
			return '';
		}
		const date = new Date(value);
		if (Number.isNaN(date.getTime())) {
			return value;
		}
		return date.toISOString().slice(0, 10);
	}

	function renderBoard(root, state) {
		root.innerHTML = '';
		const container = createElement('div', 'aep-board');

		if (!state.loading && state.columns.length === 0) {
			const empty = createElement('p', 'aep-empty', 'Create at least one status to see tasks.');
			root.appendChild(empty);
			return;
		}

		const columns = state.loading ? Array.from({ length: 3 }) : state.columns;

		columns.forEach((column, index) => {
			const columnEl = createElement('section', 'aep-board__column');
			const header = createElement('header', 'aep-board__header');
			if (state.loading) {
				header.appendChild(createElement('span', 'aep-skeleton'));
				columnEl.appendChild(header);
				columnEl.appendChild(createElement('div', 'aep-board__list'));
				container.appendChild(columnEl);
				return;
			}

			header.appendChild(createElement('span', '', column.name));
			if (column.color) {
				const badge = createElement('span', 'aep-badge');
				badge.style.background = column.color;
				badge.textContent = column.slug;
				header.appendChild(badge);
			}
			columnEl.appendChild(header);

			const list = createElement('div', 'aep-board__list');

			const tasks = state.tasks.filter((task) => task.status === column.id);
			if (tasks.length === 0) {
				list.appendChild(createElement('p', 'aep-empty', 'No tasks in this column.'));
			} else {
				tasks.forEach((task) => {
					list.appendChild(renderCard(task, state));
				});
			}

			columnEl.appendChild(list);
			container.appendChild(columnEl);
		});

		root.appendChild(container);
	}

	function renderCard(task, state) {
		const card = createElement('article', 'aep-board__card');
		const title = createElement('h3');
		title.textContent = task.title;
		card.appendChild(title);

		if (task.excerpt) {
			const excerpt = createElement('p');
			excerpt.textContent = task.excerpt;
			card.appendChild(excerpt);
		}

		const meta = createElement('div', 'aep-board__meta');
		meta.appendChild(createElement('span', '', `Due: ${task.dueDate || '—'}`));
		meta.appendChild(createElement('span', '', `Owner: ${task.ownerName || 'Unassigned'}`));
		if (task.priorityName) {
			meta.appendChild(createElement('span', 'aep-badge', task.priorityName));
		}
		card.appendChild(meta);

		const form = createElement('form');
		form.className = 'aep-board__form';
		form.addEventListener('submit', (event) => {
			event.preventDefault();
			const data = new window.FormData(form);
			updateTask(task.id, Object.fromEntries(data.entries()), state);
		});

		const statusSelect = createSelect('status', state.columns, task.status);
		form.appendChild(statusSelect.wrapper);

		const prioritySelect = createSelectOptions('priority', state.priorities, task.priority);
		form.appendChild(prioritySelect.wrapper);

		const ownerSelect = createSelectOptions('owner', state.owners, task.owner);
		form.appendChild(ownerSelect.wrapper);

		const dueLabel = createElement('label');
		dueLabel.textContent = 'Due Date';
		const dueInput = createElement('input');
		dueInput.type = 'date';
		dueInput.name = 'dueDate';
		dueInput.value = formatDate(task.dueDate);
		dueLabel.appendChild(dueInput);
		form.appendChild(dueLabel);

		const linkLabel = createElement('label');
		linkLabel.textContent = 'Brief URL';
		const linkInput = createElement('input');
		linkInput.type = 'url';
		linkInput.name = 'briefLink';
		linkInput.placeholder = 'https://...';
		linkInput.value = task.briefLink || '';
		linkLabel.appendChild(linkInput);
		form.appendChild(linkLabel);

		const actions = createElement('div', 'aep-board__actions');
		const saveBtn = createElement('button');
		saveBtn.type = 'submit';
		saveBtn.className = 'button button-primary';
		saveBtn.textContent = 'Save';
		actions.appendChild(saveBtn);

		const editLink = createElement('a');
		editLink.href = task.permalink;
		editLink.className = 'button';
		editLink.textContent = 'Edit Task';
		actions.appendChild(editLink);

		form.appendChild(actions);

		card.appendChild(form);

		return card;
	}

	function createSelect(name, options, selected) {
		const wrapper = createElement('label');
		wrapper.textContent = 'Status';
		const select = createElement('select');
		select.name = name;
		options.forEach((option) => {
			const opt = createElement('option');
			opt.value = option.id;
			opt.textContent = option.name;
			if (Number(option.id) === Number(selected)) {
				opt.selected = true;
			}
			select.appendChild(opt);
		});
		wrapper.appendChild(select);
		return { wrapper, select };
	}

	function createSelectOptions(name, options, selected) {
		const wrapper = createElement('label');
		wrapper.textContent = name === 'priority' ? 'Priority' : 'Owner';
		const select = createElement('select');
		select.name = name;

		const placeholder = createElement('option', '', name === 'priority' ? 'No priority' : 'Unassigned');
		placeholder.value = '';
		select.appendChild(placeholder);

		options.forEach((option) => {
			const opt = createElement('option');
			opt.value = option.slug || option.id;
			opt.textContent = option.name;
			if (String(opt.value) === String(selected)) {
				opt.selected = true;
			}
			select.appendChild(opt);
		});

		wrapper.appendChild(select);
		return { wrapper, select };
	}

	function fetchTasks(root, state) {
		state.loading = true;
		renderBoard(root, state);

		apiFetch({
			url: aepPlanner.endpoint,
		})
			.then((response) => {
				Object.assign(state, response, { loading: false });
				renderBoard(root, state);
			})
			.catch((error) => {
				console.error(error); // eslint-disable-line no-console
				state.loading = false;
				root.innerHTML = '<p class="aep-empty">Unable to load tasks. Please refresh.</p>';
			});
	}

	function updateTask(id, payload, state) {
		const root = document.getElementById('aep-task-board-root');
		if (!root) {
			return;
		}

		const postData = {
			id,
			status: payload.status ? Number(payload.status) : null,
			priority: payload.priority,
			owner: payload.owner ? Number(payload.owner) : null,
			dueDate: payload.dueDate || null,
			briefLink: payload.briefLink || '',
		};

		apiFetch({
			url: aepPlanner.endpoint,
			method: 'POST',
			headers: {
				'X-WP-Nonce': aepPlanner.nonce,
				'Content-Type': 'application/json',
			},
			body: JSON.stringify(postData),
		})
			.then((response) => {
				Object.assign(state, response, { loading: false });
				renderBoard(root, state);
			})
			.catch((error) => {
				console.error(error); // eslint-disable-line no-console
				alert('Failed to update task.'); // eslint-disable-line no-alert
			});
	}

	domReady(() => {
		if (!apiFetch || !domReady) {
			return;
		}

		const root = document.getElementById('aep-task-board-root');
		if (!root) {
			return;
		}

		const state = {
			columns: [],
			tasks: [],
			priorities: [],
			owners: [],
			loading: true,
		};

		fetchTasks(root, state);
	});
})();

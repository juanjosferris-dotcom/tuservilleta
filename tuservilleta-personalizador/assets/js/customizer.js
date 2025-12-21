(function () {
	const data = window.TuservilletaData || {};
	if (!data || !data.catalog) return;

	const steps = [
		{ key: 'size', label: 'Tamaño' },
		{ key: 'type', label: 'Calidad' },
		{ key: 'color', label: 'Color' },
		{ key: 'quantity', label: 'Cantidad' },
		{ key: 'printing', label: 'Impresión' },
		{ key: 'image', label: 'Imagen' },
	];

	const catalog = data.catalog || [];
	const settings = data.settings || {};
	const priceIndex = new Map();
	const sanitizeText = (value) => String(value ?? '').replace(/[<>]/g, '');
	const normalize = (value) => String(value ?? '').trim();

	const state = {
		currentStep: 0,
		selections: {
			size: null,
			type: null,
			color: null,
			quantity: null,
			printing: null,
			image: null,
		},
		price: null,
	};

	const stepsContainer = document.getElementById('tuservilleta-steps');
	const progressContainer = document.getElementById('tuservilleta-progress');
	const selectionList = document.getElementById('tuservilleta-selection');
	const priceNode = document.getElementById('tuservilleta-price');
	const imageInput = document.getElementById('tuservilleta-image');
	const sendBtn = document.getElementById('tuservilleta-send');
	const payBtn = document.getElementById('tuservilleta-pay');
	const messageNode = document.getElementById('tuservilleta-message');
	const paypalForm = document.getElementById('tuservilleta-paypal-form');
	const amountField = document.getElementById('tuservilleta-amount');
	const itemNameField = document.getElementById('tuservilleta-item-name');

	function createFormatter() {
		try {
			const currency = settings.currency || 'EUR';
			return new Intl.NumberFormat('es-ES', { style: 'currency', currency });
		} catch (e) {
			return new Intl.NumberFormat('es-ES', { style: 'currency', currency: 'EUR' });
		}
	}

	const formatter = createFormatter();

	function setAccent() {
		if (settings.accent_color) {
			document.documentElement.style.setProperty('--tuservilleta-accent', settings.accent_color);
		}
	}

	function renderEmptyCatalog() {
		if (!stepsContainer) return;
		stepsContainer.innerHTML = `<div class="tuservilleta-card">${data.strings?.emptyCatalog || 'Sube un catálogo para comenzar.'}</div>`;
		sendBtn?.setAttribute('disabled', 'disabled');
		payBtn?.setAttribute('disabled', 'disabled');
	}

	function filterCatalog(upToIndex) {
		let filtered = catalog.slice();
		steps.slice(0, upToIndex).forEach((step) => {
			const val = state.selections[step.key];
			if (val !== null && val !== undefined && val !== '') {
				filtered = filtered.filter((row) => normalize(row[step.key]) === normalize(val));
			}
		});
		return filtered;
	}

	function optionsForStep(stepIndex) {
		const step = steps[stepIndex];
		const filtered = filterCatalog(stepIndex);
		if (stepIndex === 0) {
			// Mostrar la primera columna tal cual viene (permite duplicados si el CSV los incluye).
			return filtered
				.map((row) => row[step.key])
				.filter((v) => v !== undefined && v !== null && normalize(v) !== '');
		}
		const values = new Set();
		filtered.forEach((row) => {
			if (row[step.key] !== undefined && row[step.key] !== null && normalize(row[step.key]) !== '') {
				values.add(normalize(row[step.key]));
			}
		});
		return Array.from(values);
	}

	function renderProgress() {
		if (!progressContainer) return;
		progressContainer.innerHTML = '';

		steps.forEach((step, index) => {
			const node = document.createElement('div');
			node.className = 'step';
			if (index === state.currentStep) node.classList.add('active');
			if (index < state.currentStep) node.classList.add('completed');

			node.innerHTML = `<div class="badge">${index + 1}</div><div>${step.label}</div>`;
			node.addEventListener('click', () => {
				if (index <= state.currentStep) {
					state.currentStep = index;
					render();
				}
			});
			progressContainer.appendChild(node);
		});
	}

	function renderStep() {
		if (!stepsContainer) return;
		const step = steps[state.currentStep];

		if (step.key === 'image') {
			stepsContainer.innerHTML = `
				<div class="tuservilleta-grid">
					<div class="tuservilleta-card" data-image="upload">
						<div class="title">Subir imagen ahora</div>
						<p class="caption">Adjunta tu logo o diseño para validarlo junto al pedido.</p>
					</div>
					<div class="tuservilleta-card" data-image="later">
						<div class="title">La enviaré más tarde</div>
						<p class="caption">Podrás compartirla por email tras confirmar el pedido.</p>
					</div>
				</div>
			`;
			stepsContainer.querySelectorAll('.tuservilleta-card').forEach((card) => {
				card.addEventListener('click', () => {
					const mode = card.getAttribute('data-image');
					if (mode === 'upload') {
						state.selections.image = null;
						imageInput?.click();
					} else {
						state.selections.image = 'Enviaré más tarde';
					}
					updateSummary();
					state.currentStep = steps.length - 1;
					render();
				});
			});
			return;
		}

		const options = optionsForStep(state.currentStep);
		stepsContainer.innerHTML = `<div class="tuservilleta-grid"></div>`;
		const grid = stepsContainer.querySelector('.tuservilleta-grid');

		if (!options.length) {
			grid.innerHTML = `<div class="tuservilleta-card">${data.strings?.priceNotFound || 'No hay opciones disponibles.'}</div>`;
			return;
		}

		const stepImages = data.stepImages || {};
		options.forEach((option) => {
			const card = document.createElement('div');
			card.className = 'tuservilleta-card';
			if (String(state.selections[step.key]) === String(option)) card.classList.add('selected');
			const img = stepImages[step.key];
			const label = sanitizeText(String(option));
			card.innerHTML = `
				${img ? `<div class="thumb" style="background-image:url('${img}')"></div>` : ''}
				<div class="content">
					<div class="title">${label}</div>
					<p class="caption">Elegir ${step.label.toLowerCase()}</p>
				</div>`;
			card.addEventListener('click', () => {
				state.selections[step.key] = option;
				if (state.currentStep < steps.length - 1) {
					state.currentStep += 1;
				}
				render();
				updateSummary();
			});
			grid.appendChild(card);
		});
	}

	function buildPriceIndex() {
		catalog.forEach((row) => {
			const key = [
				normalize(row.size),
				normalize(row.type),
				normalize(row.color),
				normalize(row.quantity),
				normalize(row.printing),
			]
				.join('|');
			priceIndex.set(key, parseFloat(row.price));
		});
	}

	function computePrice() {
		const { size, type, color, quantity, printing } = state.selections;
		if (!size || !type || !color || !quantity || !printing) {
			state.price = null;
			return;
		}
		const key = [size, type, color, quantity, printing].map((v) => normalize(v)).join('|');
		state.price = priceIndex.get(key) ?? null;
	}

	function updateSummary() {
		if (!selectionList || !priceNode) return;
		selectionList.innerHTML = '';

		steps.forEach((step) => {
			if (step.key === 'image') return;
			const value = state.selections[step.key] || '—';
			const li = document.createElement('li');
			const strong = document.createElement('strong');
			strong.textContent = `${step.label}:`;
			li.appendChild(strong);
			li.appendChild(document.createTextNode(` ${value}`));
			selectionList.appendChild(li);
		});

		if (state.selections.image) {
			const li = document.createElement('li');
			const strong = document.createElement('strong');
			strong.textContent = 'Imagen:';
			li.appendChild(strong);
			li.appendChild(document.createTextNode(` ${state.selections.image}`));
			selectionList.appendChild(li);
		}

		computePrice();
		if (state.price !== null && !isNaN(state.price)) {
			priceNode.textContent = formatter.format(state.price);
			payBtn?.removeAttribute('disabled');
		} else {
			priceNode.textContent = data.strings?.priceNotFound || 'No hay coincidencias';
			payBtn?.setAttribute('disabled', 'disabled');
		}

		updatePaypalFields();
	}

	function updatePaypalFields() {
		if (!paypalForm) return;
		if (!settings.paypal_business) {
			return;
		}
		const description = steps
			.filter((s) => s.key !== 'image')
			.map((s) => `${s.label}: ${sanitizeText(state.selections[s.key] || '-')}`)
			.join(' | ');
		itemNameField.value = description || 'Personalización';
		if (state.price !== null && !isNaN(state.price)) {
			amountField.value = state.price.toFixed(2);
		}
	}

	function bindImageInput() {
		if (!imageInput) return;
		imageInput.addEventListener('change', () => {
			if (imageInput.files && imageInput.files[0]) {
				state.selections.image = imageInput.files[0].name;
				updateSummary();
			} else {
				state.selections.image = null;
				updateSummary();
			}
		});
	}

	function bindActions() {
		if (sendBtn) {
			sendBtn.addEventListener('click', sendQuote);
		}
		if (payBtn) {
			payBtn.addEventListener('click', (e) => {
				e.preventDefault();
				if (!state.price) {
					messageNode.textContent = data.strings?.selectOptionsFirst || 'Selecciona todas las opciones para calcular el precio.';
					return;
				}
				if (settings.stripe_link) {
					const amount = state.price.toFixed(2);
					const link = settings.stripe_link.replace('{amount}', amount);
					window.open(link, '_blank');
					return;
				}
				if (settings.paypal_business) {
					paypalForm?.submit();
				} else {
					messageNode.textContent = data.strings?.paymentConfigMissing || 'Configura tu cuenta PayPal en el panel de administración.';
				}
			});
		}
	}

	function sendQuote() {
		messageNode.textContent = '';
		const name = document.getElementById('tuservilleta-name')?.value.trim();
		const email = document.getElementById('tuservilleta-email')?.value.trim();
		const phone = document.getElementById('tuservilleta-phone')?.value.trim();
		const notes = document.getElementById('tuservilleta-notes')?.value.trim();
		const imageFile = imageInput?.files?.[0];

		if (!name || !email) {
			messageNode.textContent = data.strings?.quoteError || 'Completa los campos obligatorios.';
			return;
		}

		const selections = { ...state.selections };
		const payload = {
			selections,
			price: state.price ? formatter.format(state.price) : null,
		};

		const form = new FormData();
		form.append('action', 'tuservilleta_send_quote');
		form.append('nonce', data.nonce);
		form.append('name', name);
		form.append('email', email);
		form.append('phone', phone || '');
		form.append('notes', notes || '');
		form.append('payload', JSON.stringify(payload));
		if (imageFile) {
			form.append('image', imageFile);
		}

		messageNode.textContent = 'Enviando...';
		fetch(data.ajaxUrl, {
			method: 'POST',
			body: form,
			credentials: 'same-origin',
		})
			.then((res) => res.json())
			.then((response) => {
				if (response.success) {
					messageNode.textContent = data.strings?.quoteSent || 'Enviado correctamente.';
				} else {
					throw new Error(response.data?.message || 'Error');
				}
			})
			.catch(() => {
				messageNode.textContent = data.strings?.quoteError || 'No se pudo enviar.';
			});
	}

	function loadHubspot() {
		if (!settings.hubspot_portal_id || !settings.hubspot_form_id) return;
		if (document.querySelector('script[src*="js.hsforms.net/forms/v2.js"]')) {
			createHubspotForm();
			return;
		}

		const script = document.createElement('script');
		script.src = 'https://js.hsforms.net/forms/v2.js';
		script.onload = createHubspotForm;
		document.body.appendChild(script);
	}

	function createHubspotForm() {
		if (!window.hbspt || !window.hbspt.forms) return;
		const target = document.getElementById('tuservilleta-hubspot');
		if (!target) return;
		window.hbspt.forms.create({
			portalId: settings.hubspot_portal_id,
			formId: settings.hubspot_form_id,
			target: '#tuservilleta-hubspot',
			onFormReady: (form) => {
				form.style.marginTop = '22px';
				updateHubspotHidden(form);
			},
			onFormSubmit: (form) => {
				updateHubspotHidden(form);
			},
		});
	}

	function updateHubspotHidden(formEl) {
		const form = formEl || document.querySelector('#tuservilleta-hubspot form');
		if (!form) return;
		const ensureField = (name, value) => {
			let input = form.querySelector(`input[name="${name}"]`);
			if (!input) {
				input = document.createElement('input');
				input.type = 'hidden';
				input.name = name;
				form.appendChild(input);
			}
			input.value = value || '';
		};
		Object.keys(state.selections).forEach((key) => {
			ensureField(`tuservilleta_${key}`, state.selections[key]);
		});
		if (state.price) {
			ensureField('tuservilleta_price', state.price);
		}
	}

	function render() {
		renderProgress();
		renderStep();
		updateSummary();
		updateHubspotHidden();
	}

	function init() {
		buildPriceIndex();
		setAccent();
		if (!catalog.length) {
			renderEmptyCatalog();
			return;
		}
		bindActions();
		bindImageInput();
		render();
		loadHubspot();
	}

	init();
})();

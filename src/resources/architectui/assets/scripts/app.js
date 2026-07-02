(() => {
    const DOC_LANG = (document.documentElement.lang || 'it').toLowerCase().split('-')[0];
    const I18N_MESSAGES = {
        it: {
            locale: 'it-IT',
            clear: 'Svuota',
            today: 'Oggi',
            weekdaysShort: ['Lun', 'Mar', 'Mer', 'Gio', 'Ven', 'Sab', 'Dom'],
            noItemsAvailable: 'Nessun elemento disponibile.',
            noOptionsAvailable: 'Nessuna opzione disponibile.',
            selectedCount: (selected, total) => `${selected} di ${total} selezionati`,
            invalidDate: 'Inserisci una data valida nel formato YYYY-MM-DD.',
            invalidFilterValue: 'Valore filtro non valido.',
            fieldsDoNotMatch: 'I campi non coincidono.',
            characterLabel: (count) => (count === 1 ? 'carattere' : 'caratteri'),
            exactLength: (length, unit) => `Deve contenere esattamente ${length} ${unit}.`,
            minLength: (length, unit) => `Deve contenere almeno ${length} ${unit}.`,
            maxLength: (length, unit) => `Deve contenere al massimo ${length} ${unit}.`,
        },
        en: {
            locale: 'en-US',
            clear: 'Clear',
            today: 'Today',
            weekdaysShort: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
            noItemsAvailable: 'No items available.',
            noOptionsAvailable: 'No options available.',
            selectedCount: (selected, total) => `${selected} of ${total} selected`,
            invalidDate: 'Enter a valid date in YYYY-MM-DD format.',
            invalidFilterValue: 'Invalid filter value.',
            fieldsDoNotMatch: 'Fields do not match.',
            characterLabel: (count) => (count === 1 ? 'character' : 'characters'),
            exactLength: (length, unit) => `Must contain exactly ${length} ${unit}.`,
            minLength: (length, unit) => `Must contain at least ${length} ${unit}.`,
            maxLength: (length, unit) => `Must contain at most ${length} ${unit}.`,
        },
    };
    const I18N = I18N_MESSAGES[DOC_LANG] || I18N_MESSAGES.it;

    const FORM_SELECTOR = '.app-validation-form';
    const FIELD_SELECTOR = 'input, select, textarea';
    const MODAL_SELECTOR = '.app-modal';
    const MODAL_BASE_Z_INDEX = 2000;
    const MODAL_STACK_STEP = 20;
    const DATE_PICKER_VIEWPORT_PADDING = 32;
    const SELECT_VIEWPORT_PADDING = 32;
    const SELECT_DROPDOWN_MIN_LIST_HEIGHT = 96;
    const DATE_PICKER_MIN_YEAR = 1900;
    const DATE_PICKER_MAX_YEAR = 2100;

    const isField = (element) =>
        element instanceof HTMLInputElement
        || element instanceof HTMLSelectElement
        || element instanceof HTMLTextAreaElement;

    const escapeSelector = (value) => {
        if (window.CSS && typeof window.CSS.escape === 'function') {
            return window.CSS.escape(value);
        }

        return String(value).replace(/["\\]/g, '\\$&');
    };

    const emitFieldChange = (field) => {
        field.dispatchEvent(new Event('input', { bubbles: true }));
        field.dispatchEvent(new Event('change', { bubbles: true }));
    };

    const eventIncludesElement = (event, element) => {
        if (!(element instanceof HTMLElement)) {
            return false;
        }

        if (typeof event.composedPath === 'function' && event.composedPath().includes(element)) {
            return true;
        }

        return event.target instanceof Node && element.contains(event.target);
    };

    const bindFocusExit = (root, callback) => {
        let lastInternalInteractionAt = 0;
        const markInternalInteraction = () => {
            lastInternalInteractionAt = Date.now();
        };

        root.addEventListener(
            'pointerdown',
            markInternalInteraction,
            { capture: true },
        );
        root.addEventListener(
            'click',
            markInternalInteraction,
            { capture: true },
        );

        root.addEventListener('focusout', (event) => {
            if (event.relatedTarget instanceof Node && root.contains(event.relatedTarget)) {
                return;
            }

            window.setTimeout(() => {
                if (Date.now() - lastInternalInteractionAt < 150 || root.contains(document.activeElement)) {
                    return;
                }

                callback();
            }, 0);
        });
    };

    const submitForm = (form) => {
        if (typeof form.requestSubmit === 'function') {
            form.requestSubmit();
            return;
        }

        form.submit();
    };

    const autoFilterValue = (field) => {
        if (field instanceof HTMLSelectElement && field.multiple) {
            return JSON.stringify(
                Array.from(field.options)
                    .filter((option) => option.selected)
                    .map((option) => option.value),
            );
        }

        return field.value;
    };

    const submitAutoFilterIfChanged = (field) => {
        if (!isField(field) || !field.form) {
            return false;
        }

        if (field.dataset.autoFilterTrigger === undefined) {
            return false;
        }

        applyCustomValidity(field);

        if (field.willValidate && !field.checkValidity()) {
            touchField(field);
            field.reportValidity();

            return false;
        }

        const value = autoFilterValue(field);

        if ((field.dataset.autoFilterLastValue || '') === value) {
            return false;
        }

        field.dataset.autoFilterLastValue = value;
        submitForm(field.form);

        return true;
    };

    const disableSubmitControls = (form) => {
        form.querySelectorAll('button[type="submit"], input[type="submit"]').forEach((control) => {
            if (!(control instanceof HTMLButtonElement) && !(control instanceof HTMLInputElement)) {
                return;
            }

            control.disabled = true;
            control.setAttribute('aria-busy', 'true');
        });
    };

    const initAutoFilters = () => {
        document.addEventListener('click', (event) => {
            if (!(event.target instanceof Node)) {
                return;
            }

            const fields = Array.from(document.querySelectorAll('[data-auto-filter-trigger="outside-click"]'));

            for (const field of fields) {
                if (!isField(field)) {
                    continue;
                }

                const control = field.closest('.app-filter-control') || field;
                if (eventIncludesElement(event, control)) {
                    continue;
                }

                if (submitAutoFilterIfChanged(field)) {
                    break;
                }
            }
        });

        document.querySelectorAll('[data-auto-filter-select-wrapper]').forEach((wrapper) => {
            if (!(wrapper instanceof HTMLElement)) {
                return;
            }

            const field = wrapper.querySelector('[data-auto-filter-trigger="select-mouseleave"]');
            if (!isField(field)) {
                return;
            }

            wrapper.addEventListener('mouseleave', () => {
                submitAutoFilterIfChanged(field);
            });
        });
    };

    const resetSelectDropdownPlacement = (root, list) => {
        root.classList.remove('is-dropup');
        list.style.removeProperty('max-height');
    };

    const positionSelectDropdown = (root, surface, dropdown, list) => {
        if (dropdown.hidden) {
            return;
        }

        list.style.removeProperty('max-height');

        const viewportHeight = window.innerHeight || document.documentElement.clientHeight;
        const surfaceRect = surface.getBoundingClientRect();
        const dropdownHeight = dropdown.getBoundingClientRect().height;
        const dropdownGap = root.classList.contains('app-single-select') ? 7 : 9;
        const spaceAbove = surfaceRect.top - dropdownGap;
        const spaceBelow = viewportHeight - surfaceRect.bottom - dropdownGap;
        const shouldDropUp = spaceBelow < dropdownHeight + SELECT_VIEWPORT_PADDING && spaceAbove > spaceBelow;

        root.classList.toggle('is-dropup', shouldDropUp);

        const availableSpace = (shouldDropUp ? spaceAbove : spaceBelow) - SELECT_VIEWPORT_PADDING;
        const listHeight = list.getBoundingClientRect().height;
        const dropdownChromeHeight = Math.max(0, dropdown.getBoundingClientRect().height - listHeight);
        const defaultMaxListHeight = Number.parseFloat(window.getComputedStyle(list).maxHeight);
        const nextMaxListHeight = Math.max(
            SELECT_DROPDOWN_MIN_LIST_HEIGHT,
            Math.floor(availableSpace - dropdownChromeHeight),
        );
        const cappedMaxListHeight = Number.isFinite(defaultMaxListHeight)
            ? Math.min(defaultMaxListHeight, nextMaxListHeight)
            : nextMaxListHeight;

        list.style.maxHeight = `${Math.floor(cappedMaxListHeight)}px`;
    };

    const padDatePart = (value) => String(value).padStart(2, '0');

    const formatDateValue = (date) => [
        date.getFullYear(),
        padDatePart(date.getMonth() + 1),
        padDatePart(date.getDate()),
    ].join('-');

    const parseDateValue = (value) => {
        const match = /^(\d{4})-(\d{2})-(\d{2})$/.exec(String(value || '').trim());

        if (match === null) {
            return null;
        }

        const year = Number.parseInt(match[1], 10);
        const month = Number.parseInt(match[2], 10) - 1;
        const day = Number.parseInt(match[3], 10);
        const date = new Date(year, month, day);

        if (
            date.getFullYear() !== year
            || date.getMonth() !== month
            || date.getDate() !== day
        ) {
            return null;
        }

        return date;
    };

    const sameDate = (left, right) => (
        left.getFullYear() === right.getFullYear()
        && left.getMonth() === right.getMonth()
        && left.getDate() === right.getDate()
    );

    const monthLabel = (month) => new Intl.DateTimeFormat(I18N.locale, {
        month: 'long',
    }).format(new Date(2000, month, 1));

    const datePickerYearOptions = (selectedYear) => {
        const years = [];

        for (let year = DATE_PICKER_MAX_YEAR; year >= DATE_PICKER_MIN_YEAR; year -= 1) {
            years.push(year);
        }

        if (!years.includes(selectedYear)) {
            years.push(selectedYear);
            years.sort((left, right) => right - left);
        }

        return years;
    };

    const datePickerControlId = (field, suffix) => {
        const base = field.id || field.name || 'date-picker';
        const normalized = String(base).replace(/[^a-zA-Z0-9_-]+/g, '-').replace(/^-+|-+$/g, '');

        return `${normalized || 'date-picker'}-${suffix}`;
    };

    const createDatePickerSingleSelect = ({ field, kind, label, value, options }) => {
        const fieldId = datePickerControlId(field, kind);
        const listId = `${fieldId}-listbox`;
        const root = document.createElement('div');
        root.className = `app-single-select app-multi-select app-date-picker__select-control app-date-picker__select-control--${kind}`;
        root.dataset.singleSelect = 'true';
        root.dataset.singleSelectPersistent = 'true';
        root.dataset.singleSelectMini = 'true';
        root.dataset.placeholder = label;
        root.dataset.emptyOptionsLabel = 'Nessuna opzione disponibile.';
        root.dataset.hasPrompt = 'false';

        if (kind === 'year') {
            root.dataset.singleSelectCenterSelected = 'true';
        }

        const enhanced = document.createElement('div');
        enhanced.className = 'app-single-select__enhanced';
        enhanced.dataset.singleSelectEnhanced = 'true';
        enhanced.hidden = true;

        const surface = document.createElement('div');
        surface.className = 'app-multi-select__surface';
        surface.dataset.singleSelectSurface = 'true';
        surface.tabIndex = 0;
        surface.setAttribute('role', 'combobox');
        surface.setAttribute('aria-haspopup', 'listbox');
        surface.setAttribute('aria-expanded', 'false');
        surface.setAttribute('aria-controls', listId);
        surface.setAttribute('aria-label', label);

        const valueWrap = document.createElement('div');
        valueWrap.className = 'app-multi-select__value';

        const tags = document.createElement('div');
        tags.className = 'app-multi-select__tags';
        tags.dataset.singleSelectTags = 'true';

        const placeholder = document.createElement('span');
        placeholder.className = 'app-multi-select__placeholder';
        placeholder.dataset.singleSelectPlaceholder = 'true';
        placeholder.textContent = label;

        valueWrap.append(tags, placeholder);

        const summary = document.createElement('span');
        summary.className = 'app-multi-select__summary';
        summary.dataset.singleSelectSummary = 'true';

        const caret = document.createElement('span');
        caret.className = 'app-multi-select__caret';
        caret.innerHTML = '<i class="fa-solid fa-chevron-down"></i>';

        surface.append(valueWrap, summary, caret);

        const dropdown = document.createElement('div');
        dropdown.className = 'app-multi-select__dropdown';
        dropdown.dataset.singleSelectDropdown = 'true';
        dropdown.hidden = true;

        const toolbar = document.createElement('div');
        toolbar.className = 'app-multi-select__toolbar';

        const counter = document.createElement('span');
        counter.className = 'app-multi-select__counter';
        counter.dataset.singleSelectCounter = 'true';
        counter.textContent = I18N.selectedCount(0, 0);

        const actions = document.createElement('div');
        actions.className = 'app-multi-select__actions';

        const clearButton = document.createElement('button');
        clearButton.type = 'button';
        clearButton.className = 'btn btn-link btn-sm app-multi-select__action';
        clearButton.dataset.singleSelectClear = 'true';
        clearButton.textContent = I18N.clear;

        actions.append(clearButton);
        toolbar.append(counter, actions);

        const list = document.createElement('div');
        list.className = 'app-multi-select__list';
        list.id = listId;
        list.setAttribute('role', 'listbox');
        list.dataset.singleSelectList = 'true';

        dropdown.append(toolbar, list);
        enhanced.append(surface, dropdown);

        const nativeSelect = document.createElement('select');
        nativeSelect.id = fieldId;
        nativeSelect.className = 'app-form-input__control app-multi-select__native app-single-select__native';
        nativeSelect.dataset.singleSelectNative = 'true';
        nativeSelect.dataset.datePickerPeriod = kind;
        nativeSelect.setAttribute('aria-label', label);

        options.forEach((optionData) => {
            const option = document.createElement('option');
            option.value = String(optionData.value);
            option.textContent = optionData.label;
            option.selected = String(optionData.value) === String(value);
            nativeSelect.append(option);
        });

        root.append(enhanced, nativeSelect);

        return root;
    };

    const positionDatePicker = (root, popup) => {
        if (popup.hidden) {
            return;
        }

        root.classList.remove('is-dropup');

        const viewportHeight = window.innerHeight || document.documentElement.clientHeight;
        const rootRect = root.getBoundingClientRect();
        const popupRect = popup.getBoundingClientRect();
        const gap = 9;
        const spaceAbove = rootRect.top - gap;
        const spaceBelow = viewportHeight - rootRect.bottom - gap;
        const shouldDropUp = spaceBelow < popupRect.height + DATE_PICKER_VIEWPORT_PADDING
            && spaceAbove > spaceBelow;

        root.classList.toggle('is-dropup', shouldDropUp);
    };

    const renderDatePicker = (root, field, state) => {
        const selectedDate = parseDateValue(field.value);
        const today = new Date();
        const monthDate = new Date(state.year, state.month, 1);
        const popup = root.querySelector('[data-date-picker-popup]');

        if (!(popup instanceof HTMLElement)) {
            return;
        }

        popup.replaceChildren();

        const header = document.createElement('div');
        header.className = 'app-date-picker__header';

        const prevButton = document.createElement('button');
        prevButton.type = 'button';
        prevButton.className = 'app-date-picker__nav';
        prevButton.dataset.datePickerPrevious = 'true';
        prevButton.setAttribute('aria-label', 'Mese precedente');
        prevButton.innerHTML = '<i class="fa-solid fa-chevron-left"></i>';

        const period = document.createElement('div');
        period.className = 'app-date-picker__period';

        const monthSelect = createDatePickerSingleSelect({
            field,
            kind: 'month',
            label: 'Mese',
            value: String(state.month),
            options: Array.from({ length: 12 }, (_, month) => ({
                value: String(month),
                label: monthLabel(month),
            })),
        });

        const yearSelect = createDatePickerSingleSelect({
            field,
            kind: 'year',
            label: 'Anno',
            value: String(state.year),
            options: datePickerYearOptions(state.year).map((year) => ({
                value: String(year),
                label: String(year),
            })),
        });

        period.append(monthSelect, yearSelect);

        const nextButton = document.createElement('button');
        nextButton.type = 'button';
        nextButton.className = 'app-date-picker__nav';
        nextButton.dataset.datePickerNext = 'true';
        nextButton.setAttribute('aria-label', 'Mese successivo');
        nextButton.innerHTML = '<i class="fa-solid fa-chevron-right"></i>';

        header.append(prevButton, period, nextButton);

        const weekdays = document.createElement('div');
        weekdays.className = 'app-date-picker__weekdays';

        I18N.weekdaysShort.forEach((day) => {
            const item = document.createElement('span');
            item.textContent = day;
            weekdays.append(item);
        });

        const days = document.createElement('div');
        days.className = 'app-date-picker__days';

        const firstDayOffset = (monthDate.getDay() + 6) % 7;
        const daysInMonth = new Date(state.year, state.month + 1, 0).getDate();

        for (let index = 0; index < firstDayOffset; index += 1) {
            const placeholder = document.createElement('span');
            placeholder.className = 'app-date-picker__day-placeholder';
            days.append(placeholder);
        }

        for (let day = 1; day <= daysInMonth; day += 1) {
            const date = new Date(state.year, state.month, day);
            const dayButton = document.createElement('button');
            dayButton.type = 'button';
            dayButton.className = 'app-date-picker__day';
            dayButton.dataset.datePickerDay = formatDateValue(date);
            dayButton.textContent = String(day);

            if (sameDate(date, today)) {
                dayButton.classList.add('is-today');
            }

            if (selectedDate !== null && sameDate(date, selectedDate)) {
                dayButton.classList.add('is-selected');
            }

            days.append(dayButton);
        }

        const footer = document.createElement('div');
        footer.className = 'app-date-picker__footer';

        const todayButton = document.createElement('button');
        todayButton.type = 'button';
        todayButton.className = 'app-date-picker__action';
        todayButton.dataset.datePickerToday = 'true';
        todayButton.textContent = I18N.today;

        const clearButton = document.createElement('button');
        clearButton.type = 'button';
        clearButton.className = 'app-date-picker__action';
        clearButton.dataset.datePickerClear = 'true';
        clearButton.textContent = I18N.clear;

        footer.append(todayButton, clearButton);
        popup.append(header, weekdays, days, footer);
        initSingleSelects(period);
    };

    const initDatePickers = () => {
        document.querySelectorAll('[data-date-picker]').forEach((field) => {
            if (!(field instanceof HTMLInputElement)) {
                return;
            }

            const root = field.closest('.app-form-input--date');

            if (!(root instanceof HTMLElement)) {
                return;
            }

            const initialDate = parseDateValue(field.value) || new Date();
            const state = {
                isOpen: false,
                month: initialDate.getMonth(),
                year: initialDate.getFullYear(),
            };

            const popup = document.createElement('div');
            popup.className = 'app-date-picker';
            popup.dataset.datePickerPopup = 'true';
            popup.hidden = true;
            root.append(popup);

            const setMonth = (year, month) => {
                const next = new Date(year, month, 1);

                if (Number.isNaN(next.getTime())) {
                    return;
                }

                state.year = next.getFullYear();
                state.month = next.getMonth();
                renderDatePicker(root, field, state);
                window.requestAnimationFrame(() => positionDatePicker(root, popup));
            };

            const setOpen = (nextState) => {
                if (field.disabled || field.readOnly) {
                    nextState = false;
                }

                state.isOpen = nextState;
                root.classList.toggle('is-open', state.isOpen);
                field.setAttribute('aria-expanded', String(state.isOpen));

                if (!state.isOpen) {
                    popup.hidden = true;
                    root.classList.remove('is-dropup');
                    return;
                }

                const selectedDate = parseDateValue(field.value);

                if (selectedDate !== null) {
                    state.year = selectedDate.getFullYear();
                    state.month = selectedDate.getMonth();
                }

                renderDatePicker(root, field, state);
                popup.hidden = false;
                window.requestAnimationFrame(() => positionDatePicker(root, popup));
            };

            const commitValue = (value) => {
                field.value = value;
                emitFieldChange(field);
                touchField(field);
                setOpen(false);
                field.focus();
            };

            field.setAttribute('aria-haspopup', 'dialog');
            field.setAttribute('aria-expanded', 'false');

            field.addEventListener('focus', () => setOpen(true));
            field.addEventListener('click', () => setOpen(true));
            field.addEventListener('input', () => {
                const parsed = parseDateValue(field.value);

                if (parsed !== null) {
                    setMonth(parsed.getFullYear(), parsed.getMonth());
                }
            });
            field.addEventListener('keydown', (event) => {
                if (event.key === 'Escape') {
                    setOpen(false);
                    return;
                }

                if (event.key === 'ArrowDown') {
                    event.preventDefault();
                    setOpen(true);
                    popup.querySelector('[data-date-picker-day]')?.focus();
                }
            });

            popup.addEventListener('click', (event) => {
                const target = event.target instanceof Element
                    ? event.target.closest('button')
                    : null;

                if (!(target instanceof HTMLButtonElement)) {
                    return;
                }

                event.preventDefault();

                if (target.dataset.datePickerPrevious === 'true') {
                    setMonth(state.year, state.month - 1);
                    return;
                }

                if (target.dataset.datePickerNext === 'true') {
                    setMonth(state.year, state.month + 1);
                    return;
                }

                if (target.dataset.datePickerToday === 'true') {
                    commitValue(formatDateValue(new Date()));
                    return;
                }

                if (target.dataset.datePickerClear === 'true') {
                    commitValue('');
                    return;
                }

                if (target.dataset.datePickerDay) {
                    commitValue(target.dataset.datePickerDay);
                }
            });

            popup.addEventListener('change', (event) => {
                const target = event.target;

                if (!(target instanceof HTMLSelectElement)) {
                    return;
                }

                if (target.dataset.datePickerPeriod === 'month') {
                    const nextMonth = Number.parseInt(target.value, 10);

                    if (Number.isNaN(nextMonth)) {
                        return;
                    }

                    window.requestAnimationFrame(() => setMonth(state.year, nextMonth));
                    return;
                }

                if (target.dataset.datePickerPeriod === 'year') {
                    const nextYear = Number.parseInt(target.value, 10);

                    if (Number.isNaN(nextYear)) {
                        return;
                    }

                    window.requestAnimationFrame(() => setMonth(nextYear, state.month));
                }
            });

            popup.addEventListener('keydown', (event) => {
                if (event.key !== 'Escape') {
                    return;
                }

                event.preventDefault();
                setOpen(false);
                field.focus();
            });

            bindFocusExit(root, () => setOpen(false));

            document.addEventListener('click', (event) => {
                if (!(event.target instanceof Node) || eventIncludesElement(event, root)) {
                    return;
                }

                setOpen(false);
            });

            const positionOpenPicker = () => {
                if (state.isOpen) {
                    positionDatePicker(root, popup);
                }
            };

            window.addEventListener('resize', positionOpenPicker);
            window.addEventListener('scroll', positionOpenPicker, { capture: true, passive: true });
        });
    };

    const getFields = (form) => Array.from(form.querySelectorAll(FIELD_SELECTOR)).filter(
        (field) => isField(field) && field.willValidate,
    );

    const getFeedback = (field) => field.closest('.app-form-input')?.querySelector('[data-validation-feedback]');

    const resolveMatchField = (field) => {
        const matchFieldName = field.dataset.matchField;

        if (!matchFieldName || !field.form) {
            return null;
        }

        return field.form.querySelector(`[name="${escapeSelector(matchFieldName)}"]`);
    };

    const resolveAllowedValues = (field) => {
        const rawValues = field.dataset.filterAllowedValues;

        if (!rawValues) {
            return null;
        }

        try {
            const values = JSON.parse(rawValues);

            if (!Array.isArray(values)) {
                return null;
            }

            return values.map((value) => String(value));
        } catch (error) {
            return null;
        }
    };

    const currentFieldValues = (field) => {
        if (field instanceof HTMLSelectElement && field.multiple) {
            return Array.from(field.selectedOptions).map((option) => option.value);
        }

        return [field.value];
    };

    const parseIntegerData = (value) => {
        if (value === undefined || value === '') {
            return null;
        }

        const parsed = Number.parseInt(value, 10);

        return Number.isFinite(parsed) ? parsed : null;
    };

    const characterLabel = (count) => I18N.characterLabel(count);

    const setLengthValidity = (field, message) => {
        field.setCustomValidity(message);

        return false;
    };

    const lengthRuleValue = (field, name) => parseIntegerData(
        field.dataset[`input${name}`] ?? field.dataset[`filter${name}`],
    );

    const applyLengthValidity = (field) => {
        if (!(field instanceof HTMLInputElement) && !(field instanceof HTMLTextAreaElement)) {
            return true;
        }

        if (field.value === '') {
            return true;
        }

        const exactLength = lengthRuleValue(field, 'ExactLength');
        const minLength = lengthRuleValue(field, 'MinLength');
        const maxLength = lengthRuleValue(field, 'MaxLength');

        if (exactLength === null && minLength === null && maxLength === null) {
            return true;
        }

        const length = Array.from(field.value).length;

        if (exactLength !== null && length !== exactLength) {
            return setLengthValidity(
                field,
                I18N.exactLength(exactLength, characterLabel(exactLength)),
            );
        }

        if (minLength !== null && length < minLength) {
            return setLengthValidity(
                field,
                I18N.minLength(minLength, characterLabel(minLength)),
            );
        }

        if (maxLength !== null && length > maxLength) {
            return setLengthValidity(
                field,
                I18N.maxLength(maxLength, characterLabel(maxLength)),
            );
        }

        return true;
    };

    const applyDateValidity = (field) => {
        if (!(field instanceof HTMLInputElement) || field.dataset.datePicker !== 'true') {
            return true;
        }

        if (field.value === '') {
            return true;
        }

        if (parseDateValue(field.value) !== null) {
            return true;
        }

        field.setCustomValidity(
            field.dataset.dateValidationMessage || I18N.invalidDate,
        );

        return false;
    };

    const applyAllowedValuesValidity = (field) => {
        const allowedValues = resolveAllowedValues(field);

        if (allowedValues === null) {
            return true;
        }

        const allowedSet = new Set(allowedValues);
        const hasInvalidValue = currentFieldValues(field).some((value) => (
            value !== '' && !allowedSet.has(value)
        ));

        if (!hasInvalidValue) {
            return true;
        }

        field.setCustomValidity(field.dataset.filterValidationMessage || I18N.invalidFilterValue);

        return false;
    };

    const applyCustomValidity = (field) => {
        field.setCustomValidity('');

        if (!applyAllowedValuesValidity(field)) {
            return;
        }

        if (!applyLengthValidity(field)) {
            return;
        }

        if (!applyDateValidity(field)) {
            return;
        }

        const matchField = resolveMatchField(field);
        if (matchField === null) {
            return;
        }

        if (field.value !== '' && matchField.value !== '' && field.value !== matchField.value) {
            field.setCustomValidity(field.dataset.matchMessage || I18N.fieldsDoNotMatch);
        }
    };

    const updateFieldState = (field, force = false) => {
        applyCustomValidity(field);

        if (!force && field.dataset.touched !== 'true') {
            return;
        }

        const feedback = getFeedback(field);
        const isValid = field.validity.valid;

        field.classList.remove('is-valid', 'is-invalid');
        field.classList.add(isValid ? 'is-valid' : 'is-invalid');

        if (feedback) {
            feedback.textContent = isValid ? '' : field.validationMessage;
        }
    };

    const updateDependentFields = (field) => {
        const fieldName = field.getAttribute('name');
        if (!field.form || !fieldName) {
            return;
        }

        field.form
            .querySelectorAll(`[data-match-field="${escapeSelector(fieldName)}"]`)
            .forEach((dependentField) => {
                if (!isField(dependentField)) {
                    return;
                }

                updateFieldState(
                    dependentField,
                    dependentField.dataset.touched === 'true' || dependentField.form?.dataset.validated === '1',
                );
            });
    };

    const touchField = (field) => {
        field.dataset.touched = 'true';
        updateFieldState(field, true);
        updateDependentFields(field);
    };

    const initFilterValidation = () => {
        document.querySelectorAll('[data-filter-validation-field]').forEach((field) => {
            if (!isField(field) || !field.willValidate) {
                return;
            }

            field.addEventListener('input', () => {
                touchField(field);
            });

            field.addEventListener('change', () => {
                touchField(field);
            });

            field.addEventListener('blur', () => {
                touchField(field);
            });
        });
    };

    const isModal = (element) => element instanceof HTMLElement && element.matches(MODAL_SELECTOR);

    const moveModalToBody = (modal) => {
        if (modal.parentElement !== document.body) {
            document.body.append(modal);
        }
    };

    const syncModalStack = (modal) => {
        const visibleModals = Array.from(document.querySelectorAll(`${MODAL_SELECTOR}.show`)).filter(
            (element) => element instanceof HTMLElement,
        );
        const stackIndex = Math.max(visibleModals.length - 1, 0);
        const modalZIndex = MODAL_BASE_Z_INDEX + (stackIndex * MODAL_STACK_STEP);

        modal.style.zIndex = String(modalZIndex);

        window.requestAnimationFrame(() => {
            const backdrop = Array.from(document.querySelectorAll('.modal-backdrop'))
                .filter((element) => element instanceof HTMLElement)
                .at(-1);

            if (backdrop instanceof HTMLElement) {
                backdrop.style.zIndex = String(modalZIndex - 10);
            }
        });
    };

    document.addEventListener('show.bs.modal', (event) => {
        if (!isModal(event.target)) {
            return;
        }

        moveModalToBody(event.target);
    });

    document.addEventListener('shown.bs.modal', (event) => {
        if (!isModal(event.target)) {
            return;
        }

        syncModalStack(event.target);
    });

    document.addEventListener('hidden.bs.modal', (event) => {
        if (!isModal(event.target)) {
            return;
        }

        event.target.style.removeProperty('z-index');

        if (document.querySelector(`${MODAL_SELECTOR}.show`) !== null) {
            document.body.classList.add('modal-open');
        }
    });

    document.querySelectorAll('[data-multi-select]').forEach((root) => {
        if (!(root instanceof HTMLElement)) {
            return;
        }

        const nativeSelect = root.querySelector('[data-multi-select-native]');
        const enhanced = root.querySelector('[data-multi-select-enhanced]');
        const surface = root.querySelector('[data-multi-select-surface]');
        const dropdown = root.querySelector('[data-multi-select-dropdown]');
        const list = root.querySelector('[data-multi-select-list]');
        const tags = root.querySelector('[data-multi-select-tags]');
        const placeholder = root.querySelector('[data-multi-select-placeholder]');
        const summary = root.querySelector('[data-multi-select-summary]');
        const counter = root.querySelector('[data-multi-select-counter]');
        const selectAllButton = root.querySelector('[data-multi-select-select-all]');
        const clearButton = root.querySelector('[data-multi-select-clear]');

        if (!(nativeSelect instanceof HTMLSelectElement)
            || !(enhanced instanceof HTMLElement)
            || !(surface instanceof HTMLElement)
            || !(dropdown instanceof HTMLElement)
            || !(list instanceof HTMLElement)
            || !(tags instanceof HTMLElement)
            || !(placeholder instanceof HTMLElement)
            || !(summary instanceof HTMLElement)
            || !(counter instanceof HTMLElement)
            || !(selectAllButton instanceof HTMLButtonElement)
            || !(clearButton instanceof HTMLButtonElement)
        ) {
            return;
        }

        let isOpen = false;
        let hasInteracted = false;

        const allOptions = () => Array.from(nativeSelect.options);
        const selectableOptions = () => allOptions().filter((option) => !option.disabled);
        const selectedOptions = () => allOptions().filter((option) => option.selected);
        const summaryText = (selectedCount, totalCount) => (
            selectedCount > 0 && totalCount > 0 ? `${selectedCount}/${totalCount}` : ''
        );

        const setOpen = (nextState) => {
            if (nativeSelect.disabled) {
                nextState = false;
            }

            isOpen = nextState;
            root.classList.toggle('is-open', isOpen);
            surface.setAttribute('aria-expanded', String(isOpen));

            if (isOpen) {
                dropdown.hidden = false;
                positionSelectDropdown(root, surface, dropdown, list);
                return;
            }

            dropdown.hidden = true;
            resetSelectDropdownPlacement(root, list);
        };

        const syncSelection = (notify = true) => {
            const selected = selectedOptions();
            const all = allOptions();
            const selectable = selectableOptions();
            const selectedSelectableCount = selectable.filter((option) => option.selected).length;

            tags.replaceChildren();
            list.replaceChildren();

            placeholder.textContent = root.dataset.placeholder || '';
            placeholder.hidden = selected.length > 0;
            summary.textContent = summaryText(selected.length, all.length);
            counter.textContent = all.length === 0
                ? (root.dataset.emptyOptionsLabel || 'Nessun elemento disponibile.')
                : I18N.selectedCount(selected.length, all.length);

            root.classList.toggle('is-disabled', nativeSelect.disabled);
            surface.setAttribute('aria-disabled', String(nativeSelect.disabled));
            surface.tabIndex = nativeSelect.disabled ? -1 : 0;

            selectAllButton.disabled = nativeSelect.disabled || selectable.length === 0 || selectedSelectableCount === selectable.length;
            clearButton.disabled = nativeSelect.disabled || selected.length === 0;

            selected.forEach((option) => {
                const tag = document.createElement('button');
                tag.type = 'button';
                tag.className = 'app-multi-select__tag';
                tag.dataset.multiSelectRemove = option.value;

                const tagLabel = document.createElement('span');
                tagLabel.className = 'app-multi-select__tag-label';
                tagLabel.textContent = option.textContent || option.label || option.value;

                const tagRemove = document.createElement('span');
                tagRemove.className = 'app-multi-select__tag-remove';
                tagRemove.setAttribute('aria-hidden', 'true');
                tagRemove.innerHTML = '<i class="fa-solid fa-xmark"></i>';

                tag.append(tagLabel, tagRemove);
                tags.append(tag);
            });

            if (all.length === 0) {
                const emptyState = document.createElement('div');
                emptyState.className = 'app-multi-select__empty';
                emptyState.textContent = root.dataset.emptyOptionsLabel || I18N.noItemsAvailable;
                list.append(emptyState);
            } else {
                all.forEach((option) => {
                    const optionButton = document.createElement('button');
                    optionButton.type = 'button';
                    optionButton.className = 'app-multi-select__option';
                    optionButton.dataset.multiSelectOption = option.value;
                    optionButton.setAttribute('role', 'option');
                    optionButton.setAttribute('aria-selected', option.selected ? 'true' : 'false');
                    optionButton.disabled = nativeSelect.disabled || option.disabled;

                    if (option.selected) {
                        optionButton.classList.add('is-selected');
                    }

                    const optionLabel = document.createElement('span');
                    optionLabel.className = 'app-multi-select__option-label';
                    optionLabel.textContent = option.textContent || option.label || option.value;

                    const optionCheck = document.createElement('span');
                    optionCheck.className = 'app-multi-select__option-check';
                    optionCheck.setAttribute('aria-hidden', 'true');
                    optionCheck.innerHTML = '<i class="fa-solid fa-check"></i>';

                    optionButton.append(optionLabel, optionCheck);
                    list.append(optionButton);
                });
            }

            if (notify) {
                emitFieldChange(nativeSelect);
            }

            if (isOpen) {
                window.requestAnimationFrame(() => positionSelectDropdown(root, surface, dropdown, list));
            }
        };

        const toggleOption = (value, forcedState = null) => {
            const option = allOptions().find((item) => item.value === value);

            if (!option || option.disabled || nativeSelect.disabled) {
                return;
            }

            option.selected = forcedState === null ? !option.selected : forcedState;
            syncSelection();
        };

        surface.addEventListener('click', () => {
            hasInteracted = true;
            setOpen(true);
        });

        surface.addEventListener('keydown', (event) => {
            if (nativeSelect.disabled) {
                return;
            }

            if (event.key === 'Enter' || event.key === ' ') {
                event.preventDefault();
                hasInteracted = true;
                setOpen(true);
                return;
            }

            if (event.key === 'ArrowDown') {
                event.preventDefault();
                hasInteracted = true;
                setOpen(true);
                list.querySelector('[data-multi-select-option]')?.focus();
                return;
            }

            if (event.key === 'Escape') {
                setOpen(false);
            }
        });

        tags.addEventListener('click', (event) => {
            const target = event.target instanceof Element
                ? event.target.closest('[data-multi-select-remove]')
                : null;

            if (!(target instanceof HTMLButtonElement)) {
                return;
            }

            event.preventDefault();
            event.stopPropagation();
            hasInteracted = true;
            toggleOption(target.dataset.multiSelectRemove || '', false);
        });

        list.addEventListener('click', (event) => {
            const target = event.target instanceof Element
                ? event.target.closest('[data-multi-select-option]')
                : null;

            if (!(target instanceof HTMLButtonElement)) {
                return;
            }

            event.preventDefault();
            hasInteracted = true;
            toggleOption(target.dataset.multiSelectOption || '');
        });

        list.addEventListener('keydown', (event) => {
            if (event.key !== 'Escape') {
                return;
            }

            event.preventDefault();
            setOpen(false);
            surface.focus();
        });

        selectAllButton.addEventListener('click', (event) => {
            event.preventDefault();
            hasInteracted = true;

            selectableOptions().forEach((option) => {
                option.selected = true;
            });

            syncSelection();
        });

        clearButton.addEventListener('click', (event) => {
            event.preventDefault();
            hasInteracted = true;

            allOptions().forEach((option) => {
                option.selected = false;
            });

            syncSelection();
        });

        nativeSelect.addEventListener('change', () => {
            syncSelection(false);
        });

        bindFocusExit(root, () => {
            setOpen(false);

            if (hasInteracted) {
                touchField(nativeSelect);
            }
        });

        document.addEventListener('click', (event) => {
            if (!(event.target instanceof Node) || eventIncludesElement(event, root)) {
                return;
            }

            setOpen(false);

            if (hasInteracted) {
                touchField(nativeSelect);
            }
        });

        document.addEventListener('keydown', (event) => {
            if (event.key !== 'Escape' || !isOpen) {
                return;
            }

            setOpen(false);
        });

        const positionOpenDropdown = () => {
            if (isOpen) {
                positionSelectDropdown(root, surface, dropdown, list);
            }
        };

        window.addEventListener('resize', positionOpenDropdown);
        window.addEventListener('scroll', positionOpenDropdown, { capture: true, passive: true });

        enhanced.hidden = false;
        root.classList.add('is-ready');
        syncSelection(false);
    });

    const initSingleSelect = (root) => {
        if (!(root instanceof HTMLElement) || root.dataset.singleSelectReady === 'true') {
            return;
        }

        const nativeSelect = root.querySelector('[data-single-select-native]');
        const enhanced = root.querySelector('[data-single-select-enhanced]');
        const surface = root.querySelector('[data-single-select-surface]');
        const dropdown = root.querySelector('[data-single-select-dropdown]');
        const list = root.querySelector('[data-single-select-list]');
        const tags = root.querySelector('[data-single-select-tags]');
        const placeholder = root.querySelector('[data-single-select-placeholder]');
        const summary = root.querySelector('[data-single-select-summary]');
        const counter = root.querySelector('[data-single-select-counter]');
        const clearButton = root.querySelector('[data-single-select-clear]');

        if (!(nativeSelect instanceof HTMLSelectElement)
            || !(enhanced instanceof HTMLElement)
            || !(surface instanceof HTMLElement)
            || !(dropdown instanceof HTMLElement)
            || !(list instanceof HTMLElement)
            || !(tags instanceof HTMLElement)
            || !(placeholder instanceof HTMLElement)
            || !(summary instanceof HTMLElement)
            || !(counter instanceof HTMLElement)
            || !(clearButton instanceof HTMLButtonElement)
        ) {
            return;
        }

        root.dataset.singleSelectReady = 'true';

        let isOpen = false;
        let hasInteracted = false;

        const persistent = root.dataset.singleSelectPersistent === 'true';
        const mini = root.dataset.singleSelectMini === 'true';
        const centerSelected = root.dataset.singleSelectCenterSelected === 'true';
        const hasPrompt = root.dataset.hasPrompt === 'true';
        const allOptions = () => Array.from(nativeSelect.options);
        const promptOption = () => {
            if (!hasPrompt) {
                return null;
            }

            return allOptions().find(
                (option) => option.value === '' && option.parentElement === nativeSelect,
            ) || null;
        };
        const isPromptOption = (option) => option === promptOption();
        const selectableOptions = () => allOptions().filter(
            (option) => !isPromptOption(option),
        );
        const selectedOption = () => {
            const option = nativeSelect.selectedOptions.item(0);

            if (!(option instanceof HTMLOptionElement) || isPromptOption(option)) {
                return null;
            }

            return option;
        };
        const summaryText = (selected, totalCount) => (
            selected !== null && totalCount > 0 ? `1/${totalCount}` : ''
        );
        const centerSelectedOption = () => {
            if (!centerSelected) {
                return;
            }

            const selectedButton = list.querySelector('[data-single-select-option].is-selected');

            if (!(selectedButton instanceof HTMLElement)) {
                return;
            }

            selectedButton.scrollIntoView({ block: 'center' });
        };

        const setOpen = (nextState) => {
            if (nativeSelect.disabled) {
                nextState = false;
            }

            isOpen = nextState;
            root.classList.toggle('is-open', isOpen);
            surface.setAttribute('aria-expanded', String(isOpen));

            if (isOpen) {
                dropdown.hidden = false;
                positionSelectDropdown(root, surface, dropdown, list);
                window.requestAnimationFrame(centerSelectedOption);
                return;
            }

            dropdown.hidden = true;
            resetSelectDropdownPlacement(root, list);
        };

        const clearSelection = (notify = true) => {
            if (nativeSelect.disabled) {
                return;
            }

            const emptyOption = promptOption();
            if (emptyOption instanceof HTMLOptionElement) {
                nativeSelect.value = emptyOption.value;
            } else {
                nativeSelect.selectedIndex = -1;
            }

            syncSelection(notify);
        };

        const buildOptionButton = (option) => {
            const optionButton = document.createElement('button');
            optionButton.type = 'button';
            optionButton.className = 'app-multi-select__option';
            optionButton.dataset.singleSelectOption = option.value;
            optionButton.setAttribute('role', 'option');
            optionButton.setAttribute('aria-selected', option.selected ? 'true' : 'false');
            optionButton.disabled = nativeSelect.disabled || option.disabled;

            if (option.selected) {
                optionButton.classList.add('is-selected');
            }

            const optionLabel = document.createElement('span');
            optionLabel.className = 'app-multi-select__option-label';
            optionLabel.textContent = option.textContent || option.label || option.value;

            const optionCheck = document.createElement('span');
            optionCheck.className = 'app-multi-select__option-check';
            optionCheck.setAttribute('aria-hidden', 'true');
            optionCheck.innerHTML = '<i class="fa-solid fa-check"></i>';

            optionButton.append(optionLabel, optionCheck);

            return optionButton;
        };

        const renderOptions = () => {
            list.replaceChildren();

            if (selectableOptions().length === 0) {
                const emptyState = document.createElement('div');
                emptyState.className = 'app-multi-select__empty';
                emptyState.textContent = root.dataset.emptyOptionsLabel || I18N.noOptionsAvailable;
                list.append(emptyState);
                return;
            }

            Array.from(nativeSelect.children).forEach((child) => {
                if (child instanceof HTMLOptionElement) {
                    if (isPromptOption(child)) {
                        return;
                    }

                    list.append(buildOptionButton(child));
                    return;
                }

                if (!(child instanceof HTMLOptGroupElement)) {
                    return;
                }

                const groupedOptions = Array.from(child.querySelectorAll('option')).filter(
                    (option) => !isPromptOption(option),
                );

                if (groupedOptions.length === 0) {
                    return;
                }

                const group = document.createElement('div');
                group.className = 'app-multi-select__group';

                const groupLabel = document.createElement('div');
                groupLabel.className = 'app-multi-select__group-label';
                groupLabel.textContent = child.label;

                const groupOptions = document.createElement('div');
                groupOptions.className = 'app-multi-select__group-options';

                groupedOptions.forEach((option) => {
                    groupOptions.append(buildOptionButton(option));
                });

                group.append(groupLabel, groupOptions);
                list.append(group);
            });
        };

        const syncSelection = (notify = true) => {
            const availableOptions = selectableOptions();
            const selected = selectedOption();

            tags.replaceChildren();
            renderOptions();

            placeholder.textContent = root.dataset.placeholder || '';
            placeholder.hidden = selected !== null;
            summary.textContent = summaryText(selected, availableOptions.length);
            counter.textContent = availableOptions.length === 0
                ? (root.dataset.emptyOptionsLabel || 'Nessuna opzione disponibile.')
                : I18N.selectedCount(selected !== null ? 1 : 0, availableOptions.length);

            root.classList.toggle('is-disabled', nativeSelect.disabled);
            surface.setAttribute('aria-disabled', String(nativeSelect.disabled));
            surface.tabIndex = nativeSelect.disabled ? -1 : 0;
            clearButton.disabled = nativeSelect.disabled || selected === null;

            if (selected !== null) {
                const tag = document.createElement(mini ? 'span' : 'button');
                tag.className = 'app-multi-select__tag';

                if (!mini) {
                    tag.type = 'button';
                    tag.dataset.singleSelectClearTag = 'true';
                }

                const tagLabel = document.createElement('span');
                tagLabel.className = 'app-multi-select__tag-label';
                tagLabel.textContent = selected.textContent || selected.label || selected.value;

                tag.append(tagLabel);

                if (!mini) {
                    const tagRemove = document.createElement('span');
                    tagRemove.className = 'app-multi-select__tag-remove';
                    tagRemove.setAttribute('aria-hidden', 'true');
                    tagRemove.innerHTML = '<i class="fa-solid fa-xmark"></i>';
                    tag.append(tagRemove);
                }

                tags.append(tag);
            }

            if (notify) {
                emitFieldChange(nativeSelect);
            }

            if (isOpen) {
                window.requestAnimationFrame(() => positionSelectDropdown(root, surface, dropdown, list));
            }
        };

        const selectOption = (value) => {
            const option = selectableOptions().find((item) => item.value === value);

            if (!option || option.disabled || nativeSelect.disabled) {
                return;
            }

            nativeSelect.value = option.value;
            syncSelection();

            if (!persistent) {
                setOpen(false);
                surface.focus();
            }
        };

        surface.addEventListener('click', () => {
            hasInteracted = true;
            setOpen(persistent ? true : !isOpen);
        });

        surface.addEventListener('keydown', (event) => {
            if (nativeSelect.disabled) {
                return;
            }

            if (event.key === 'Enter' || event.key === ' ') {
                event.preventDefault();
                hasInteracted = true;
                setOpen(persistent ? true : !isOpen);
                return;
            }

            if (event.key === 'ArrowDown') {
                event.preventDefault();
                hasInteracted = true;
                setOpen(true);
                list.querySelector('[data-single-select-option].is-selected, [data-single-select-option]')?.focus();
                return;
            }

            if (event.key === 'Escape') {
                setOpen(false);
            }
        });

        tags.addEventListener('click', (event) => {
            const target = event.target instanceof Element
                ? event.target.closest('[data-single-select-clear-tag]')
                : null;

            if (!(target instanceof HTMLButtonElement)) {
                return;
            }

            event.preventDefault();
            event.stopPropagation();
            hasInteracted = true;
            clearSelection();
            submitAutoFilterIfChanged(nativeSelect);
        });

        list.addEventListener('click', (event) => {
            const target = event.target instanceof Element
                ? event.target.closest('[data-single-select-option]')
                : null;

            if (!(target instanceof HTMLButtonElement)) {
                return;
            }

            event.preventDefault();
            hasInteracted = true;
            selectOption(target.dataset.singleSelectOption || '');
        });

        list.addEventListener('keydown', (event) => {
            if (event.key !== 'Escape') {
                return;
            }

            event.preventDefault();
            setOpen(false);
            surface.focus();
        });

        bindFocusExit(root, () => {
            setOpen(false);

            if (hasInteracted) {
                touchField(nativeSelect);
            }
        });

        clearButton.addEventListener('click', (event) => {
            event.preventDefault();
            hasInteracted = true;
            clearSelection();
        });

        nativeSelect.addEventListener('change', () => {
            syncSelection(false);
        });

        document.addEventListener('click', (event) => {
            if (!(event.target instanceof Node) || eventIncludesElement(event, root)) {
                return;
            }

            setOpen(false);

            if (hasInteracted) {
                touchField(nativeSelect);
            }
        });

        document.addEventListener('keydown', (event) => {
            if (event.key !== 'Escape' || !isOpen) {
                return;
            }

            setOpen(false);
        });

        const positionOpenDropdown = () => {
            if (isOpen) {
                positionSelectDropdown(root, surface, dropdown, list);
            }
        };

        window.addEventListener('resize', positionOpenDropdown);
        window.addEventListener('scroll', positionOpenDropdown, { capture: true, passive: true });

        enhanced.hidden = false;
        root.classList.add('is-ready');
        syncSelection(false);
    };

    const initSingleSelects = (scope = document) => {
        if (scope instanceof HTMLElement && scope.matches('[data-single-select]')) {
            initSingleSelect(scope);
        }

        scope.querySelectorAll('[data-single-select]').forEach(initSingleSelect);
    };

    initSingleSelects();


    initDatePickers();

    document.querySelectorAll(FORM_SELECTOR).forEach((form) => {
        if (!(form instanceof HTMLFormElement)) {
            return;
        }

        if (form.dataset.validated === '1') {
            form.classList.add('was-validated');
        }

        getFields(form).forEach((field) => {
            field.addEventListener('input', () => {
                touchField(field);
            });

            field.addEventListener('change', () => {
                touchField(field);
            });

            field.addEventListener('blur', () => {
                touchField(field);
            });
        });

        form.addEventListener(
            'invalid',
            (event) => {
                if (!isField(event.target)) {
                    return;
                }

                form.dataset.validated = '1';
                form.classList.add('was-validated');
                event.target.dataset.touched = 'true';
                updateFieldState(event.target, true);
            },
            true,
        );

        form.addEventListener('submit', (event) => {
            const lockSubmit = form.dataset.disableSubmitLock !== '1';

            if (lockSubmit && form.dataset.submitting === '1') {
                event.preventDefault();
                return;
            }

            form.dataset.validated = '1';
            form.classList.add('was-validated');
            getFields(form).forEach((field) => updateFieldState(field, true));

            if (!form.checkValidity()) {
                return;
            }

            if (lockSubmit) {
                form.dataset.submitting = '1';
                disableSubmitControls(form);
            }
        });
    });

    initAutoFilters();
    initFilterValidation();
})();

// MODAL FUNCTIONALITY

const modal = document.getElementById('listingModal');
const openBtn = document.getElementById('openModalBtn');
const closeBtn = document.getElementById('closeModalBtn');
const modalOverlay = document.querySelector('.modal-overlay');

function openListingModal() {
    if (!modal) return;

    modal.classList.add('active');
    document.body.classList.add('modal-open');
}

function closeListingModal() {
    if (!modal) return;

    modal.classList.remove('active');
    document.body.classList.remove('modal-open');
}

openBtn?.addEventListener('click', openListingModal);
closeBtn?.addEventListener('click', closeListingModal);
modalOverlay?.addEventListener('click', closeListingModal);

document.querySelectorAll('[data-close-modal]').forEach(button => {
    button.addEventListener('click', closeListingModal);
});

document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape' && modal?.classList.contains('active')) {
        closeListingModal();
    }
});

// FORM STEPPER

const listingForm = document.getElementById('listingForm');
const steps = document.querySelectorAll('.form-step');
const stepIndicators = document.querySelectorAll('.stepper .step');
const listingReview = document.getElementById('listingReview');
let currentStep = 0;

function updateSteps() {
    steps.forEach((step, index) => {
        step.classList.toggle('active', index === currentStep);
    });

    stepIndicators.forEach((step, index) => {
        step.classList.toggle('active', index <= currentStep);
    });

    if (currentStep === steps.length - 1) {
        updateReview();
    }
}

function validateCurrentStep() {
    const current = steps[currentStep];
    const fields = current?.querySelectorAll('input, select, textarea') || [];

    for (const field of fields) {
        if (field.type === 'hidden' || field.disabled) continue;

        if (!field.checkValidity()) {
            field.reportValidity();
            return false;
        }
    }

    if (current?.querySelector('.day-options')) {
        const selectedDay = current.querySelector('.day-options input:checked');

        if (!selectedDay) {
            alert('Please choose at least one available day.');
            return false;
        }
    }

    return true;
}

document.querySelectorAll('.next-btn').forEach(button => {
    button.addEventListener('click', (event) => {
        event.preventDefault();

        if (!validateCurrentStep()) return;

        if (currentStep < steps.length - 1) {
            currentStep++;
            updateSteps();
        }
    });
});

document.querySelectorAll('.prev-btn').forEach(button => {
    button.addEventListener('click', (event) => {
        event.preventDefault();

        if (currentStep > 0) {
            currentStep--;
            updateSteps();
        }
    });
});

// TABS - PACKAGE / ALA CARTE

const tabs = document.querySelectorAll('.tab');

tabs.forEach(tab => {
    tab.addEventListener('click', () => {
        tabs.forEach(item => item.classList.remove('active'));
        tab.classList.add('active');
    });
});

// LISTING FILTERS AND VIEW TOGGLE

const listingGrid = document.querySelector('.listing-grid');
const listingCards = document.querySelectorAll('.listing-card');
const gridBtns = document.querySelectorAll('.grid-btn');
const searchInput = document.querySelector('.search-box input');
const statusFilter = document.getElementById('statusFilter');

function applyListingFilters() {
    const searchTerm = searchInput?.value.toLowerCase().trim() || '';
    const selectedStatus = statusFilter?.value || 'all';

    listingCards.forEach(card => {
        const title = card.dataset.title || card.querySelector('h3')?.textContent.toLowerCase() || '';
        const category = card.dataset.category || card.querySelector('.category')?.textContent.toLowerCase() || '';
        const status = card.dataset.status || '';
        const matchesSearch = title.includes(searchTerm) || category.includes(searchTerm);
        const matchesStatus = selectedStatus === 'all' || status === selectedStatus;

        card.hidden = !(matchesSearch && matchesStatus);
    });
}

gridBtns.forEach(button => {
    button.addEventListener('click', () => {
        const view = button.dataset.view || 'grid';

        gridBtns.forEach(item => item.classList.remove('active'));
        button.classList.add('active');
        listingGrid?.classList.toggle('list-view', view === 'list');
    });
});

searchInput?.addEventListener('input', applyListingFilters);
statusFilter?.addEventListener('change', applyListingFilters);

// FORM CHOICES

const categoryCards = document.querySelectorAll('.category-card');
const listingType = document.getElementById('listingType');

categoryCards.forEach(card => {
    card.addEventListener('click', () => {
        categoryCards.forEach(item => item.classList.remove('active'));
        card.classList.add('active');

        if (listingType) {
            listingType.value = card.dataset.type || 'package';
        }
    });
});

document.querySelectorAll('.segmented-choice').forEach(group => {
    group.querySelectorAll('.choice-card').forEach(card => {
        const input = card.querySelector('input');

        card.addEventListener('click', () => {
            group.querySelectorAll('.choice-card').forEach(item => item.classList.remove('active'));
            card.classList.add('active');

            if (input) {
                input.checked = true;
                input.dispatchEvent(new Event('change', { bubbles: true }));
            }
        });
    });
});

const securityRadios = document.querySelectorAll('input[name="requires_security_deposit"]');
const securityAmountGroup = document.getElementById('securityAmountGroup');
const securityAmountInput = document.getElementById('securityDepositAmount');

function syncSecurityAmount() {
    const requiresDeposit = document.querySelector('input[name="requires_security_deposit"]:checked')?.value === '1';

    if (securityAmountGroup) {
        securityAmountGroup.style.display = requiresDeposit ? '' : 'none';
    }

    if (securityAmountInput) {
        securityAmountInput.required = requiresDeposit;
        if (!requiresDeposit) securityAmountInput.value = '0';
    }
}

securityRadios.forEach(radio => radio.addEventListener('change', syncSecurityAmount));
syncSecurityAmount();

document.querySelectorAll('.highlight-options label, .day-options label').forEach(label => {
    const input = label.querySelector('input');

    label.addEventListener('click', () => {
        window.setTimeout(() => {
            label.classList.toggle('active', input?.checked || false);
        }, 0);
    });
});

// UPLOAD FILE LABELS

document.querySelectorAll('.upload-box input[type="file"]').forEach(input => {
    input.addEventListener('change', () => {
        const uploadBox = input.closest('.upload-box');
        const name = input.files?.[0]?.name || uploadBox?.dataset.uploadLabel || 'Upload file';
        const label = uploadBox?.querySelector('strong');

        if (label) label.textContent = name;
    });
});

// REVIEW

function checkedValues(selector) {
    return Array.from(document.querySelectorAll(`${selector}:checked`)).map(input => input.value);
}

function fieldValue(name, fallback = 'Not set') {
    const field = listingForm?.elements[name];
    const value = field?.value?.trim();

    return value || fallback;
}

function updateReview() {
    if (!listingReview) return;

    const highlights = checkedValues('input[name="service_highlights[]"]');
    const days = checkedValues('input[name="available_days[]"]');
    const requiresDeposit = document.querySelector('input[name="requires_security_deposit"]:checked')?.value === '1';
    const requiresLogistics = document.querySelector('input[name="requires_logistics_fee"]:checked')?.value === '1';
    const imageName = document.getElementById('imageUpload')?.files?.[0]?.name || 'Not selected';
    const logoName = document.getElementById('logoUpload')?.files?.[0]?.name || 'No logo';

    const rows = [
        ['Title', fieldValue('title')],
        ['Type', fieldValue('type')],
        ['Base Price', `PHP ${fieldValue('price', '0')}`],
        ['Security Deposit', requiresDeposit ? `Yes, PHP ${fieldValue('security_deposit_amount', '0')}` : 'No'],
        ['Logistics Fee', requiresLogistics ? 'Yes' : 'No'],
        ['Highlights', highlights.length ? highlights.join(', ') : 'None selected'],
        ['Available Days', days.length ? days.join(', ') : 'None selected'],
        ['Service Areas', fieldValue('service_areas')],
        ['Listing Image', imageName],
        ['Logo', logoName],
    ];

    listingReview.innerHTML = rows
        .map(([label, value]) => `<dt>${label}</dt><dd>${value}</dd>`)
        .join('');
}

listingForm?.addEventListener('submit', (event) => {
    if (!validateCurrentStep()) {
        event.preventDefault();
    }
});

updateSteps();

// SIDEBAR DROPDOWN

const dropdownToggle = document.querySelector('.dropdown-toggle');
const dropdownMenu = document.getElementById('dropdownMenu');

if (dropdownToggle && dropdownMenu) {
    dropdownToggle.addEventListener('click', (event) => {
        event.stopPropagation();
        dropdownMenu.classList.toggle('show');
    });

    document.addEventListener('click', (event) => {
        if (!event.target.closest('.vendor-dropdown')) {
            dropdownMenu.classList.remove('show');
        }
    });
}

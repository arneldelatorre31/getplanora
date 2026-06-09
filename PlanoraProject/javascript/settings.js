const dropdownToggle = document.querySelector(".dropdown-toggle");
const dropdownMenu = document.getElementById("dropdownMenu");

if (dropdownToggle && dropdownMenu) {
    dropdownToggle.addEventListener("click", (event) => {
        event.stopPropagation();
        dropdownMenu.classList.toggle("show");
    });

    document.addEventListener("click", (event) => {
        if (!event.target.closest(".vendor-dropdown")) {
            dropdownMenu.classList.remove("show");
        }
    });
}

const modals = document.querySelectorAll(".settings-modal");

function openModal(modalId) {
    const modal = document.getElementById(modalId);

    if (!modal) {
        return;
    }

    modal.classList.add("active");
    document.body.classList.add("modal-open");
}

function closeModal(modal) {
    modal.classList.remove("active");
    document.body.classList.remove("modal-open");
}

document.querySelectorAll("[data-modal]").forEach((button) => {
    button.addEventListener("click", () => {
        document.querySelectorAll(".document-actions.open").forEach((actions) => {
            actions.classList.remove("open");
        });

        if (button.classList.contains("reupload-btn")) {
            const documentTypeSelect = document.getElementById("documentTypeSelect");
            const documentDescriptionInput = document.getElementById("documentDescriptionInput");

            if (documentTypeSelect) {
                documentTypeSelect.value = button.dataset.documentType || "";
            }

            if (documentDescriptionInput) {
                documentDescriptionInput.value = button.dataset.description || "";
            }
        }

        openModal(button.dataset.modal);
    });
});

document.querySelectorAll(".document-actions-toggle").forEach((button) => {
    button.addEventListener("click", (event) => {
        event.stopPropagation();

        const actions = button.closest(".document-actions");
        const wasOpen = actions?.classList.contains("open");

        document.querySelectorAll(".document-actions.open").forEach((openActions) => {
            openActions.classList.remove("open");
        });

        if (actions && !wasOpen) {
            actions.classList.add("open");
        }
    });
});

document.addEventListener("click", (event) => {
    if (!event.target.closest(".document-actions")) {
        document.querySelectorAll(".document-actions.open").forEach((actions) => {
            actions.classList.remove("open");
        });
    }
});

document.querySelectorAll(".delete-document-form").forEach((form) => {
    form.addEventListener("submit", (event) => {
        const submitButton = form.querySelector(".delete-document-btn");

        if (submitButton?.disabled) {
            event.preventDefault();
            return;
        }

        if (!confirm("Delete this pending document?")) {
            event.preventDefault();
        }
    });
});

modals.forEach((modal) => {
    const overlay = modal.querySelector(".modal-overlay");
    const closeButton = modal.querySelector(".modal-close");

    if (overlay) {
        overlay.addEventListener("click", () => closeModal(modal));
    }

    if (closeButton) {
        closeButton.addEventListener("click", () => closeModal(modal));
    }
});

document.addEventListener("keydown", (event) => {
    if (event.key !== "Escape") {
        return;
    }

    modals.forEach((modal) => {
        if (modal.classList.contains("active")) {
            closeModal(modal);
        }
    });
});

document.querySelectorAll('input[type="file"]').forEach((input) => {
    input.addEventListener("change", () => {
        const fileName = input.files?.[0]?.name;

        if (!fileName) {
            return;
        }

        input.classList.add("has-file");
        input.dataset.fileName = fileName;
    });
});

const useCurrentLocationBtn = document.getElementById("useCurrentLocationBtn");
const addressInput = document.getElementById("addressInput");
const locationHelper = document.getElementById("locationHelper");

function setLocationHelper(message, isError = false) {
    if (!locationHelper) {
        return;
    }

    locationHelper.textContent = message;
    locationHelper.classList.toggle("error", isError);
}

async function fillAddressFromCoordinates(latitude, longitude) {
    const coordinateAddress = `${latitude.toFixed(6)}, ${longitude.toFixed(6)}`;

    if (addressInput) {
        addressInput.value = coordinateAddress;
    }

    try {
        const response = await fetch(
            `https://nominatim.openstreetmap.org/reverse?format=jsonv2&lat=${latitude}&lon=${longitude}`
        );

        if (!response.ok) {
            throw new Error("Address lookup failed.");
        }

        const data = await response.json();

        if (data.display_name && addressInput) {
            addressInput.value = data.display_name;
            setLocationHelper("Current address found. Click Save Changes to update it.");
            return;
        }
    } catch (error) {
        setLocationHelper("Coordinates were added. You can edit the address before saving.");
        return;
    }

    setLocationHelper("Coordinates were added. You can edit the address before saving.");
}

if (useCurrentLocationBtn && addressInput) {
    useCurrentLocationBtn.addEventListener("click", () => {
        if (!navigator.geolocation) {
            setLocationHelper("Your browser does not support current location.", true);
            return;
        }

        useCurrentLocationBtn.disabled = true;
        useCurrentLocationBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Finding Location';
        setLocationHelper("Getting your current location...");

        navigator.geolocation.getCurrentPosition(
            async (position) => {
                await fillAddressFromCoordinates(
                    position.coords.latitude,
                    position.coords.longitude
                );

                useCurrentLocationBtn.disabled = false;
                useCurrentLocationBtn.innerHTML = '<i class="fa-solid fa-location-crosshairs"></i> Use My Current Location';
            },
            () => {
                setLocationHelper("Location access was blocked or unavailable.", true);
                useCurrentLocationBtn.disabled = false;
                useCurrentLocationBtn.innerHTML = '<i class="fa-solid fa-location-crosshairs"></i> Use My Current Location';
            },
            {
                enableHighAccuracy: true,
                timeout: 12000,
                maximumAge: 0
            }
        );
    });
}

document.querySelectorAll(".logout-action-btn, .sidebar-logout-link").forEach((button) => {
    button.addEventListener("click", (event) => {
        if (!confirm("Are you sure you want to logout?")) {
            event.preventDefault();
        }
    });
});

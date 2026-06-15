

'use strict';

/* ─────────────────────────────────────────────────────
   1. NAVBAR SCROLL SHADOW
   ───────────────────────────────────────────────────── */
document.addEventListener('DOMContentLoaded', () => {
    const nav = document.getElementById('mainNav');
    if (nav) {
        window.addEventListener('scroll', () => {
            if (window.scrollY > 40) {
                nav.classList.add('scrolled');
            } else {
                nav.classList.remove('scrolled');
            }
        });
    }

    /* ─────────────────────────────────────────────────
       2. SCROLL REVEAL ANIMATION
       ───────────────────────────────────────────────── */
    const revealEls = document.querySelectorAll('.reveal');
    if (revealEls.length) {
        const revealObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('visible');
                    revealObserver.unobserve(entry.target);
                }
            });
        }, { threshold: 0.1 });

        revealEls.forEach(el => revealObserver.observe(el));
    }

    /* ─────────────────────────────────────────────────
       3. BOOKING FORM — PRICE CALCULATOR
       ───────────────────────────────────────────────── */
    const checkinInput  = document.getElementById('tanggal_checkin');
    const checkoutInput = document.getElementById('tanggal_checkout');
    const guestInput    = document.getElementById('jumlah_tamu');
    const pricePerNight = parseFloat(document.getElementById('price_per_night')?.value || '0');

    const nightsDisplay = document.getElementById('calc_nights');
    const subtotalDisplay = document.getElementById('calc_subtotal');
    const serviceFeeDisplay = document.getElementById('calc_service');
    const totalDisplay = document.getElementById('calc_total');
    const totalHiddenInput = document.getElementById('total_harga');

    function calculatePrice() {
        if (!checkinInput || !checkoutInput || !pricePerNight) return;

        const checkin  = new Date(checkinInput.value);
        const checkout = new Date(checkoutInput.value);

        if (isNaN(checkin) || isNaN(checkout) || checkout <= checkin) {
            if (nightsDisplay)   nightsDisplay.textContent  = '—';
            if (subtotalDisplay) subtotalDisplay.textContent = '—';
            if (serviceFeeDisplay) serviceFeeDisplay.textContent = '—';
            if (totalDisplay)    totalDisplay.textContent   = '—';
            return;
        }

        const nights     = Math.ceil((checkout - checkin) / (1000 * 60 * 60 * 24));
        const subtotal   = nights * pricePerNight;
        const serviceFee = subtotal * 0.12;
        const total      = subtotal + serviceFee;

        const fmt = (n) => 'Rp ' + Math.round(n).toLocaleString('id-ID');

        if (nightsDisplay)     nightsDisplay.textContent   = nights + ' night' + (nights > 1 ? 's' : '');
        if (subtotalDisplay)   subtotalDisplay.textContent  = fmt(subtotal);
        if (serviceFeeDisplay) serviceFeeDisplay.textContent = fmt(serviceFee);
        if (totalDisplay)      totalDisplay.textContent     = fmt(total);
        if (totalHiddenInput)  totalHiddenInput.value       = Math.round(total);
    }

    if (checkinInput)  checkinInput.addEventListener('change', calculatePrice);
    if (checkoutInput) checkoutInput.addEventListener('change', calculatePrice);
    calculatePrice(); 

    /* ─────────────────────────────────────────────────
       4. CHECKOUT MUST BE AFTER CHECKIN
       ───────────────────────────────────────────────── */
    if (checkinInput && checkoutInput) {
        checkinInput.addEventListener('change', () => {
            if (checkoutInput.value && checkoutInput.value <= checkinInput.value) {
                checkoutInput.value = '';
                showFieldError(checkoutInput, 'Checkout must be after check-in.');
            }

            checkoutInput.min = checkinInput.value;
        });
    }

 
    if (checkinInput && !checkinInput.value) {
        checkinInput.min = new Date().toISOString().split('T')[0];
    }

    /* ─────────────────────────────────────────────────
       5. BOOKING FORM VALIDATION
       ───────────────────────────────────────────────── */
    const bookingForm = document.getElementById('bookingForm');
    if (bookingForm) {
        bookingForm.addEventListener('submit', (e) => {
            let valid = true;

            const nama = document.getElementById('nama_pemesan');
            if (nama && nama.value.trim().length < 3) {
                e.preventDefault();
                showFieldError(nama, 'Name must be at least 3 characters.');
                valid = false;
            } else if (nama) {
                clearFieldError(nama);
            }

  
            const ci = document.getElementById('tanggal_checkin');
            if (ci && !ci.value) {
                e.preventDefault();
                showFieldError(ci, 'Check-in date is required.');
                valid = false;
            } else if (ci) {
                clearFieldError(ci);
            }

            const co = document.getElementById('tanggal_checkout');
            if (co && !co.value) {
                e.preventDefault();
                showFieldError(co, 'Check-out date is required.');
                valid = false;
            } else if (co && ci && co.value <= ci.value) {
                e.preventDefault();
                showFieldError(co, 'Check-out date must be after check-in.');
                valid = false;
            } else if (co) {
                clearFieldError(co);
            }

            return valid;
        });
    }

    /* ─────────────────────────────────────────────────
       6. ADD/EDIT ROOM FORM VALIDATION
       ───────────────────────────────────────────────── */
    const roomForm = document.getElementById('roomForm') || document.getElementById('editRoomForm');
    if (roomForm) {
        roomForm.addEventListener('submit', (e) => {
            let valid = true;

            const namaKamar = document.getElementById('nama_kamar');
            if (namaKamar && namaKamar.value.trim().length < 2) {
                e.preventDefault();
                showFieldError(namaKamar, 'Room name is required (minimum 2 characters).');
                valid = false;
            } else if (namaKamar) {
                clearFieldError(namaKamar);
            }

            const harga = document.getElementById('harga_per_malam');
            if (harga && (isNaN(parseFloat(harga.value)) || parseFloat(harga.value) <= 0)) {
                e.preventDefault();
                showFieldError(harga, 'Price must be a positive number.');
                valid = false;
            } else if (harga) {
                clearFieldError(harga);
            }

            const kapasitas = document.getElementById('kapasitas');
            if (kapasitas && (isNaN(parseInt(kapasitas.value)) || parseInt(kapasitas.value) < 1)) {
                e.preventDefault();
                showFieldError(kapasitas, 'Capacity must be at least 1 guest.');
                valid = false;
            } else if (kapasitas) {
                clearFieldError(kapasitas);
            }

            if (valid && roomForm.id === 'editRoomForm') {
                if (!confirm('Are you sure you want to save changes to this room?')) {
                    e.preventDefault();
                    valid = false;
                }
            }

            return valid;
        });
    }

    /* ─────────────────────────────────────────────────
       7. LOGIN FORM VALIDATION
       ───────────────────────────────────────────────── */
    const loginForm = document.getElementById('loginForm');
    if (loginForm) {
        loginForm.addEventListener('submit', (e) => {
            let valid = true;

            const username = document.getElementById('username');
            if (username && username.value.trim() === '') {
                e.preventDefault();
                showFieldError(username, 'Username is required.');
                valid = false;
            } else if (username) {
                clearFieldError(username);
            }

            const password = document.getElementById('password');
            if (password && password.value.length < 4) {
                e.preventDefault();
                showFieldError(password, 'Password is required.');
                valid = false;
            } else if (password) {
                clearFieldError(password);
            }

            return valid;
        });
    }

    /* ─────────────────────────────────────────────────
       7b. REVIEW FORM VALIDATION
       ───────────────────────────────────────────────── */
    const reviewForm = document.getElementById('reviewForm');
    if (reviewForm) {
        reviewForm.addEventListener('submit', (e) => {
            let valid = true;

            const commentInput = document.getElementById('komentar');
            const commentVal = commentInput ? commentInput.value.trim() : '';
            const commentError = document.getElementById('commentError');

            const ratings = document.getElementsByName('rating');
            let ratingChecked = false;
            for (const r of ratings) {
                if (r.checked) {
                    ratingChecked = true;
                    break;
                }
            }

            const ratingError = document.getElementById('ratingError');
            if (!ratingChecked) {
                e.preventDefault();
                if (ratingError) {
                    ratingError.textContent = 'Please choose a star rating.';
                    ratingError.classList.add('visible');
                }
                valid = false;
            } else if (ratingError) {
                ratingError.textContent = '';
                ratingError.classList.remove('visible');
            }

            if (commentInput) {
                if (commentVal.length < 5) {
                    e.preventDefault();
                    if (commentError) {
                        commentError.textContent = 'Comments must be at least 5 characters long.';
                        commentError.classList.add('visible');
                    }
                    commentInput.classList.add('is-invalid');
                    valid = false;
                } else if (commentVal.length > 1000) {
                    e.preventDefault();
                    if (commentError) {
                        commentError.textContent = 'Comments cannot exceed 1000 characters.';
                        commentError.classList.add('visible');
                    }
                    commentInput.classList.add('is-invalid');
                    valid = false;
                } else {
                    if (commentError) {
                        commentError.textContent = '';
                        commentError.classList.remove('visible');
                    }
                    commentInput.classList.remove('is-invalid');
                }
            }

            return valid;
        });
    }

    /* ─────────────────────────────────────────────────
       8. CONFIRM DIALOGS (delete / cancel)
       ───────────────────────────────────────────────── */
    document.querySelectorAll('[data-confirm]').forEach(el => {
        el.addEventListener('click', (e) => {
            const msg = el.dataset.confirm || 'Are you sure?';
            if (!confirm(msg)) {
                e.preventDefault();
            }
        });
    });

    const editBookingForm = document.getElementById('editBookingForm');
    if (editBookingForm) {
        editBookingForm.addEventListener('submit', (e) => {
            if (!confirm('Are you sure you want to modify this reservation?')) {
                e.preventDefault();
            }
        });
    }

    /* ─────────────────────────────────────────────────
       9. LIVE SEARCH INPUT DEBOUNCE (rooms page)
       ───────────────────────────────────────────────── */
    const searchInput = document.getElementById('searchInput');
    if (searchInput) {
        let searchTimer;
        searchInput.addEventListener('input', () => {
            clearTimeout(searchTimer);
            searchTimer = setTimeout(() => {
                const form = searchInput.closest('form');
                if (form) form.submit();
            }, 500);
        });
    }

    /* ─────────────────────────────────────────────────
       10. INLINE REAL-TIME VALIDATION (blur events)
       ───────────────────────────────────────────────── */
    document.querySelectorAll('[data-validate]').forEach(input => {
        input.addEventListener('blur', () => {
            const rule = input.dataset.validate;
            if (rule === 'required' && input.value.trim() === '') {
                showFieldError(input, input.dataset.errorMsg || 'This field is required.');
            } else if (rule === 'email' && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(input.value)) {
                showFieldError(input, 'Invalid email address.');
            } else if (rule === 'positive-number' && (isNaN(parseFloat(input.value)) || parseFloat(input.value) <= 0)) {
                showFieldError(input, 'Must be a positive number.');
            } else {
                clearFieldError(input);
            }
        });
        input.addEventListener('focus', () => clearFieldError(input));
    });

    /* ─────────────────────────────────────────────────
       11. ROOM IMAGE GALLERY THUMBNAIL SWITCHER
       ───────────────────────────────────────────────── */
    const mainImg = document.getElementById('mainGalleryImg');
    document.querySelectorAll('.gallery-thumb').forEach(thumb => {
        thumb.addEventListener('click', () => {
            if (mainImg) {
                mainImg.style.opacity = '0';
                setTimeout(() => {
                    mainImg.src = thumb.src;
                    mainImg.style.opacity = '1';
                }, 300);
            }
        });
    });
    if (mainImg) {
        mainImg.style.transition = 'opacity 0.3s ease';
    }

    /* ─────────────────────────────────────────────────
       12. PROFILE FORM — Live input label color
       ───────────────────────────────────────────────── */
    document.querySelectorAll('.elysian-form-control').forEach(input => {
        input.addEventListener('focus', () => {
            const label = input.parentElement.querySelector('.elysian-form-label');
            if (label) label.style.color = '#775a19';
        });
        input.addEventListener('blur', () => {
            const label = input.parentElement.querySelector('.elysian-form-label');
            if (label) label.style.color = '';
        });
    });

});

/* ─────────────────────────────────────────────────────
   HELPERS: showFieldError / clearFieldError
   ───────────────────────────────────────────────────── */
function showFieldError(input, message) {
    input.classList.add('is-invalid');
    let errEl = input.parentElement.querySelector('.form-error-msg');
    if (!errEl) {
        errEl = document.createElement('p');
        errEl.className = 'form-error-msg';
        input.parentElement.appendChild(errEl);
    }
    errEl.textContent = message;
    errEl.classList.add('visible');
}

function clearFieldError(input) {
    input.classList.remove('is-invalid');
    const errEl = input.parentElement.querySelector('.form-error-msg');
    if (errEl) errEl.classList.remove('visible');
}
